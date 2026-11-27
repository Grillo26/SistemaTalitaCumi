import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { forkJoin, of, switchMap } from 'rxjs';
import { ToastService } from '../../../core/services/toast.service';
import { environment } from '../../../../environments/environment';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../../shared/components/ui/modal/modal.component';
import { PaginationComponent } from '../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { TABLE_IMPORTS } from '../../../shared/components/ui/table';
import { TextareaDirective } from '../../../shared/components/ui/textarea/textarea.directive';
import { Branch } from '../../organizacion/sucursales/sucursales.models';
import { SucursalesService } from '../../organizacion/sucursales/sucursales.service';
import { Sala } from '../../organizacion/salas-ambientes/salas-ambientes.models';
import { SalasAmbientesService } from '../../organizacion/salas-ambientes/salas-ambientes.service';
import { CatalogoServiciosService } from '../../catalogo-servicios/servicios/servicios.service';
import { Servicio } from '../../catalogo-servicios/servicios/servicios.models';
import { CategoriaTarifa } from '../../catalogo-servicios/tarifas/tarifas.models';
import { TarifasService } from '../../catalogo-servicios/tarifas/tarifas.service';
import { Paciente } from '../../pacientes/pacientes.models';
import { PacientesService } from '../../pacientes/pacientes.service';
import { InscripcionComprobanteComponent } from './inscripcion-comprobante.component';
import { InscripcionDateInputComponent } from './inscripcion-date-input.component';
import { InscripcionPacienteSearchComponent } from './inscripcion-paciente-search.component';
import {
  AsistenciaResumen,
  BajaPayload,
  ComprobanteData,
  EMPTY_ABILITIES,
  ESTADO_OPTIONS,
  InscripcionAbilities,
  InscripcionApiError,
  InscripcionDetalle,
  InscripcionListParams,
  InscripcionPayload,
  InscripcionRow,
  InscripcionUpdatePayload,
  MONTH_OPTIONS,
  MOTIVO_BAJA_OPTIONS,
  PagoEstado,
  PreviewMonto,
  PreviewMontoResponse,
  RenovacionPayload,
  RenovacionPreview,
  SALAS_GRUPALES_TEXTS,
  TurnosFuturosResumen,
  formatBs,
  formatDate,
} from './salas-grupales.models';
import { SalasGrupalesService } from './salas-grupales.service';

type FormMode = 'create' | 'edit';
type FichaTab = 'resumen' | 'pago' | 'asistencia' | 'comprobante';

@Component({
  selector: 'app-salas-grupales-page',
  imports: [
    ReactiveFormsModule,
    PageBreadcrumbComponent,
    ComponentCardComponent,
    ButtonComponent,
    BadgeComponent,
    AlertComponent,
    PaginationComponent,
    ModalComponent,
    SelectFieldComponent,
    TextareaDirective,
    InscripcionDateInputComponent,
    InscripcionPacienteSearchComponent,
    InscripcionComprobanteComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './salas-grupales.page.html',
})
export class SalasGrupalesPageComponent implements OnInit {
  private readonly service = inject(SalasGrupalesService);
  private readonly sucursalesService = inject(SucursalesService);
  private readonly salasService = inject(SalasAmbientesService);
  private readonly catalogoService = inject(CatalogoServiciosService);
  private readonly tarifasService = inject(TarifasService);
  private readonly pacientesService = inject(PacientesService);
  private readonly toast = inject(ToastService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = SALAS_GRUPALES_TEXTS;
  readonly todayDate = this.today();
  readonly motivoOptions = MOTIVO_BAJA_OPTIONS;
  readonly estadoOptions = ESTADO_OPTIONS;
  readonly monthOptions = MONTH_OPTIONS;
  readonly formatBs = formatBs;
  readonly formatDate = formatDate;

  readonly rows = signal<InscripcionRow[]>([]);
  readonly abilities = signal<InscripcionAbilities>(EMPTY_ABILITIES);
  readonly loading = signal(false);
  readonly listError = signal<string | null>(null);
  readonly page = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly from = signal<number | null>(null);
  readonly to = signal<number | null>(null);
  readonly sort = signal<'fecha_inicio' | 'fecha_fin' | 'estado' | 'monto_esperado'>(
    'fecha_inicio',
  );
  readonly direction = signal<'asc' | 'desc'>('desc');

  readonly branches = signal<Branch[]>([]);
  readonly salas = signal<Sala[]>([]);
  readonly servicios = signal<Servicio[]>([]);
  readonly categorias = signal<CategoriaTarifa[]>([]);

  readonly filterPaciente = signal<Paciente | null>(null);
  readonly filters = this.fb.nonNullable.group({
    sucursal_id: [''],
    sala_id: [''],
    periodo_mes: [String(new Date().getMonth() + 1)],
    periodo_anio: [String(new Date().getFullYear())],
    estado: [''],
  });

  readonly formOpen = signal(false);
  readonly formMode = signal<FormMode>('create');
  readonly formSaving = signal(false);
  readonly formError = signal<string | null>(null);
  readonly formFieldErrors = signal<Record<string, string[]>>({});
  readonly formPaciente = signal<Paciente | null>(null);
  readonly editingPatient = signal<{
    id: number;
    nombre_completo: string;
    estado: string;
    estado_label: string;
  } | null>(null);
  readonly formTutores = signal<{ id: number; nombre_completo: string; activo?: boolean }[]>([]);
  readonly formTutoresLoading = signal(false);
  readonly formTutoresError = signal<string | null>(null);
  readonly formTariff = signal<PreviewMonto | null>(null);
  readonly formTariffLoading = signal(false);
  readonly formTariffError = signal<string | null>(null);
  readonly formTariffChanged = signal<PreviewMonto | null>(null);
  readonly formConfirmTariff = signal(false);
  readonly editingId = signal<number | null>(null);
  readonly editingTariffId = signal<number | null>(null);
  readonly editingAmount = signal<string | null>(null);

  readonly form = this.fb.nonNullable.group({
    tutor_responsable_id: ['', [Validators.required]],
    sucursal_id: ['', [Validators.required]],
    sala_id: ['', [Validators.required]],
    servicio_id: ['', [Validators.required]],
    categoria_tarifa_id: ['', [Validators.required]],
    fecha_inicio: ['', [Validators.required]],
    fecha_fin: ['', [Validators.required]],
    observaciones: [''],
  });

  readonly fichaOpen = signal(false);
  readonly fichaLoading = signal(false);
  readonly fichaError = signal<string | null>(null);
  readonly ficha = signal<InscripcionDetalle | null>(null);
  readonly fichaTab = signal<FichaTab>('resumen');
  readonly pago = signal<PagoEstado | null>(null);
  readonly pagoError = signal<string | null>(null);
  readonly pagoLoading = signal(false);
  readonly asistencia = signal<AsistenciaResumen | null>(null);
  readonly asistenciaError = signal<string | null>(null);
  readonly asistenciaLoading = signal(false);

  readonly bajaOpen = signal(false);
  readonly bajaSaving = signal(false);
  readonly bajaError = signal<string | null>(null);
  readonly bajaFieldErrors = signal<Record<string, string[]>>({});
  readonly bajaInscripcion = signal<InscripcionDetalle | null>(null);
  readonly turnosFuturos = signal<TurnosFuturosResumen | null>(null);
  readonly turnosFuturosError = signal<string | null>(null);
  readonly bajaForm = this.fb.nonNullable.group({
    motivo: ['retiro_paciente', [Validators.required]],
    motivo_detalle: [''],
    fecha_baja: [this.today(), [Validators.required]],
  });

  readonly renovacionOpen = signal(false);
  readonly renovacionLoading = signal(false);
  readonly renovacionSaving = signal(false);
  readonly renovacionError = signal<string | null>(null);
  readonly renovacionFieldErrors = signal<Record<string, string[]>>({});
  readonly renovacionInscripcion = signal<InscripcionDetalle | null>(null);
  readonly renovacionPreview = signal<RenovacionPreview | null>(null);
  readonly renovacionTariff = signal<PreviewMonto | null>(null);
  readonly renovacionTariffLoading = signal(false);
  readonly renovacionTariffError = signal<string | null>(null);
  readonly renovacionTariffChanged = signal<PreviewMonto | null>(null);
  readonly renovacionConfirmTariff = signal(false);
  readonly renovacionForm = this.fb.nonNullable.group({
    fecha_inicio: ['', [Validators.required]],
    fecha_fin: ['', [Validators.required]],
    servicio_id: ['', [Validators.required]],
    categoria_tarifa_id: ['', [Validators.required]],
    observaciones: [''],
  });

  readonly comprobanteOpen = signal(false);
  readonly comprobanteLoading = signal(false);
  readonly comprobanteError = signal<string | null>(null);
  readonly comprobante = signal<ComprobanteData | null>(null);

  readonly branchOptions = computed(() =>
    this.branches().map((branch) => ({ value: String(branch.id), label: branch.nombre })),
  );

  readonly filterSalaOptions = computed(() => {
    const branchId = this.filters.controls.sucursal_id.value;
    return this.salas()
      .filter((sala) => sala.activo && (branchId === '' || sala.sucursal?.id === Number(branchId)))
      .map((sala) => ({ value: String(sala.id), label: this.salaLabel(sala) }));
  });

  readonly formSalaOptions = computed(() => {
    const branchId = this.form.controls.sucursal_id.value;
    const currentSala = this.form.controls.sala_id.value;
    return this.salas()
      .filter(
        (sala) =>
          (sala.activo || (this.formMode() === 'edit' && String(sala.id) === currentSala)) &&
          (branchId === '' || sala.sucursal?.id === Number(branchId)),
      )
      .map((sala) => ({ value: String(sala.id), label: this.salaLabel(sala) }));
  });

  readonly serviceOptions = computed(() =>
    this.servicios().map((servicio) => ({
      value: String(servicio.id),
      label: servicio.nombre,
    })),
  );

  readonly categoriaOptions = computed(() =>
    this.categorias()
      .filter((categoria) => categoria.activo)
      .map((categoria) => ({ value: String(categoria.id), label: categoria.nombre })),
  );

  readonly tutorOptions = computed(() =>
    this.formTutores().map((tutor) => ({
      value: String(tutor.id),
      label: tutor.nombre_completo + (tutor.activo === false ? ' (inactivo)' : ''),
    })),
  );

  readonly yearOptions = computed(() => {
    const current = new Date().getFullYear();
    return [
      { value: '', label: 'Todos los años' },
      ...Array.from({ length: 4 }, (_, index) => current - 2 + index).map((year) => ({
        value: String(year),
        label: String(year),
      })),
    ];
  });

  readonly selectedPatientIsActive = computed(() => this.formPaciente()?.estado === 'activo');
  readonly editing = computed(() => this.editingId() !== null);
  readonly motivoOtro = computed(() => this.bajaForm.controls.motivo.value === 'otro');

  ngOnInit(): void {
    this.form.controls.fecha_inicio.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.requestPreview());

    this.renovacionForm.controls.fecha_inicio.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.requestRenovacionPreview());

    this.loadOptions();
    this.load();
  }

  loadOptions(): void {
    this.sucursalesService
      .list({ estado: 'activa', page: 1, per_page: 50, sort: 'nombre', direction: 'asc' })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.branches.set(response.data),
        error: () => this.branches.set([]),
      });

    this.loadAllSalas()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (salas) => this.salas.set(salas),
        error: () => this.salas.set([]),
      });

    this.loadAllServicios()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (servicios) => this.servicios.set(servicios),
        error: () => this.servicios.set([]),
      });

    this.tarifasService
      .listCategorias({ estado: 'activo' })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.categorias.set(response.data),
        error: () => this.categorias.set([]),
      });
  }

  load(page = this.page()): void {
    this.loading.set(true);
    this.listError.set(null);
    this.page.set(page);

    const raw = this.filters.getRawValue();
    const params: InscripcionListParams = {
      paciente_id: this.filterPaciente()?.id ?? '',
      sucursal_id: raw.sucursal_id ? Number(raw.sucursal_id) : '',
      sala_id: raw.sala_id ? Number(raw.sala_id) : '',
      periodo_mes: raw.periodo_mes ? Number(raw.periodo_mes) : '',
      periodo_anio: raw.periodo_anio ? Number(raw.periodo_anio) : '',
      estado: raw.estado as InscripcionListParams['estado'],
      page,
      per_page: 10,
      sort: this.sort(),
      direction: this.direction(),
    };

    this.service
      .list(params)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.rows.set(response.data);
          this.abilities.set(response.can);
          this.lastPage.set(response.meta.last_page);
          this.total.set(response.meta.total);
          this.from.set(response.meta.from);
          this.to.set(response.meta.to);
          this.loading.set(false);
        },
        error: (error: InscripcionApiError) => {
          this.loading.set(false);
          this.rows.set([]);
          this.listError.set(error.message);
        },
      });
  }

  applyFilters(): void {
    this.load(1);
  }

  clearFilters(): void {
    this.filterPaciente.set(null);
    this.filters.reset({
      sucursal_id: '',
      sala_id: '',
      periodo_mes: '',
      periodo_anio: '',
      estado: '',
    });
    this.load(1);
  }

  onFilterPaciente(paciente: Paciente | null): void {
    this.filterPaciente.set(paciente);
    this.load(1);
  }

  onFilterBranchChange(value: string): void {
    this.filters.controls.sucursal_id.setValue(value);
    this.filters.controls.sala_id.setValue('');
  }

  goToPage(page: number): void {
    if (page < 1 || page > this.lastPage()) {
      return;
    }

    this.load(page);
  }

  setSort(sort: 'fecha_inicio' | 'fecha_fin' | 'estado' | 'monto_esperado'): void {
    if (this.sort() === sort) {
      this.direction.set(this.direction() === 'asc' ? 'desc' : 'asc');
    } else {
      this.sort.set(sort);
      this.direction.set('desc');
    }

    this.load(1);
  }

  openCreate(): void {
    this.formMode.set('create');
    this.editingId.set(null);
    this.editingTariffId.set(null);
    this.editingAmount.set(null);
    this.formPaciente.set(null);
    this.editingPatient.set(null);
    this.formTutores.set([]);
    this.formTutoresError.set(null);
    this.formTariff.set(null);
    this.formTariffChanged.set(null);
    this.formConfirmTariff.set(false);
    this.form.reset({
      tutor_responsable_id: '',
      sucursal_id: '',
      sala_id: '',
      servicio_id: '',
      categoria_tarifa_id: '',
      fecha_inicio: this.today(),
      fecha_fin: this.defaultEnd(this.today()),
      observaciones: '',
    });
    this.formError.set(null);
    this.formFieldErrors.set({});
    this.formOpen.set(true);
  }

  openEdit(inscripcion: InscripcionDetalle): void {
    this.formMode.set('edit');
    this.editingId.set(inscripcion.id);
    this.editingTariffId.set(inscripcion.tarifa_id);
    this.editingAmount.set(inscripcion.monto_esperado);
    this.formPaciente.set(null);
    this.editingPatient.set({
      id: inscripcion.paciente.id,
      nombre_completo: inscripcion.paciente.nombre_completo,
      estado: inscripcion.paciente.estado,
      estado_label: inscripcion.paciente.estado_label,
    });
    this.formTutores.set([
      {
        id: inscripcion.tutor.id,
        nombre_completo: inscripcion.tutor.nombre_completo,
        activo: true,
      },
    ]);
    this.formTutoresError.set(null);
    this.form.reset({
      tutor_responsable_id: String(inscripcion.tutor.id),
      sucursal_id: String(inscripcion.sucursal.id),
      sala_id: String(inscripcion.sala.id),
      servicio_id: String(inscripcion.servicio.id),
      categoria_tarifa_id: String(inscripcion.categoria.id),
      fecha_inicio: inscripcion.fecha_inicio,
      fecha_fin: inscripcion.fecha_fin,
      observaciones: inscripcion.observaciones ?? '',
    });
    this.formTariff.set(null);
    this.formTariffChanged.set(null);
    this.formConfirmTariff.set(false);
    this.formError.set(null);
    this.formFieldErrors.set({});
    this.formOpen.set(true);
    this.requestPreview();
  }

  closeForm(): void {
    this.formOpen.set(false);
    this.formError.set(null);
    this.formFieldErrors.set({});
  }

  onFormPaciente(paciente: Paciente | null): void {
    this.formPaciente.set(paciente);
    this.form.controls.tutor_responsable_id.setValue('');
    this.formTutores.set([]);
    this.formTutoresError.set(null);

    if (paciente === null) {
      return;
    }

    this.formTutoresLoading.set(true);
    this.pacientesService
      .getPaciente(paciente.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.formTutoresLoading.set(false);
          const tutores = response.data.tutores ?? [];

          if (tutores.length === 0) {
            this.formTutoresError.set(
              'El paciente no tiene tutores disponibles. Registrá uno antes de inscribir.',
            );
            return;
          }

          if (!tutores.some((tutor) => tutor.activo)) {
            this.formTutoresError.set(
              'El paciente no tiene tutores activos. Reactivá o registrá un tutor antes de inscribir.',
            );
          }

          this.formTutores.set(
            tutores.map((tutor) => ({
              id: tutor.tutor_id,
              nombre_completo: tutor.nombre_completo,
              activo: tutor.activo,
            })),
          );
          const principal = tutores.find((tutor) => tutor.es_principal && tutor.activo);
          const firstActive = tutores.find((tutor) => tutor.activo);

          if (principal ?? firstActive) {
            this.form.controls.tutor_responsable_id.setValue(
              String((principal ?? firstActive)!.tutor_id),
            );
          }
        },
        error: (error: { message?: string }) => {
          this.formTutoresLoading.set(false);
          this.formTutoresError.set(error.message ?? 'No se pudieron cargar los tutores.');
        },
      });
  }

  onFormBranchChange(value: string): void {
    this.form.controls.sucursal_id.setValue(value);
    this.form.controls.sala_id.setValue('');
    this.requestPreview();
  }

  onFormServiceChange(value: string): void {
    this.form.controls.servicio_id.setValue(value);
    this.requestPreview();
  }

  onFormCategoryChange(value: string): void {
    this.form.controls.categoria_tarifa_id.setValue(value);
    this.requestPreview();
  }

  onFormDateChange(field: 'fecha_inicio' | 'fecha_fin', value: string): void {
    this.form.controls[field].setValue(value);
    this.requestPreview();
  }

  requestPreview(): void {
    const raw = this.form.getRawValue();

    if (!raw.servicio_id || !raw.sucursal_id || !raw.categoria_tarifa_id || !raw.fecha_inicio) {
      this.formTariff.set(null);
      this.formTariffChanged.set(null);
      this.formTariffError.set(null);
      return;
    }

    this.formTariffLoading.set(true);
    this.formTariffError.set(null);

    this.service
      .previewMonto({
        servicio_id: Number(raw.servicio_id),
        sucursal_id: Number(raw.sucursal_id),
        categoria_tarifa_id: Number(raw.categoria_tarifa_id),
        fecha_inicio: raw.fecha_inicio,
        tarifa_id_esperada: this.editingTariffId(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response: PreviewMontoResponse) => {
          this.formTariffLoading.set(false);
          this.formTariff.set(response.data);

          if (response.data === null) {
            this.formTariffError.set(response.message ?? this.texts.noTariff);
            this.formTariffChanged.set(null);
            return;
          }

          if (
            this.editingTariffId() !== null &&
            response.data.tarifa_id !== this.editingTariffId()
          ) {
            this.formTariffChanged.set(response.data);
            this.formConfirmTariff.set(false);
          } else {
            this.formTariffChanged.set(null);
          }
        },
        error: (error: InscripcionApiError) => {
          this.formTariffLoading.set(false);
          this.formTariff.set(null);
          this.formTariffError.set(error.message);
        },
      });
  }

  saveForm(): void {
    this.form.markAllAsTouched();
    this.formError.set(null);
    this.formFieldErrors.set({});

    if (this.form.invalid) {
      return;
    }

    if (this.formMode() === 'create' && this.formPaciente() === null) {
      this.formError.set(this.texts.selectPatient);
      return;
    }

    if (!this.selectedPatientIsActive() && this.formMode() === 'create') {
      this.formError.set(this.texts.patientInactive);
      return;
    }

    if (this.formTariffChanged() !== null && !this.formConfirmTariff()) {
      this.formError.set(this.texts.tariffChangedDetail);
      return;
    }

    const raw = this.form.getRawValue();
    this.formSaving.set(true);

    if (this.formMode() === 'edit') {
      const id = this.editingId();

      if (id === null) {
        this.formSaving.set(false);
        return;
      }

      const payload: InscripcionUpdatePayload = {
        fecha_inicio: raw.fecha_inicio,
        fecha_fin: raw.fecha_fin,
        servicio_id: Number(raw.servicio_id),
        categoria_tarifa_id: Number(raw.categoria_tarifa_id),
        observaciones: raw.observaciones || null,
        tarifa_id_esperada: this.editingTariffId(),
        confirmar_tarifa_cambio: this.formConfirmTariff(),
      };

      this.service
        .update(id, payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: (response) => {
            this.formSaving.set(false);
            this.formOpen.set(false);
            this.toast.success(
              'Inscripción actualizada',
              'Los cambios quedaron registrados en bitácora.',
            );
            this.load();
            this.openFicha(response.data.id);
          },
          error: (error: InscripcionApiError) => this.handleFormError(error),
        });

      return;
    }

    const paciente = this.formPaciente();

    if (paciente === null) {
      this.formSaving.set(false);
      return;
    }

    const payload: InscripcionPayload = {
      paciente_id: paciente.id,
      tutor_responsable_id: Number(raw.tutor_responsable_id),
      sucursal_id: Number(raw.sucursal_id),
      sala_id: Number(raw.sala_id),
      servicio_id: Number(raw.servicio_id),
      categoria_tarifa_id: Number(raw.categoria_tarifa_id),
      fecha_inicio: raw.fecha_inicio,
      fecha_fin: raw.fecha_fin,
      observaciones: raw.observaciones || null,
      tarifa_id_esperada: this.formTariff()?.tarifa_id ?? null,
      confirmar_tarifa_cambio: this.formConfirmTariff(),
    };

    this.service
      .create(payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.formSaving.set(false);
          this.formOpen.set(false);
          this.toast.success('Inscripción creada', 'La inscripción quedó activa.');
          this.load();
          this.openFicha(response.data.id);
        },
        error: (error: InscripcionApiError) => this.handleFormError(error),
      });
  }

  openFicha(id: number): void {
    this.fichaOpen.set(true);
    this.fichaLoading.set(true);
    this.fichaError.set(null);
    this.ficha.set(null);
    this.fichaTab.set('resumen');
    this.pago.set(null);
    this.pagoError.set(null);
    this.asistencia.set(null);
    this.asistenciaError.set(null);
    this.comprobante.set(null);
    this.comprobanteError.set(null);

    this.service
      .get(id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.fichaLoading.set(false);
          this.ficha.set(response.data);
          this.abilities.set(response.can);
        },
        error: (error: InscripcionApiError) => {
          this.fichaLoading.set(false);
          this.fichaError.set(error.message);
        },
      });
  }

  closeFicha(): void {
    this.fichaOpen.set(false);
    this.ficha.set(null);
    this.fichaError.set(null);
  }

  setFichaTab(tab: FichaTab): void {
    this.fichaTab.set(tab);

    if (tab === 'pago' && this.pago() === null && this.pagoError() === null) {
      this.loadPago();
    }

    if (tab === 'asistencia' && this.asistencia() === null && this.asistenciaError() === null) {
      this.loadAsistencia();
    }

    if (tab === 'comprobante' && this.comprobante() === null && this.comprobanteError() === null) {
      this.loadComprobante();
    }
  }

  loadPago(): void {
    const detalle = this.ficha();

    if (detalle === null) {
      return;
    }

    if (!detalle.can.pago_disponible) {
      this.pagoError.set(this.texts.noPayment);
      return;
    }

    this.pagoLoading.set(true);
    this.pagoError.set(null);
    this.service
      .getPago(detalle.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.pagoLoading.set(false);
          this.pago.set(response.data);
        },
        error: (error: InscripcionApiError) => {
          this.pagoLoading.set(false);
          this.pagoError.set(error.message);
        },
      });
  }

  loadAsistencia(): void {
    const detalle = this.ficha();

    if (detalle === null) {
      return;
    }

    if (!detalle.can.asistencia_disponible) {
      this.asistenciaError.set(this.texts.noAttendance);
      return;
    }

    this.asistenciaLoading.set(true);
    this.asistenciaError.set(null);
    this.service
      .getAsistencia(detalle.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.asistenciaLoading.set(false);
          this.asistencia.set(response.data);
        },
        error: (error: InscripcionApiError) => {
          this.asistenciaLoading.set(false);
          this.asistenciaError.set(error.message);
        },
      });
  }

  loadComprobante(): void {
    const detalle = this.ficha();

    if (detalle === null) {
      return;
    }

    this.comprobanteLoading.set(true);
    this.comprobanteError.set(null);
    this.service
      .getComprobante(detalle.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.comprobanteLoading.set(false);
          this.comprobante.set(response.data);
        },
        error: (error: InscripcionApiError) => {
          this.comprobanteLoading.set(false);
          this.comprobanteError.set(error.message);
        },
      });
  }

  openComprobante(): void {
    const detalle = this.ficha();

    if (detalle === null) {
      return;
    }

    if (this.comprobante() === null) {
      this.loadComprobante();
    }

    this.comprobanteOpen.set(true);
  }

  closeComprobante(): void {
    this.comprobanteOpen.set(false);
  }

  registrarPago(): void {
    const detalle = this.ficha();

    if (detalle === null) {
      return;
    }

    this.pagoLoading.set(true);
    this.service
      .registrarPago(detalle.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.pagoLoading.set(false);
          this.toast.success('Pago iniciado', 'El módulo de Pagos y Caja continuará el registro.');
          const url = (response.data as { url?: string | null })?.url;

          if (url) {
            window.open(url, '_blank', 'noopener');
          }

          this.loadPago();
        },
        error: (error: InscripcionApiError) => {
          this.pagoLoading.set(false);
          this.pagoError.set(error.message);
        },
      });
  }

  openEditById(id: number): void {
    this.withDetail(id, (detalle) => this.openEdit(detalle));
  }

  openBajaById(id: number): void {
    this.withDetail(id, (detalle) => this.openBaja(detalle));
  }

  openRenovacionById(id: number): void {
    this.withDetail(id, (detalle) => this.openRenovacion(detalle));
  }

  private withDetail(id: number, action: (detalle: InscripcionDetalle) => void): void {
    this.service
      .get(id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => action(response.data),
        error: (error: InscripcionApiError) =>
          this.toast.error('No se pudo abrir la inscripción', error.message),
      });
  }

  openEditFromFicha(): void {
    const detalle = this.ficha();

    if (detalle === null) {
      return;
    }

    this.closeFicha();
    this.openEdit(detalle);
  }

  openBajaFromFicha(): void {
    const detalle = this.ficha();

    if (detalle === null) {
      return;
    }

    this.closeFicha();
    this.openBaja(detalle);
  }

  openRenovacionFromFicha(): void {
    const detalle = this.ficha();

    if (detalle === null) {
      return;
    }

    this.closeFicha();
    this.openRenovacion(detalle);
  }

  openBaja(inscripcion: InscripcionDetalle): void {
    this.bajaInscripcion.set(inscripcion);
    this.bajaError.set(null);
    this.bajaFieldErrors.set({});
    this.turnosFuturos.set(null);
    this.turnosFuturosError.set(null);
    this.bajaForm.reset({
      motivo: 'retiro_paciente',
      motivo_detalle: '',
      fecha_baja: this.today(),
    });
    this.bajaOpen.set(true);

    if (!inscripcion.can.turnos_futuros_disponible) {
      this.turnosFuturosError.set(this.texts.noFutureTurns);
      return;
    }

    this.service
      .getTurnosFuturos(inscripcion.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.turnosFuturos.set(response.data),
        error: () => this.turnosFuturosError.set(this.texts.noFutureTurns),
      });
  }

  closeBaja(): void {
    this.bajaOpen.set(false);
    this.bajaInscripcion.set(null);
    this.bajaError.set(null);
    this.bajaFieldErrors.set({});
  }

  confirmBaja(): void {
    const inscripcion = this.bajaInscripcion();

    if (inscripcion === null) {
      return;
    }

    this.bajaForm.markAllAsTouched();
    this.bajaError.set(null);
    this.bajaFieldErrors.set({});

    if (this.bajaForm.invalid) {
      return;
    }

    if (this.motivoOtro() && this.bajaForm.controls.motivo_detalle.value.trim() === '') {
      this.bajaError.set(this.texts.retiroOtherRequired);
      return;
    }

    const raw = this.bajaForm.getRawValue();
    const payload: BajaPayload = {
      motivo: raw.motivo,
      motivo_detalle: raw.motivo_detalle.trim() || null,
      fecha_baja: raw.fecha_baja,
    };

    this.bajaSaving.set(true);
    this.service
      .withdraw(inscripcion.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.bajaSaving.set(false);
          this.bajaOpen.set(false);
          this.toast.success('Inscripción retirada', 'La baja quedó registrada en bitácora.');
          this.load();
          this.openFicha(inscripcion.id);
        },
        error: (error: InscripcionApiError) => {
          this.bajaSaving.set(false);
          this.bajaError.set(error.message);
          this.bajaFieldErrors.set(error.errors ?? {});
        },
      });
  }

  openRenovacion(inscripcion: InscripcionDetalle): void {
    this.renovacionInscripcion.set(inscripcion);
    this.renovacionPreview.set(null);
    this.renovacionTariff.set(null);
    this.renovacionTariffChanged.set(null);
    this.renovacionConfirmTariff.set(false);
    this.renovacionError.set(null);
    this.renovacionFieldErrors.set({});
    this.renovacionOpen.set(true);
    this.renovacionLoading.set(true);

    this.service
      .getRenovacion(inscripcion.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.renovacionLoading.set(false);
          this.renovacionPreview.set(response.data);
          this.renovacionTariff.set(response.data.tarifa);
          this.renovacionForm.reset({
            fecha_inicio: response.data.fecha_inicio,
            fecha_fin: response.data.fecha_fin,
            servicio_id: String(response.data.servicio.id),
            categoria_tarifa_id: String(response.data.categoria.id),
            observaciones: '',
          });
        },
        error: (error: InscripcionApiError) => {
          this.renovacionLoading.set(false);
          this.renovacionError.set(error.message);
        },
      });
  }

  closeRenovacion(): void {
    this.renovacionOpen.set(false);
    this.renovacionInscripcion.set(null);
    this.renovacionPreview.set(null);
    this.renovacionError.set(null);
  }

  requestRenovacionPreview(): void {
    const raw = this.renovacionForm.getRawValue();
    const detalle = this.renovacionInscripcion();

    if (detalle === null || !raw.servicio_id || !raw.categoria_tarifa_id || !raw.fecha_inicio) {
      this.renovacionTariff.set(null);
      this.renovacionTariffChanged.set(null);
      this.renovacionTariffError.set(null);
      return;
    }

    this.renovacionTariffLoading.set(true);
    this.renovacionTariffError.set(null);

    this.service
      .previewMonto({
        servicio_id: Number(raw.servicio_id),
        sucursal_id: detalle.sucursal.id,
        categoria_tarifa_id: Number(raw.categoria_tarifa_id),
        fecha_inicio: raw.fecha_inicio,
        tarifa_id_esperada: this.renovacionTariff()?.tarifa_id ?? null,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.renovacionTariffLoading.set(false);
          this.renovacionTariff.set(response.data);

          if (response.data === null) {
            this.renovacionTariffError.set(response.message ?? this.texts.noTariff);
            this.renovacionTariffChanged.set(null);
            return;
          }

          const originTariff = this.renovacionPreview()?.tarifa;

          if (
            originTariff !== null &&
            originTariff !== undefined &&
            originTariff.tarifa_id !== response.data.tarifa_id
          ) {
            this.renovacionTariffChanged.set(response.data);
            this.renovacionConfirmTariff.set(false);
          } else {
            this.renovacionTariffChanged.set(null);
          }
        },
        error: (error: InscripcionApiError) => {
          this.renovacionTariffLoading.set(false);
          this.renovacionTariff.set(null);
          this.renovacionTariffError.set(error.message);
        },
      });
  }

  confirmRenovacion(): void {
    const inscripcion = this.renovacionInscripcion();
    const preview = this.renovacionPreview();

    if (inscripcion === null || preview === null) {
      return;
    }

    this.renovacionForm.markAllAsTouched();
    this.renovacionError.set(null);
    this.renovacionFieldErrors.set({});

    if (this.renovacionForm.invalid) {
      return;
    }

    if (this.renovacionTariffChanged() !== null && !this.renovacionConfirmTariff()) {
      this.renovacionError.set(this.texts.tariffChangedDetail);
      return;
    }

    const raw = this.renovacionForm.getRawValue();
    const payload: RenovacionPayload = {
      fecha_inicio: raw.fecha_inicio,
      fecha_fin: raw.fecha_fin,
      servicio_id: Number(raw.servicio_id),
      categoria_tarifa_id: Number(raw.categoria_tarifa_id),
      observaciones: raw.observaciones || null,
      tarifa_id_esperada: preview.tarifa?.tarifa_id ?? null,
      confirmar_tarifa_cambio: this.renovacionConfirmTariff(),
    };

    this.renovacionSaving.set(true);
    this.service
      .renew(inscripcion.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.renovacionSaving.set(false);
          this.renovacionOpen.set(false);
          this.toast.success(
            'Inscripción renovada',
            'La nueva inscripción quedó vinculada a la anterior.',
          );
          this.load();
          this.openFicha(response.data.id);
        },
        error: (error: InscripcionApiError) => {
          this.renovacionSaving.set(false);
          this.renovacionError.set(error.message);
          this.renovacionFieldErrors.set(error.errors ?? {});

          if (error.code === 'TARIFA_CAMBIO' && error.tarifa_aplicable) {
            this.renovacionTariff.set(error.tarifa_aplicable);
            this.renovacionTariffChanged.set(error.tarifa_aplicable);
          }
        },
      });
  }

  fieldError(errors: Record<string, string[]>, field: string): string | null {
    return errors[field]?.[0] ?? null;
  }

  motivoLabel(code: string | null): string {
    if (!code) {
      return '—';
    }

    return MOTIVO_BAJA_OPTIONS.find((motivo) => motivo.value === code)?.label ?? code;
  }

  estadoColor(estado: string): 'success' | 'warning' | 'error' | 'gray' | 'info' {
    return matchEstado(estado);
  }

  whatsappUrl(telefono: string | null | undefined): string | null {
    if (!telefono) {
      return null;
    }

    const digits = telefono.replace(/\D+/g, '');
    if (!digits) {
      return null;
    }

    const international = digits.startsWith('591') ? digits : `591${digits}`;
    return `https://wa.me/${international}`;
  }

  private handleFormError(error: InscripcionApiError): void {
    this.formSaving.set(false);
    this.formError.set(error.message);
    this.formFieldErrors.set(error.errors ?? {});

    if (error.code === 'TARIFA_CAMBIO' && error.tarifa_aplicable) {
      this.formTariff.set(error.tarifa_aplicable);
      this.formTariffChanged.set(error.tarifa_aplicable);
      this.formConfirmTariff.set(false);
    }
  }

  private loadAllSalas() {
    return this.salasService.listSalas({ estado: 'activa', page: 1, per_page: 50 }).pipe(
      switchMap((first) => {
        if (first.meta.last_page <= 1) {
          return of(first.data);
        }

        const requests = [];

        for (let page = 2; page <= first.meta.last_page; page++) {
          requests.push(this.salasService.listSalas({ estado: 'activa', page, per_page: 50 }));
        }

        return forkJoin(requests).pipe(
          switchMap((pages) => of([...first.data, ...pages.flatMap((page) => page.data)])),
        );
      }),
    );
  }

  private loadAllServicios() {
    return this.catalogoService.listServicios({ estado: 'activo', page: 1, per_page: 50 }).pipe(
      switchMap((first) => {
        const all =
          first.meta.last_page <= 1
            ? of([...first.data])
            : forkJoin(
                Array.from({ length: first.meta.last_page - 1 }, (_, index) =>
                  this.catalogoService.listServicios({
                    estado: 'activo',
                    page: index + 2,
                    per_page: 50,
                  }),
                ),
              ).pipe(
                switchMap((pages) => of([...first.data, ...pages.flatMap((page) => page.data)])),
              );

        return all.pipe(
          switchMap((servicios) =>
            of(servicios.filter((servicio) => servicio.tipo_servicio?.codigo === 'sala_grupal')),
          ),
        );
      }),
    );
  }

  private salaLabel(sala: Sala): string {
    const parts = [sala.nombre];

    if (sala.tipo_sala?.nombre) {
      parts.push(sala.tipo_sala.nombre);
    }

    if (sala.turno?.nombre) {
      parts.push(sala.turno.nombre);
    }

    return parts.join(' · ');
  }

  private today(): string {
    return new Intl.DateTimeFormat('en-CA', {
      timeZone: environment.timezone,
    }).format(new Date());
  }

  private defaultEnd(start: string): string {
    const [year, month, day] = start.split('-').map(Number);
    const startDate = new Date(year, month - 1, day, 12);
    const targetMonth = new Date(startDate.getFullYear(), startDate.getMonth() + 1, 1, 12);
    const lastDay = new Date(targetMonth.getFullYear(), targetMonth.getMonth() + 1, 0).getDate();
    targetMonth.setDate(Math.min(day, lastDay));
    targetMonth.setDate(targetMonth.getDate() - 1);

    const resultYear = targetMonth.getFullYear();
    const resultMonth = String(targetMonth.getMonth() + 1).padStart(2, '0');
    const resultDay = String(targetMonth.getDate()).padStart(2, '0');

    return `${resultYear}-${resultMonth}-${resultDay}`;
  }
}

function matchEstado(estado: string): 'success' | 'warning' | 'error' | 'gray' | 'info' {
  switch (estado) {
    case 'activa':
      return 'success';
    case 'vencida':
      return 'warning';
    case 'retirada':
      return 'gray';
    case 'en_pausa':
      return 'info';
    case 'renovada':
      return 'info';
    default:
      return 'gray';
  }
}
