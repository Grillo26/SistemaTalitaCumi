import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { forkJoin, of, switchMap } from 'rxjs';
import { environment } from '../../../../environments/environment';
import { ToastService } from '../../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../../shared/components/ui/modal/modal.component';
import { PaginationComponent } from '../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { TABLE_IMPORTS } from '../../../shared/components/ui/table';
import { TextareaDirective } from '../../../shared/components/ui/textarea/textarea.directive';
import { Branch } from '../../organizacion/sucursales/sucursales.models';
import { SucursalesService } from '../../organizacion/sucursales/sucursales.service';
import { CatalogoServiciosService } from '../../catalogo-servicios/servicios/servicios.service';
import { Servicio } from '../../catalogo-servicios/servicios/servicios.models';
import { CategoriaTarifa } from '../../catalogo-servicios/tarifas/tarifas.models';
import { TarifasService } from '../../catalogo-servicios/tarifas/tarifas.service';
import { Profesional } from '../../profesionales/profesionales.models';
import { ProfesionalesService } from '../../profesionales/profesionales.service';
import { Paciente } from '../../pacientes/pacientes.models';
import { PacientesService } from '../../pacientes/pacientes.service';
import { InscripcionDateInputComponent } from '../salas-grupales/inscripcion-date-input.component';
import { InscripcionPacienteSearchComponent } from '../salas-grupales/inscripcion-paciente-search.component';
import { PaqueteComprobanteComponent } from './paquete-comprobante.component';
import {
  BajaPaquetePayload,
  ComprobantePaqueteData,
  ConsumoPaquete,
  DIAS_SEMANA_OPTIONS,
  EMPTY_ABILITIES,
  ESTADO_PAQUETE_OPTIONS,
  FichaTab,
  MOTIVO_BAJA_PAQUETE_OPTIONS,
  MontoPreview,
  PagoEstado,
  PAQUETES_TEXTS,
  PaqueteAbilities,
  PaqueteApiError,
  PaqueteDetalle,
  PaqueteListParams,
  PaquetePayload,
  PaqueteRow,
  PaqueteSort,
  PaqueteUpdatePayload,
  RenovacionPayload,
  RenovacionPreview,
  TarifaAplicable,
  TurnosFuturosResumen,
  formatBs,
  formatDate,
  weekdaysLabel,
} from './paquetes-sesiones.models';
import { PaquetesSesionesService } from './paquetes-sesiones.service';

type FormMode = 'create' | 'edit';

@Component({
  selector: 'app-paquetes-sesiones-page',
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
    InputDirective,
    TextareaDirective,
    InscripcionDateInputComponent,
    InscripcionPacienteSearchComponent,
    PaqueteComprobanteComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './paquetes-sesiones.page.html',
})
export class PaquetesSesionesPageComponent implements OnInit {
  private readonly service = inject(PaquetesSesionesService);
  private readonly sucursalesService = inject(SucursalesService);
  private readonly catalogoService = inject(CatalogoServiciosService);
  private readonly tarifasService = inject(TarifasService);
  private readonly profesionalesService = inject(ProfesionalesService);
  private readonly pacientesService = inject(PacientesService);
  private readonly toast = inject(ToastService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = PAQUETES_TEXTS;
  readonly todayDate = this.today();
  readonly motivoOptions = MOTIVO_BAJA_PAQUETE_OPTIONS;
  readonly estadoOptions = ESTADO_PAQUETE_OPTIONS;
  readonly diasOptions = DIAS_SEMANA_OPTIONS;
  readonly fichaTabs: FichaTab[] = ['resumen', 'consumo', 'pago', 'comprobante'];
  readonly formatBs = formatBs;
  readonly formatDate = formatDate;
  readonly weekdaysLabel = weekdaysLabel;

  readonly rows = signal<PaqueteRow[]>([]);
  readonly abilities = signal<PaqueteAbilities>(EMPTY_ABILITIES);
  readonly loading = signal(false);
  readonly listError = signal<string | null>(null);
  readonly page = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly from = signal<number | null>(null);
  readonly to = signal<number | null>(null);
  readonly sort = signal<PaqueteSort>('fecha_inicio');
  readonly direction = signal<'asc' | 'desc'>('desc');

  readonly branches = signal<Branch[]>([]);
  readonly servicios = signal<Servicio[]>([]);
  readonly categorias = signal<CategoriaTarifa[]>([]);
  readonly profesionales = signal<Profesional[]>([]);

  readonly filterPaciente = signal<Paciente | null>(null);
  readonly filters = this.fb.nonNullable.group({
    sucursal_id: [''],
    servicio_id: [''],
    profesional_id: [''],
    estado: [''],
    periodo_desde: [''],
    periodo_hasta: [''],
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
  readonly formDias = signal<number[]>([]);
  readonly formTariff = signal<MontoPreview | null>(null);
  readonly formTariffLoading = signal(false);
  readonly formTariffError = signal<string | null>(null);
  readonly formTariffChanged = signal<TarifaAplicable | null>(null);
  readonly formConfirmTariff = signal(false);
  readonly editingId = signal<number | null>(null);
  readonly editingPackage = signal<PaqueteDetalle | null>(null);
  readonly defaults = signal({ cantidad_sesiones_default: 10, duracion_minutos_default: 50 });

  readonly form = this.fb.nonNullable.group({
    tutor_responsable_id: ['', [Validators.required]],
    sucursal_id: ['', [Validators.required]],
    servicio_id: ['', [Validators.required]],
    profesional_id: [''],
    categoria_tarifa_id: ['', [Validators.required]],
    cantidad_sesiones: [10, [Validators.required, Validators.min(1)]],
    hora: ['08:00', [Validators.required]],
    duracion_minutos: [50, [Validators.required, Validators.min(1)]],
    fecha_inicio: ['', [Validators.required]],
    fecha_fin: ['', [Validators.required]],
    observaciones: [''],
  });

  readonly fichaOpen = signal(false);
  readonly fichaLoading = signal(false);
  readonly fichaError = signal<string | null>(null);
  readonly ficha = signal<PaqueteDetalle | null>(null);
  readonly fichaTab = signal<FichaTab>('resumen');
  readonly consumo = signal<ConsumoPaquete | null>(null);
  readonly consumoError = signal<string | null>(null);
  readonly consumoLoading = signal(false);
  readonly pago = signal<PagoEstado | null>(null);
  readonly pagoError = signal<string | null>(null);
  readonly pagoLoading = signal(false);
  readonly comprobante = signal<ComprobantePaqueteData | null>(null);
  readonly comprobanteError = signal<string | null>(null);
  readonly comprobanteLoading = signal(false);

  readonly bajaOpen = signal(false);
  readonly bajaSaving = signal(false);
  readonly bajaError = signal<string | null>(null);
  readonly bajaFieldErrors = signal<Record<string, string[]>>({});
  readonly bajaPaquete = signal<PaqueteDetalle | null>(null);
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
  readonly renovacionPaquete = signal<PaqueteDetalle | null>(null);
  readonly renovacionPreview = signal<RenovacionPreview | null>(null);
  readonly renovacionDias = signal<number[]>([]);
  readonly renovacionTariffChanged = signal<TarifaAplicable | null>(null);
  readonly renovacionConfirmTariff = signal(false);
  readonly renovacionForm = this.fb.nonNullable.group({
    fecha_inicio: ['', [Validators.required]],
    fecha_fin: ['', [Validators.required]],
    cantidad_sesiones: [10, [Validators.required, Validators.min(1)]],
    hora: ['08:00', [Validators.required]],
    duracion_minutos: [50, [Validators.required, Validators.min(1)]],
    profesional_id: [''],
    observaciones: [''],
  });

  readonly branchOptions = computed(() => [
    { value: '', label: this.texts.allBranches },
    ...this.branches().map((branch) => ({ value: String(branch.id), label: branch.nombre })),
  ]);

  readonly serviceOptions = computed(() => [
    { value: '', label: this.texts.allServices },
    ...this.servicios().map((servicio) => ({
      value: String(servicio.id),
      label: servicio.nombre,
    })),
  ]);

  readonly filterProfessionalOptions = computed(() => [
    { value: '', label: this.texts.allProfessionals },
    ...this.professionalsForBranch(this.filters.controls.sucursal_id.value).map((profesional) => ({
      value: String(profesional.id),
      label: profesional.nombre_completo,
    })),
  ]);

  readonly formProfessionalOptions = computed(() => [
    { value: '', label: 'Sin profesional asignado' },
    ...this.professionalsForBranch(this.form.controls.sucursal_id.value).map((profesional) => ({
      value: String(profesional.id),
      label: profesional.nombre_completo,
    })),
  ]);

  readonly renovacionProfessionalOptions = computed(() => [
    { value: '', label: 'Sin profesional asignado' },
    ...this.professionalsForBranch(String(this.renovacionPaquete()?.sucursal.id ?? '')).map(
      (profesional) => ({
        value: String(profesional.id),
        label: profesional.nombre_completo,
      }),
    ),
  ]);

  readonly categoriaOptions = computed(() => [
    { value: '', label: this.texts.selectCategory },
    ...this.categorias().map((categoria) => ({
      value: String(categoria.id),
      label: categoria.nombre,
    })),
  ]);

  readonly tutorOptions = computed(() =>
    this.formTutores().map((tutor) => ({
      value: String(tutor.id),
      label: `${tutor.nombre_completo}${tutor.activo === false ? ' (inactivo)' : ''}`,
    })),
  );

  readonly selectedPatientIsActive = computed(() => this.formPaciente()?.estado === 'activo');
  readonly motivoOtro = computed(() => this.bajaForm.controls.motivo.value === 'otro');

  ngOnInit(): void {
    this.form.controls.fecha_inicio.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.requestPreview());

    this.form.controls.fecha_fin.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.requestPreview());

    this.form.controls.cantidad_sesiones.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.requestPreview());

    this.loadOptions();
    this.load();
  }

  loadOptions(): void {
    this.service
      .opciones()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.defaults.set(response.data.defaults);
          this.form.controls.cantidad_sesiones.setValue(
            response.data.defaults.cantidad_sesiones_default,
            { emitEvent: false },
          );
          this.form.controls.duracion_minutos.setValue(
            response.data.defaults.duracion_minutos_default,
            { emitEvent: false },
          );
        },
        error: () => undefined,
      });

    this.sucursalesService
      .list({ estado: 'activa', page: 1, per_page: 50, sort: 'nombre', direction: 'asc' })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.branches.set(response.data),
        error: () => this.branches.set([]),
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

    this.loadAllProfesionales()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (profesionales) => this.profesionales.set(profesionales),
        error: () => this.profesionales.set([]),
      });
  }

  load(page = this.page()): void {
    this.loading.set(true);
    this.listError.set(null);
    this.page.set(page);

    const raw = this.filters.getRawValue();
    const params: PaqueteListParams = {
      paciente_id: this.filterPaciente()?.id ?? '',
      sucursal_id: raw.sucursal_id ? Number(raw.sucursal_id) : '',
      servicio_id: raw.servicio_id ? Number(raw.servicio_id) : '',
      profesional_id: raw.profesional_id ? Number(raw.profesional_id) : '',
      periodo_desde: raw.periodo_desde,
      periodo_hasta: raw.periodo_hasta,
      estado: raw.estado as PaqueteListParams['estado'],
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
        error: (error: PaqueteApiError) => {
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
      servicio_id: '',
      profesional_id: '',
      estado: '',
      periodo_desde: '',
      periodo_hasta: '',
    });
    this.load(1);
  }

  onFilterPaciente(paciente: Paciente | null): void {
    this.filterPaciente.set(paciente);
    this.load(1);
  }

  onFilterBranchChange(value: string): void {
    this.filters.controls.sucursal_id.setValue(value);
    this.filters.controls.profesional_id.setValue('');
  }

  goToPage(page: number): void {
    if (page < 1 || page > this.lastPage()) {
      return;
    }

    this.load(page);
  }

  setSort(sort: PaqueteSort): void {
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
    this.editingPackage.set(null);
    this.formPaciente.set(null);
    this.editingPatient.set(null);
    this.formTutores.set([]);
    this.formTutoresError.set(null);
    this.formDias.set([1, 3]);
    this.formTariff.set(null);
    this.formTariffChanged.set(null);
    this.formConfirmTariff.set(false);
    this.formTariffError.set(null);
    const start = this.today();

    this.form.reset({
      tutor_responsable_id: '',
      sucursal_id: '',
      servicio_id: '',
      profesional_id: '',
      categoria_tarifa_id: '',
      cantidad_sesiones: this.defaults().cantidad_sesiones_default,
      hora: '08:00',
      duracion_minutos: this.defaults().duracion_minutos_default,
      fecha_inicio: start,
      fecha_fin: this.defaultEnd(start),
      observaciones: '',
    });
    this.formError.set(null);
    this.formFieldErrors.set({});
    this.formOpen.set(true);
  }

  openEdit(paquete: PaqueteDetalle): void {
    this.formMode.set('edit');
    this.editingId.set(paquete.id);
    this.editingPackage.set(paquete);
    this.formPaciente.set(null);
    this.editingPatient.set({
      id: paquete.paciente.id,
      nombre_completo: paquete.paciente.nombre_completo,
      estado: paquete.paciente.estado,
      estado_label: paquete.paciente.estado_label,
    });
    this.formTutores.set([
      { id: paquete.tutor.id, nombre_completo: paquete.tutor.nombre_completo, activo: true },
    ]);
    this.formTutoresError.set(null);
    this.formDias.set([...paquete.dias_semana]);
    this.formTariff.set(null);
    this.formTariffChanged.set(null);
    this.formConfirmTariff.set(false);
    this.form.reset({
      tutor_responsable_id: String(paquete.tutor.id),
      sucursal_id: String(paquete.sucursal.id),
      servicio_id: String(paquete.servicio.id),
      profesional_id: paquete.profesional ? String(paquete.profesional.id) : '',
      categoria_tarifa_id: String(paquete.categoria.id),
      cantidad_sesiones: paquete.cantidad_sesiones,
      hora: paquete.hora,
      duracion_minutos: paquete.duracion_minutos,
      fecha_inicio: paquete.fecha_inicio,
      fecha_fin: paquete.fecha_fin,
      observaciones: paquete.observaciones ?? '',
    });
    this.formError.set(null);
    this.formFieldErrors.set({});
    this.formOpen.set(true);
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
              'El paciente no tiene tutores disponibles. Registrá uno antes de crear el paquete.',
            );
            return;
          }

          if (!tutores.some((tutor) => tutor.activo)) {
            this.formTutoresError.set(
              'El paciente no tiene tutores activos. Reactivá o registrá un tutor antes de crear el paquete.',
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
    this.form.controls.profesional_id.setValue('');
    this.resetTariffChange();
    this.requestPreview();
  }

  onFormServiceChange(value: string): void {
    this.form.controls.servicio_id.setValue(value);
    this.resetTariffChange();
    this.requestPreview();
  }

  onFormCategoryChange(value: string): void {
    this.form.controls.categoria_tarifa_id.setValue(value);
    this.resetTariffChange();
    this.requestPreview();
  }

  private resetTariffChange(): void {
    this.formTariffChanged.set(null);
    this.formConfirmTariff.set(false);
  }

  toggleDia(dia: number): void {
    const current = this.formDias();

    this.formDias.set(
      current.includes(dia)
        ? current.filter((value) => value !== dia)
        : [...current, dia].sort((a, b) => a - b),
    );
    this.requestPreview();
  }

  requestPreview(): void {
    if (this.formMode() !== 'create' || !this.formOpen()) {
      return;
    }

    const raw = this.form.getRawValue();

    if (!raw.servicio_id || !raw.sucursal_id || !raw.categoria_tarifa_id || !raw.fecha_inicio) {
      this.formTariff.set(null);
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
        cantidad_sesiones: Number(raw.cantidad_sesiones),
        dias_semana: this.formDias(),
        fecha_fin: raw.fecha_fin || undefined,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.formTariffLoading.set(false);
          this.formTariff.set(response.data);

          if (!response.data.tarifa_disponible) {
            this.formTariffError.set(response.data.message ?? this.texts.noTariff);
          }
        },
        error: (error: PaqueteApiError) => {
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

    if (this.formDias().length === 0) {
      this.formError.set(this.texts.selectDays);
      return;
    }

    if (this.formMode() === 'edit') {
      this.saveEdit();
      return;
    }

    if (this.formPaciente() === null) {
      this.formError.set(this.texts.selectPatient);
      return;
    }

    if (!this.selectedPatientIsActive()) {
      this.formError.set(this.texts.patientInactive);
      return;
    }

    if (this.formTariffChanged() !== null && !this.formConfirmTariff()) {
      this.formError.set(this.texts.tariffChangedDetail);
      return;
    }

    if (!this.formTariff()?.tarifa_disponible) {
      this.formError.set(this.formTariffError() ?? this.texts.noTariff);
      return;
    }

    const raw = this.form.getRawValue();
    const paciente = this.formPaciente();

    if (paciente === null) {
      return;
    }

    const payload: PaquetePayload = {
      paciente_id: paciente.id,
      tutor_responsable_id: Number(raw.tutor_responsable_id),
      sucursal_id: Number(raw.sucursal_id),
      servicio_id: Number(raw.servicio_id),
      profesional_id: raw.profesional_id ? Number(raw.profesional_id) : null,
      categoria_tarifa_id: Number(raw.categoria_tarifa_id),
      cantidad_sesiones: Number(raw.cantidad_sesiones),
      dias_semana: this.formDias(),
      hora: raw.hora,
      duracion_minutos: Number(raw.duracion_minutos),
      fecha_inicio: raw.fecha_inicio,
      fecha_fin: raw.fecha_fin,
      observaciones: raw.observaciones || null,
      tarifa_id_esperada:
        this.formTariffChanged()?.tarifa_id ?? this.formTariff()?.tarifa?.tarifa_id ?? null,
      confirmar_tarifa_cambio: this.formConfirmTariff(),
    };

    this.formSaving.set(true);

    this.service
      .create(payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.formSaving.set(false);
          this.formOpen.set(false);
          this.toast.success('Paquete creado', 'El paquete quedó activo.');
          this.load();
          this.openFicha(response.data.id);
        },
        error: (error: PaqueteApiError) => this.handleFormError(error),
      });
  }

  private saveEdit(): void {
    const id = this.editingId();

    if (id === null) {
      return;
    }

    const raw = this.form.getRawValue();
    const payload: PaqueteUpdatePayload = {
      profesional_id: raw.profesional_id ? Number(raw.profesional_id) : null,
      dias_semana: this.formDias(),
      hora: raw.hora,
      observaciones: raw.observaciones || null,
    };

    this.formSaving.set(true);

    this.service
      .update(id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.formSaving.set(false);
          this.formOpen.set(false);
          this.toast.success(
            'Paquete actualizado',
            'Los cambios quedaron registrados en bitácora.',
          );
          this.load();
          this.openFicha(response.data.id);
        },
        error: (error: PaqueteApiError) => this.handleFormError(error),
      });
  }

  openFicha(id: number, tab: FichaTab = 'resumen'): void {
    this.fichaOpen.set(true);
    this.fichaLoading.set(true);
    this.fichaError.set(null);
    this.ficha.set(null);
    this.fichaTab.set(tab);
    this.consumo.set(null);
    this.consumoError.set(null);
    this.pago.set(null);
    this.pagoError.set(null);
    this.comprobante.set(null);
    this.comprobanteError.set(null);

    this.service
      .get(id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.fichaLoading.set(false);
          this.ficha.set(response.data);

          if (tab === 'consumo') {
            this.loadConsumo();
          } else if (tab === 'pago') {
            this.loadPago();
          } else if (tab === 'comprobante') {
            this.loadComprobante();
          }
        },
        error: (error: PaqueteApiError) => {
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

    if (tab === 'consumo' && this.consumo() === null && this.consumoError() === null) {
      this.loadConsumo();
    } else if (tab === 'pago' && this.pago() === null && this.pagoError() === null) {
      this.loadPago();
    } else if (tab === 'comprobante' && this.comprobante() === null) {
      this.loadComprobante();
    }
  }

  loadConsumo(): void {
    const paquete = this.ficha();

    if (paquete === null) {
      return;
    }

    if (!paquete.can.consumo_disponible) {
      this.consumoError.set(this.texts.noConsumo);
      return;
    }

    this.consumoLoading.set(true);
    this.consumoError.set(null);

    this.service
      .getConsumo(paquete.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.consumoLoading.set(false);
          this.consumo.set(response.data);
        },
        error: (error: PaqueteApiError) => {
          this.consumoLoading.set(false);
          this.consumoError.set(error.message);
        },
      });
  }

  loadPago(): void {
    const paquete = this.ficha();

    if (paquete === null) {
      return;
    }

    if (!paquete.can.pago_disponible) {
      this.pagoError.set(this.texts.noPayment);
      return;
    }

    this.pagoLoading.set(true);
    this.pagoError.set(null);

    this.service
      .getPago(paquete.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.pagoLoading.set(false);
          this.pago.set(response.data);
        },
        error: (error: PaqueteApiError) => {
          this.pagoLoading.set(false);
          this.pagoError.set(error.message);
        },
      });
  }

  loadComprobante(): void {
    const paquete = this.ficha();

    if (paquete === null) {
      return;
    }

    this.comprobanteLoading.set(true);
    this.comprobanteError.set(null);

    this.service
      .getComprobante(paquete.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.comprobanteLoading.set(false);
          this.comprobante.set(response.data);
        },
        error: (error: PaqueteApiError) => {
          this.comprobanteLoading.set(false);
          this.comprobanteError.set(error.message);
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

  private withDetail(id: number, action: (detalle: PaqueteDetalle) => void): void {
    this.service
      .get(id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => action(response.data),
        error: (error: PaqueteApiError) => this.toast.error(this.texts.requestError, error.message),
      });
  }

  openEditFromFicha(): void {
    const paquete = this.ficha();

    if (paquete) {
      this.openEdit(paquete);
    }
  }

  openBajaFromFicha(): void {
    const paquete = this.ficha();

    if (paquete) {
      this.openBaja(paquete);
    }
  }

  openRenovacionFromFicha(): void {
    const paquete = this.ficha();

    if (paquete) {
      this.openRenovacion(paquete);
    }
  }

  openBaja(paquete: PaqueteDetalle): void {
    this.bajaPaquete.set(paquete);
    this.bajaForm.reset({
      motivo: 'retiro_paciente',
      motivo_detalle: '',
      fecha_baja: this.today(),
    });
    this.bajaError.set(null);
    this.bajaFieldErrors.set({});
    this.turnosFuturos.set(null);
    this.turnosFuturosError.set(null);
    this.bajaOpen.set(true);

    if (paquete.can.turnos_futuros_disponible) {
      this.service
        .getTurnosFuturos(paquete.id)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: (response) => this.turnosFuturos.set(response.data),
          error: () => this.turnosFuturosError.set(this.texts.noFutureTurns),
        });
    } else {
      this.turnosFuturosError.set(this.texts.noFutureTurns);
    }
  }

  closeBaja(): void {
    this.bajaOpen.set(false);
    this.bajaError.set(null);
    this.bajaFieldErrors.set({});
  }

  confirmBaja(): void {
    const paquete = this.bajaPaquete();

    if (paquete === null) {
      return;
    }

    this.bajaForm.markAllAsTouched();
    this.bajaError.set(null);
    this.bajaFieldErrors.set({});

    if (this.bajaForm.invalid) {
      return;
    }

    const raw = this.bajaForm.getRawValue();

    if (raw.motivo === 'otro' && raw.motivo_detalle.trim() === '') {
      this.bajaError.set(this.texts.retiroOtherRequired);
      return;
    }

    const payload: BajaPaquetePayload = {
      motivo: raw.motivo,
      motivo_detalle: raw.motivo_detalle.trim() || null,
      fecha_baja: raw.fecha_baja,
    };

    this.bajaSaving.set(true);

    this.service
      .withdraw(paquete.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.bajaSaving.set(false);
          this.bajaOpen.set(false);
          this.toast.success('Paquete retirado', 'La baja quedó registrada en bitácora.');
          this.load();
          this.openFicha(response.data.id);
        },
        error: (error: PaqueteApiError) => {
          this.bajaSaving.set(false);
          this.bajaError.set(error.message);
          this.bajaFieldErrors.set(error.errors ?? {});
        },
      });
  }

  openRenovacion(paquete: PaqueteDetalle): void {
    this.renovacionPaquete.set(paquete);
    this.renovacionPreview.set(null);
    this.renovacionTariffChanged.set(null);
    this.renovacionConfirmTariff.set(false);
    this.renovacionError.set(null);
    this.renovacionFieldErrors.set({});
    this.renovacionDias.set([...paquete.dias_semana]);
    this.renovacionLoading.set(true);
    this.renovacionOpen.set(true);

    this.service
      .getRenovacion(paquete.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const preview = response.data;
          this.renovacionLoading.set(false);
          this.renovacionPreview.set(preview);
          this.renovacionDias.set([...preview.dias_semana]);
          this.renovacionForm.reset({
            fecha_inicio: preview.fecha_inicio,
            fecha_fin: preview.fecha_fin,
            cantidad_sesiones: preview.cantidad_sesiones,
            hora: preview.hora,
            duracion_minutos: preview.duracion_minutos,
            profesional_id: preview.profesional_id ? String(preview.profesional_id) : '',
            observaciones: '',
          });

          if (!preview.tarifa_disponible) {
            this.renovacionError.set(this.texts.noTariff);
          }
        },
        error: (error: PaqueteApiError) => {
          this.renovacionLoading.set(false);
          this.renovacionError.set(error.message);
        },
      });
  }

  closeRenovacion(): void {
    this.renovacionOpen.set(false);
    this.renovacionError.set(null);
    this.renovacionFieldErrors.set({});
  }

  toggleRenovacionDia(dia: number): void {
    const current = this.renovacionDias();

    this.renovacionDias.set(
      current.includes(dia)
        ? current.filter((value) => value !== dia)
        : [...current, dia].sort((a, b) => a - b),
    );
  }

  confirmRenovacion(): void {
    const paquete = this.renovacionPaquete();
    const preview = this.renovacionPreview();

    if (paquete === null || preview === null) {
      return;
    }

    this.renovacionForm.markAllAsTouched();
    this.renovacionError.set(null);
    this.renovacionFieldErrors.set({});

    if (this.renovacionForm.invalid) {
      return;
    }

    if (this.renovacionDias().length === 0) {
      this.renovacionError.set(this.texts.selectDays);
      return;
    }

    if (!preview.tarifa_disponible) {
      this.renovacionError.set(this.texts.noTariff);
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
      cantidad_sesiones: Number(raw.cantidad_sesiones),
      dias_semana: this.renovacionDias(),
      hora: raw.hora,
      duracion_minutos: Number(raw.duracion_minutos),
      profesional_id: raw.profesional_id ? Number(raw.profesional_id) : null,
      observaciones: raw.observaciones || null,
      tarifa_id_esperada:
        this.renovacionTariffChanged()?.tarifa_id ?? preview.tarifa?.tarifa_id ?? null,
      confirmar_tarifa_cambio: this.renovacionConfirmTariff(),
    };

    this.renovacionSaving.set(true);

    this.service
      .renew(paquete.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.renovacionSaving.set(false);
          this.renovacionOpen.set(false);
          this.toast.success(
            'Paquete renovado',
            'La renovación quedó vinculada al paquete de origen.',
          );
          this.load();
          this.openFicha(response.data.id);
        },
        error: (error: PaqueteApiError) => {
          this.renovacionSaving.set(false);
          this.renovacionError.set(error.message);
          this.renovacionFieldErrors.set(error.errors ?? {});

          if (error.code === 'TARIFA_CAMBIO' && error.tarifa_aplicable) {
            this.renovacionTariffChanged.set(error.tarifa_aplicable);
            this.renovacionConfirmTariff.set(false);
          }
        },
      });
  }

  exportar(): void {
    this.toast.info('Exportación', 'Generando archivo...');

    this.service.exportar(this.exportParams()).subscribe({
      next: (blob) => {
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `paquetes-sesiones-${this.today()}.xlsx`;
        link.click();
        window.URL.revokeObjectURL(url);
      },
      error: (error: PaqueteApiError) => {
        this.toast.error(
          this.texts.export,
          error.code === 'MODULO_NO_DISPONIBLE' ? this.texts.noExport : error.message,
        );
      },
    });
  }

  fieldError(errors: Record<string, string[]>, field: string): string | null {
    return errors[field]?.[0] ?? null;
  }

  motivoLabel(code: string | null | undefined): string {
    return this.motivoOptions.find((option) => option.value === code)?.label ?? '—';
  }

  estadoColor(estado: string): 'success' | 'warning' | 'error' | 'gray' | 'info' {
    switch (estado) {
      case 'activo':
        return 'success';
      case 'vencido':
        return 'warning';
      case 'retirado':
        return 'gray';
      case 'en_pausa':
      case 'renovado':
        return 'info';
      default:
        return 'gray';
    }
  }

  whatsappUrl(telefono: string | null | undefined): string | null {
    if (!telefono) {
      return null;
    }

    const digits = telefono.replace(/\D+/g, '');

    if (digits === '') {
      return null;
    }

    const international = digits.startsWith('591') ? digits : `591${digits}`;
    return `https://wa.me/${international}`;
  }

  private handleFormError(error: PaqueteApiError): void {
    this.formSaving.set(false);
    this.formError.set(error.code === 'PAQUETE_DUPLICADO' ? this.texts.duplicate : error.message);
    this.formFieldErrors.set(error.errors ?? {});

    if (error.code === 'TARIFA_CAMBIO' && error.tarifa_aplicable) {
      this.formTariffChanged.set(error.tarifa_aplicable);
      this.formConfirmTariff.set(false);
    }
  }

  private exportParams(): PaqueteListParams {
    const raw = this.filters.getRawValue();

    return {
      paciente_id: this.filterPaciente()?.id ?? '',
      sucursal_id: raw.sucursal_id ? Number(raw.sucursal_id) : '',
      servicio_id: raw.servicio_id ? Number(raw.servicio_id) : '',
      profesional_id: raw.profesional_id ? Number(raw.profesional_id) : '',
      periodo_desde: raw.periodo_desde,
      periodo_hasta: raw.periodo_hasta,
      estado: raw.estado as PaqueteListParams['estado'],
      sort: this.sort(),
      direction: this.direction(),
    };
  }

  private professionalsForBranch(sucursalId: string): Profesional[] {
    if (!sucursalId) {
      return this.profesionales();
    }

    const id = Number(sucursalId);

    return this.profesionales().filter((profesional) =>
      (profesional.sucursales ?? []).some(
        (sucursal) => sucursal.id === id && sucursal.activo_relacion !== false,
      ),
    );
  }

  private loadAllServicios() {
    return this.catalogoService.listServicios({ estado: 'activo', page: 1, per_page: 50 }).pipe(
      switchMap((first) => {
        if (first.meta.last_page <= 1) {
          return of(first.data);
        }

        return forkJoin(
          Array.from({ length: first.meta.last_page - 1 }, (_, index) =>
            this.catalogoService.listServicios({
              estado: 'activo',
              page: index + 2,
              per_page: 50,
            }),
          ),
        ).pipe(switchMap((pages) => of([...first.data, ...pages.flatMap((page) => page.data)])));
      }),
      switchMap((servicios) =>
        of(servicios.filter((servicio) => servicio.tipo_servicio?.codigo === 'terapia_individual')),
      ),
    );
  }

  private loadAllProfesionales() {
    return this.profesionalesService
      .listProfesionales({ estado: 'activo', page: 1, per_page: 50 })
      .pipe(
        switchMap((first) => {
          if (first.meta.last_page <= 1) {
            return of(first.data);
          }

          return forkJoin(
            Array.from({ length: first.meta.last_page - 1 }, (_, index) =>
              this.profesionalesService.listProfesionales({
                estado: 'activo',
                page: index + 2,
                per_page: 50,
              }),
            ),
          ).pipe(switchMap((pages) => of([...first.data, ...pages.flatMap((page) => page.data)])));
        }),
      );
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
