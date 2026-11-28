import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, FormControl, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { forkJoin, of, switchMap } from 'rxjs';
import { debounceTime, distinctUntilChanged, map } from 'rxjs/operators';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
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
import { TiposDiagnosticoService } from '../../catalogo-servicios/tipos-diagnostico/tipos-diagnostico.service';
import { Profesional } from '../../profesionales/profesionales.models';
import { ProfesionalesService } from '../../profesionales/profesionales.service';
import { Paciente } from '../../pacientes/pacientes.models';
import { PacientesService } from '../../pacientes/pacientes.service';
import { InscripcionDateInputComponent } from '../salas-grupales/inscripcion-date-input.component';
import { InscripcionPacienteSearchComponent } from '../salas-grupales/inscripcion-paciente-search.component';
import { AtencionComprobanteComponent } from './atencion-comprobante.component';
import {
  ATENCION_TEXTS,
  AtencionAbilities,
  AtencionApiError,
  BajaAtencionPayload,
  ComprobanteAtencionData,
  DiagnosticoDetalle,
  DiagnosticoListParams,
  DiagnosticoPayload,
  DiagnosticoRow,
  DiagnosticoUpdatePayload,
  EMPTY_ABILITIES,
  EntrevistaDetalle,
  EntrevistaListParams,
  EntrevistaPayload,
  EntrevistaPrevia,
  EntrevistaRow,
  EntrevistaUpdatePayload,
  ESTADO_OPTIONS,
  FichaTab,
  FormMode,
  MOTIVO_BAJA_OPTIONS,
  PagoEstado,
  PreviewMonto,
  ProfesionalResumen,
  RequisitoEspecialidad,
  TipoDiagnosticoResumen,
  formatBs,
  formatDate,
} from './entrevista-diagnostico.models';
import { EntrevistaDiagnosticoService } from './entrevista-diagnostico.service';

type AtencionTab = 'entrevistas' | 'diagnosticos';
type FormKind = 'entrevista' | 'diagnostico';
type AtencionKind = 'entrevista' | 'diagnostico';

@Component({
  selector: 'app-entrevista-diagnostico-page',
  imports: [
    ReactiveFormsModule,
    PageBreadcrumbComponent,
    ComponentCardComponent,
    AlertComponent,
    BadgeComponent,
    ButtonComponent,
    InputDirective,
    ModalComponent,
    PaginationComponent,
    SelectFieldComponent,
    TextareaDirective,
    InscripcionDateInputComponent,
    InscripcionPacienteSearchComponent,
    AtencionComprobanteComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './entrevista-diagnostico.page.html',
})
export class EntrevistaDiagnosticoPageComponent implements OnInit {
  private readonly service = inject(EntrevistaDiagnosticoService);
  private readonly sucursalesService = inject(SucursalesService);
  private readonly catalogoService = inject(CatalogoServiciosService);
  private readonly tarifasService = inject(TarifasService);
  private readonly tiposService = inject(TiposDiagnosticoService);
  private readonly profesionalesService = inject(ProfesionalesService);
  private readonly pacientesService = inject(PacientesService);
  private readonly toast = inject(ToastService);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = ATENCION_TEXTS;
  readonly estadoOptions = ESTADO_OPTIONS;
  readonly motivoOptions = MOTIVO_BAJA_OPTIONS;
  readonly formatBs = formatBs;
  readonly formatDate = formatDate;
  readonly puedeCoordinar = computed(() => this.auth.hasPermission('asignacion.ver'));
  readonly todayDate = this.today();

  private requirementsRequestSeq = 0;
  private previewRequestSeq = 0;

  readonly activeTab = signal<AtencionTab>('entrevistas');
  readonly abilities = signal<AtencionAbilities>(EMPTY_ABILITIES);
  readonly loading = signal(false);
  readonly error = signal<AtencionApiError | null>(null);

  readonly entrevistaRows = signal<EntrevistaRow[]>([]);
  readonly diagnosticoRows = signal<DiagnosticoRow[]>([]);
  readonly page = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly from = signal<number | null>(null);
  readonly to = signal<number | null>(null);
  readonly sort = signal<'fecha' | 'fecha_inicio' | 'monto_esperado' | 'estado'>('fecha');
  readonly direction = signal<'asc' | 'desc'>('desc');

  readonly branches = signal<Branch[]>([]);
  readonly categories = signal<CategoriaTarifa[]>([]);
  readonly interviewServices = signal<Servicio[]>([]);
  readonly diagnosticTypes = signal<TipoDiagnosticoResumen[]>([]);
  readonly professionals = signal<Profesional[]>([]);

  readonly search = new FormControl('', { nonNullable: true });
  readonly filters = this.fb.nonNullable.group({
    sucursal_id: [''],
    tipo_diagnostico_id: [''],
    profesional_id: [''],
    periodo_desde: [''],
    periodo_hasta: [''],
    estado: [''],
  });

  readonly branchOptions = computed(() => [
    { value: '', label: this.texts.allBranches },
    ...this.branches().map((branch) => ({ value: String(branch.id), label: branch.nombre })),
  ]);
  readonly categoryOptions = computed(() => [
    { value: '', label: 'Seleccioná una categoría' },
    ...this.categories().map((categoria) => ({
      value: String(categoria.id),
      label: categoria.nombre,
    })),
  ]);
  readonly interviewServiceOptions = computed(() => [
    { value: '', label: 'Seleccioná un servicio' },
    ...this.interviewServices().map((servicio) => ({
      value: String(servicio.id),
      label: servicio.nombre,
    })),
  ]);
  readonly diagnosticTypeOptions = computed(() => [
    { value: '', label: 'Seleccioná un tipo' },
    ...this.diagnosticTypes().map((tipo) => ({
      value: String(tipo.id),
      label: tipo.nombre,
    })),
  ]);
  readonly professionalFilterOptions = computed(() => [
    { value: '', label: this.texts.allProfessionals },
    ...this.professionals().map((profesional) => ({
      value: String(profesional.id),
      label: profesional.nombre_completo,
    })),
  ]);
  readonly typeFilterOptions = computed(() => [
    { value: '', label: this.texts.allTypes },
    ...this.diagnosticTypes().map((tipo) => ({
      value: String(tipo.id),
      label: tipo.nombre,
    })),
  ]);

  readonly previousInterviewOptions = computed(() => [
    { value: '', label: 'Seleccioná una entrevista previa' },
    ...this.previousInterviews().map((entrevista) => ({
      value: String(entrevista.id),
      label: `${this.formatDate(entrevista.fecha)} · ${entrevista.sucursal.nombre}`,
    })),
  ]);

  readonly tutorFormOptions = computed(() => [
    { value: '', label: 'Seleccioná un tutor' },
    ...this.formTutores()
      .filter((tutor) => tutor.activo !== false)
      .map((tutor) => ({
        value: String(tutor.id),
        label: tutor.nombre_completo,
      })),
  ]);

  readonly linkInterviewOptions = computed(() => [
    { value: '', label: 'Seleccioná una entrevista' },
    ...this.previousInterviewsForLink().map((entrevista) => ({
      value: String(entrevista.id),
      label: `${this.formatDate(entrevista.fecha)} · ${entrevista.sucursal.nombre}`,
    })),
  ]);

  readonly formOpen = signal(false);
  readonly formKind = signal<FormKind>('entrevista');
  readonly formMode = signal<FormMode>('create');
  readonly formSaving = signal(false);
  readonly formError = signal<string | null>(null);
  readonly formFieldErrors = signal<Record<string, string[]>>({});
  readonly editingId = signal<number | null>(null);
  readonly formPatient = signal<Paciente | null>(null);
  readonly formTutores = signal<{ id: number; nombre_completo: string; activo?: boolean }[]>([]);
  readonly formTutoresLoading = signal(false);
  readonly formTutoresError = signal<string | null>(null);
  readonly editingAmount = signal<string | null>(null);

  readonly requirements = signal<RequisitoEspecialidad[]>([]);
  readonly requirementsLoading = signal(false);
  readonly requirementsError = signal<string | null>(null);
  readonly diagnosisBranchId = signal('');
  readonly selectedProfessionals = signal<ReadonlySet<number>>(new Set());
  readonly omitInterview = signal(true);
  readonly previousInterviews = signal<EntrevistaPrevia[]>([]);
  readonly selectedPreviousInterview = signal<number | null>(null);

  readonly preview = signal<PreviewMonto | null>(null);
  readonly previewLoading = signal(false);
  readonly previewError = signal<string | null>(null);
  readonly tariffChanged = signal<PreviewMonto | null>(null);
  readonly confirmTariff = signal(false);
  readonly expectedTariffId = signal<number | null>(null);

  readonly interviewForm = this.fb.nonNullable.group({
    tutor_id: ['', [Validators.required]],
    sucursal_id: ['', [Validators.required]],
    servicio_id: ['', [Validators.required]],
    categoria_tarifa_id: ['', [Validators.required]],
    fecha: ['', [Validators.required]],
    notas_admin: [''],
  });

  readonly diagnosisForm = this.fb.nonNullable.group({
    sucursal_id: ['', [Validators.required]],
    tipo_diagnostico_id: ['', [Validators.required]],
    categoria_tarifa_id: ['', [Validators.required]],
    fecha_inicio: ['', [Validators.required]],
    fecha_fin: ['', [Validators.required]],
    motivo_omision_entrevista: [''],
    notas_admin: [''],
  });

  readonly detailOpen = signal(false);
  readonly detailKind = signal<AtencionKind>('entrevista');
  readonly detail = signal<EntrevistaDetalle | DiagnosticoDetalle | null>(null);
  readonly detailLoading = signal(false);
  readonly detailError = signal<string | null>(null);
  readonly detailTab = signal<FichaTab>('resumen');
  readonly pago = signal<PagoEstado | null>(null);
  readonly pagoLoading = signal(false);
  readonly pagoError = signal<string | null>(null);
  readonly comprobanteData = signal<ComprobanteAtencionData | null>(null);
  readonly comprobanteLoading = signal(false);
  readonly comprobanteError = signal<string | null>(null);

  readonly bajaOpen = signal(false);
  readonly bajaKind = signal<AtencionKind>('entrevista');
  readonly bajaId = signal<number | null>(null);
  readonly bajaSaving = signal(false);
  readonly bajaError = signal<string | null>(null);
  readonly bajaForm = this.fb.nonNullable.group({
    motivo: ['retiro_paciente', [Validators.required]],
    motivo_detalle: [''],
    fecha_baja: [this.today(), [Validators.required]],
  });
  readonly motivoBaja = signal('retiro_paciente');

  readonly linkOpen = signal(false);
  readonly linkDiagnosticoId = signal<number | null>(null);
  readonly linkSaving = signal(false);
  readonly linkError = signal<string | null>(null);
  readonly previousInterviewsForLink = signal<EntrevistaPrevia[]>([]);
  readonly selectedLinkInterview = signal<number | null>(null);

  readonly detailRows = computed(() => this.detail() as DiagnosticoDetalle | null);
  readonly detailEntrevista = computed(() =>
    this.detailKind() === 'entrevista' ? (this.detail() as EntrevistaDetalle | null) : null,
  );
  readonly detailDiagnostico = computed(() =>
    this.detailKind() === 'diagnostico' ? (this.detail() as DiagnosticoDetalle | null) : null,
  );
  readonly canCreate = computed(() => this.abilities().create);
  readonly isEditing = computed(() => this.formMode() === 'edit');
  readonly isDiagnosisForm = computed(() => this.formKind() === 'diagnostico');
  readonly motivoOtro = computed(() => this.motivoBaja() === 'otro');
  readonly missingProfessionals = computed(() => {
    if (this.selectedProfessionals().size === 0) {
      return [];
    }

    return this.requirements().filter((requisito) => {
      const selected = this.selectedProfessionals().size === 0
        ? 0
        : this.countProfessionalsFor(requisito.especialidad_id);

      return selected < requisito.cantidad_minima;
    });
  });
  readonly availableProfessionals = computed(() => {
    const branchId = this.diagnosisBranchId();

    if (branchId === '') {
      return [];
    }

    const required = new Set(this.requirements().map((item) => item.especialidad_id));

    return this.professionals().filter((profesional) => {
      const worksInBranch = (profesional.sucursales ?? []).some(
        (sucursal) => sucursal.id === Number(branchId) && sucursal.activo_relacion,
      );

      if (!profesional.activo || !worksInBranch) {
        return false;
      }

      if (required.size === 0) {
        return true;
      }

      return (profesional.especialidades ?? []).some(
        (especialidad) => especialidad.activo && required.has(especialidad.id),
      );
    });
  });

  ngOnInit(): void {
    this.search.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => {
        this.page.set(1);
        this.load();
      });

    this.interviewForm.controls.fecha.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.requestPreview());
    this.interviewForm.controls.servicio_id.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.requestPreview());
    this.interviewForm.controls.sucursal_id.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.requestPreview());
    this.interviewForm.controls.categoria_tarifa_id.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.requestPreview());
    this.diagnosisForm.controls.tipo_diagnostico_id.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.onDiagnosisTypeChange());
    this.diagnosisForm.controls.sucursal_id.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((branchId) => {
        this.diagnosisBranchId.set(branchId);

        if (this.formMode() === 'create') {
          this.selectedProfessionals.set(new Set());
        }

        this.requestPreview();
      });
    this.diagnosisForm.controls.categoria_tarifa_id.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.requestPreview());
    this.diagnosisForm.controls.fecha_inicio.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((fechaInicio) => {
        const fechaFin = this.diagnosisForm.controls.fecha_fin.value;

        if (fechaInicio !== '' && fechaFin !== '' && fechaFin < fechaInicio) {
          this.diagnosisForm.controls.fecha_fin.setValue(fechaInicio);
        }

        this.requestPreview();
      });
    this.bajaForm.controls.motivo.valueChanges
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe((motivo) => this.motivoBaja.set(motivo));

    this.loadOptions();
    this.load();
  }

  setTab(tab: AtencionTab): void {
    if (this.activeTab() === tab) {
      return;
    }

    this.activeTab.set(tab);
    this.page.set(1);
    this.sort.set(tab === 'entrevistas' ? 'fecha' : 'fecha_inicio');
    this.error.set(null);
    this.load();
  }

  load(page = this.page()): void {
    this.loading.set(true);
    this.error.set(null);
    this.page.set(page);

    if (!this.validateFilterDates()) {
      this.loading.set(false);
      return;
    }

    const raw = this.filters.getRawValue();
    const base: EntrevistaListParams = {
      search: this.search.value.trim() || undefined,
      sucursal_id: raw.sucursal_id ? Number(raw.sucursal_id) : '',
      periodo_desde: raw.periodo_desde || undefined,
      periodo_hasta: raw.periodo_hasta || undefined,
      estado: raw.estado ? (raw.estado as EntrevistaListParams['estado']) : '',
      page,
      per_page: 10,
      sort: this.activeTab() === 'entrevistas' ? (this.sort() as EntrevistaListParams['sort']) : undefined,
      direction: this.direction(),
    };

    if (this.activeTab() === 'entrevistas') {
      this.service
        .listEntrevistas(base)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: (response) => {
            this.entrevistaRows.set(response.data);
            this.abilities.set(response.can);
            this.applyMeta(response.meta);
            this.loading.set(false);
          },
          error: (error: AtencionApiError) => this.handleListError(error),
        });

      return;
    }

    const params: DiagnosticoListParams = {
      ...base,
      tipo_diagnostico_id: raw.tipo_diagnostico_id ? Number(raw.tipo_diagnostico_id) : '',
      profesional_id: raw.profesional_id ? Number(raw.profesional_id) : '',
      sort: this.sort() as DiagnosticoListParams['sort'],
    };

    this.service
      .listDiagnosticos(params)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.diagnosticoRows.set(response.data);
          this.abilities.set(response.can);
          this.applyMeta(response.meta);
          this.loading.set(false);
        },
        error: (error: AtencionApiError) => this.handleListError(error),
      });
  }

  applyFilters(): void {
    this.page.set(1);
    this.load(1);
  }

  clearFilters(): void {
    this.search.setValue('');
    this.filters.reset({
      sucursal_id: '',
      tipo_diagnostico_id: '',
      profesional_id: '',
      periodo_desde: '',
      periodo_hasta: '',
      estado: '',
    });
    this.load(1);
  }

  goToPage(page: number): void {
    if (page < 1 || page > this.lastPage()) {
      return;
    }

    this.load(page);
  }

  setSort(field: 'fecha' | 'fecha_inicio' | 'monto_esperado' | 'estado'): void {
    if (this.sort() === field) {
      this.direction.set(this.direction() === 'asc' ? 'desc' : 'asc');
    } else {
      this.sort.set(field);
      this.direction.set(field === 'fecha' || field === 'fecha_inicio' ? 'desc' : 'asc');
    }

    this.load(1);
  }

  sortIndicator(field: string): string {
    return this.sort() === field ? (this.direction() === 'asc' ? '↑' : '↓') : '';
  }

  ariaSort(field: string): 'ascending' | 'descending' | 'none' {
    return this.sort() === field
      ? this.direction() === 'asc'
        ? 'ascending'
        : 'descending'
      : 'none';
  }

  openCreateInterview(): void {
    this.formKind.set('entrevista');
    this.formMode.set('create');
    this.editingId.set(null);
    this.formPatient.set(null);
    this.formTutores.set([]);
    this.formTutoresLoading.set(false);
    this.formTutoresError.set(null);
    this.editingAmount.set(null);
    this.interviewForm.reset({
      tutor_id: '',
      sucursal_id: '',
      servicio_id: '',
      categoria_tarifa_id: '',
      fecha: this.today(),
      notas_admin: '',
    });
    this.resetPreview();
    this.clearFormErrors();
    this.formOpen.set(true);
  }

  openCreateDiagnosis(): void {
    this.formKind.set('diagnostico');
    this.formMode.set('create');
    this.editingId.set(null);
    this.formPatient.set(null);
    this.requirements.set([]);
    this.requirementsLoading.set(false);
    this.requirementsError.set(null);
    this.selectedProfessionals.set(new Set());
    this.omitInterview.set(true);
    this.editingAmount.set(null);
    this.previousInterviews.set([]);
    this.selectedPreviousInterview.set(null);
    this.diagnosisForm.reset({
      sucursal_id: '',
      tipo_diagnostico_id: '',
      categoria_tarifa_id: '',
      fecha_inicio: this.today(),
      fecha_fin: this.today(),
      motivo_omision_entrevista: '',
      notas_admin: '',
    });
    this.diagnosisBranchId.set('');
    this.resetPreview();
    this.clearFormErrors();
    this.formOpen.set(true);
  }

  openEditInterview(row: EntrevistaRow): void {
    this.detailOpen.set(false);

    this.service
      .getEntrevista(row.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const detalle = response.data;
          this.formKind.set('entrevista');
          this.formMode.set('edit');
          this.editingId.set(row.id);
          this.editingAmount.set(detalle.monto_esperado);
          this.formPatient.set({
            id: detalle.paciente.id,
            codigo: detalle.paciente.codigo,
            nombre: detalle.paciente.nombre_completo,
            apellido_paterno: '',
            apellido_materno: null,
            nombre_completo: detalle.paciente.nombre_completo,
            fecha_nacimiento: detalle.paciente.fecha_nacimiento ?? '',
            edad: { anios: detalle.paciente.edad_anios ?? 0, meses: 0, etiqueta: '' },
            sexo: 'otro',
            sexo_label: '',
            ci: null,
            tipo_diagnostico_id: null,
            sin_diagnostico_definido: false,
            sucursal_registro: { id: detalle.sucursal.id, nombre: detalle.sucursal.nombre },
            estado: (detalle.paciente.estado ?? 'activo') as Paciente['estado'],
            estado_label: detalle.paciente.estado_label ?? '',
            version: 0,
            created_at: null,
            updated_at: null,
            can: {} as Paciente['can'],
          } as unknown as Paciente);
          this.formTutores.set([
            { id: detalle.tutor.id, nombre_completo: detalle.tutor.nombre_completo, activo: true },
          ]);
          this.formTutoresLoading.set(false);
          this.formTutoresError.set(null);
          this.interviewForm.reset({
            tutor_id: String(detalle.tutor.id),
            sucursal_id: String(detalle.sucursal.id),
            servicio_id: String(detalle.servicio.id),
            categoria_tarifa_id: String(detalle.categoria.id),
            fecha: detalle.fecha,
            notas_admin: detalle.notas_admin ?? '',
          });
          this.resetPreview();
          this.clearFormErrors();
          this.formOpen.set(true);
        },
        error: (error: AtencionApiError) => this.toast.error('No se pudo abrir la atención', error.message),
      });
  }

  openEditDiagnosis(row: DiagnosticoRow): void {
    this.detailOpen.set(false);

    this.service
      .getDiagnostico(row.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const detalle = response.data;
          this.formKind.set('diagnostico');
          this.formMode.set('edit');
          this.editingId.set(row.id);
          this.editingAmount.set(detalle.monto_esperado);
          this.detailKind.set('diagnostico');
          this.detail.set(detalle);
          this.formPatient.set({
            id: detalle.paciente.id,
            codigo: detalle.paciente.codigo,
            nombre: detalle.paciente.nombre_completo,
            apellido_paterno: '',
            apellido_materno: null,
            nombre_completo: detalle.paciente.nombre_completo,
            fecha_nacimiento: detalle.paciente.fecha_nacimiento ?? '',
            edad: { anios: detalle.paciente.edad_anios ?? 0, meses: 0, etiqueta: '' },
            sexo: 'otro',
            sexo_label: '',
            ci: null,
            tipo_diagnostico_id: null,
            sin_diagnostico_definido: false,
            sucursal_registro: { id: detalle.sucursal.id, nombre: detalle.sucursal.nombre },
            estado: (detalle.paciente.estado ?? 'activo') as Paciente['estado'],
            estado_label: detalle.paciente.estado_label ?? '',
            version: 0,
            created_at: null,
            updated_at: null,
            can: {} as Paciente['can'],
          } as unknown as Paciente);
          this.requirements.set(detalle.requisitos);
          this.requirementsLoading.set(false);
          this.requirementsError.set(null);
          this.selectedProfessionals.set(new Set(detalle.profesionales.map((item) => item.id)));
          this.omitInterview.set(detalle.omitio_entrevista);
          this.selectedPreviousInterview.set(detalle.entrevista_previa_id);
          this.previousInterviews.set([]);
          this.diagnosisForm.reset({
            sucursal_id: String(detalle.sucursal.id),
            tipo_diagnostico_id: String(detalle.tipo_diagnostico.id),
            categoria_tarifa_id: String(detalle.categoria.id),
            fecha_inicio: detalle.fecha_inicio,
            fecha_fin: detalle.fecha_fin,
            motivo_omision_entrevista: detalle.motivo_omision_entrevista ?? '',
            notas_admin: detalle.notas_admin ?? '',
          });
          this.diagnosisBranchId.set(String(detalle.sucursal.id));
          this.resetPreview();
          this.clearFormErrors();
          this.formOpen.set(true);
        },
        error: (error: AtencionApiError) => this.toast.error('No se pudo abrir la atención', error.message),
      });
  }

  closeForm(): void {
    if (!this.formSaving()) {
      this.formOpen.set(false);
    }
  }

  onFormPatient(paciente: Paciente | null): void {
    this.formPatient.set(paciente);
    this.formTutores.set([]);
    this.formTutoresError.set(null);
    this.previousInterviews.set([]);
    this.selectedPreviousInterview.set(null);

    if (paciente === null) {
      return;
    }

    if (this.formKind() === 'entrevista') {
      this.loadTutores(paciente.id);
      return;
    }

    this.service
      .entrevistasPrevias(paciente.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.previousInterviews.set(response.data),
        error: () => this.previousInterviews.set([]),
      });
  }

  onDiagnosisTypeChange(): void {
    if (this.formMode() === 'edit') {
      this.requirementsRequestSeq++;
      return;
    }

    const tipoId = this.diagnosisForm.controls.tipo_diagnostico_id.value;

    if (tipoId === '') {
      this.requirementsRequestSeq++;
      this.requirementsLoading.set(false);
      this.requirements.set([]);
      this.requirementsError.set(null);
      this.selectedProfessionals.set(new Set());
      this.resetPreview();
      return;
    }

    const requestId = ++this.requirementsRequestSeq;

    this.requirementsLoading.set(true);
    this.requirementsError.set(null);

    this.service
      .requisitos(Number(tipoId))
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          if (requestId !== this.requirementsRequestSeq) {
            return;
          }

          this.requirementsLoading.set(false);
          this.requirements.set(response.data.especialidades);
          this.selectedProfessionals.set(new Set());
          const tipo = response.data.tipo;

          if (this.formMode() === 'create') {
            this.diagnosisForm.controls.fecha_fin.setValue(
              this.addDays(this.diagnosisForm.controls.fecha_inicio.value || this.today(), Math.max(0, tipo.dias_estimados - 1)),
            );
          }

          this.requestPreview();
        },
        error: (error: AtencionApiError) => {
          if (requestId !== this.requirementsRequestSeq) {
            return;
          }

          this.requirementsLoading.set(false);
          this.requirements.set([]);
          this.requirementsError.set(error.message);
          this.resetPreview();
        },
      });
  }

  toggleProfesional(profesionalId: number): void {
    const next = new Set(this.selectedProfessionals());

    if (next.has(profesionalId)) {
      next.delete(profesionalId);
    } else {
      next.add(profesionalId);
    }

    this.selectedProfessionals.set(next);
  }

  coordinarDiagnostico(id: number): void {
    this.router.navigate(['/agenda-turnos/diagnosticos', id]);
  }

  isProfesionalSelected(profesionalId: number): boolean {
    return this.selectedProfessionals().has(profesionalId);
  }

  countProfessionalsFor(especialidadId: number): number {
    let count = 0;

    for (const profesionalId of this.selectedProfessionals()) {
      const profesional = this.professionals().find((item) => item.id === profesionalId);

      if (profesional?.especialidades?.some((item) => item.id === especialidadId && item.activo)) {
        count++;
      }
    }

    return count;
  }

  profesionalName(profesionalId: number): string {
    return this.professionals().find((item) => item.id === profesionalId)?.nombre_completo ?? '';
  }

  specialtiesLabel(profesional: Profesional): string {
    return (profesional.especialidades ?? [])
      .filter((especialidad) => especialidad.activo)
      .map((especialidad) => especialidad.nombre)
      .join(' · ');
  }

  selectPreviousInterview(value: string): void {
    if (value === '') {
      this.selectedPreviousInterview.set(null);
      this.omitInterview.set(true);
      this.requestPreview();
      return;
    }

    this.selectedPreviousInterview.set(Number(value));
    this.omitInterview.set(false);
    this.requestPreview();
  }

  toggleOmitInterview(checked: boolean): void {
    this.omitInterview.set(checked);

    if (checked) {
      this.selectedPreviousInterview.set(null);
    }
  }

  saveInterview(): void {
    if (this.formSaving()) {
      return;
    }

    this.interviewForm.markAllAsTouched();

    if (this.formPatient() === null || this.interviewForm.invalid) {
      return;
    }

    const raw = this.interviewForm.getRawValue();
    const payload: EntrevistaPayload = {
      paciente_id: this.formPatient()!.id,
      tutor_id: Number(raw.tutor_id),
      sucursal_id: Number(raw.sucursal_id),
      servicio_id: Number(raw.servicio_id),
      categoria_tarifa_id: Number(raw.categoria_tarifa_id),
      fecha: raw.fecha,
      notas_admin: raw.notas_admin.trim() || null,
      tarifa_id_esperada: this.expectedTariffId(),
      confirmar_tarifa_cambio: this.confirmTariff(),
    };

    this.formSaving.set(true);
    this.clearFormErrors();

    const request = this.formMode() === 'create'
      ? this.service.createEntrevista(payload)
      : this.service.updateEntrevista(this.editingId()!, {
          fecha: raw.fecha,
          notas_admin: raw.notas_admin.trim() || null,
        });

    request.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.formSaving.set(false);
        this.formOpen.set(false);
        this.toast.success(
          this.formMode() === 'create' ? this.texts.interviewCreated : this.texts.updated,
          'Los datos se guardaron correctamente.',
        );
        this.load(1);
      },
      error: (error: AtencionApiError) => this.handleFormError(error),
    });
  }

  saveDiagnosis(): void {
    if (this.formSaving()) {
      return;
    }

    this.diagnosisForm.markAllAsTouched();

    if (this.formPatient() === null || this.diagnosisForm.invalid) {
      return;
    }

    if (this.selectedProfessionals().size > 0 && this.missingProfessionals().length > 0) {
      this.formError.set(this.texts.missingProfessionals);
      return;
    }

    const raw = this.diagnosisForm.getRawValue();
    const payload: DiagnosticoPayload = {
      paciente_id: this.formPatient()!.id,
      sucursal_id: Number(raw.sucursal_id),
      tipo_diagnostico_id: Number(raw.tipo_diagnostico_id),
      categoria_tarifa_id: Number(raw.categoria_tarifa_id),
      fecha_inicio: raw.fecha_inicio,
      fecha_fin: raw.fecha_fin,
      profesionales: [...this.selectedProfessionals()],
      omitio_entrevista: this.selectedPreviousInterview() === null ? this.omitInterview() : false,
      motivo_omision_entrevista: this.selectedPreviousInterview() === null
        ? (raw.motivo_omision_entrevista.trim() || null)
        : null,
      entrevista_previa_id: this.selectedPreviousInterview(),
      notas_admin: raw.notas_admin.trim() || null,
      tarifa_id_esperada: this.expectedTariffId(),
      confirmar_tarifa_cambio: this.confirmTariff(),
    };

    this.formSaving.set(true);
    this.clearFormErrors();

    const request = this.formMode() === 'create'
      ? this.service.createDiagnostico(payload)
      : this.service.updateDiagnostico(this.editingId()!, {
          fecha_inicio: raw.fecha_inicio,
          fecha_fin: raw.fecha_fin,
          profesionales: [...this.selectedProfessionals()],
          notas_admin: raw.notas_admin.trim() || null,
        });

    request.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.formSaving.set(false);
        this.formOpen.set(false);
        this.toast.success(
          this.formMode() === 'create' ? this.texts.diagnosisCreated : this.texts.updated,
          'Los datos se guardaron correctamente.',
        );
        this.load(1);
      },
      error: (error: AtencionApiError) => this.handleFormError(error),
    });
  }

  fieldError(field: string): string | null {
    const backend = this.formFieldErrors()[field]?.[0];

    if (backend) {
      return backend;
    }

    const control = this.formKind() === 'entrevista'
      ? this.interviewForm.get(field)
      : this.diagnosisForm.get(field);

    if (!control || !control.touched || control.valid) {
      return null;
    }

    if (control.hasError('required')) {
      return 'Este campo es obligatorio.';
    }

    if (control.hasError('maxlength')) {
      return 'El valor es demasiado largo.';
    }

    return 'Revisá este campo.';
  }

  openDetail(kind: AtencionKind, id: number): void {
    this.detailOpen.set(true);
    this.detailKind.set(kind);
    this.detailLoading.set(true);
    this.detailError.set(null);
    this.detail.set(null);
    this.detailTab.set('resumen');
    this.pago.set(null);
    this.pagoError.set(null);
    this.comprobanteData.set(null);
    this.comprobanteError.set(null);

    if (kind === 'entrevista') {
      this.service
        .getEntrevista(id)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: (response) => {
            this.detail.set(response.data);
            this.abilities.set(response.can);
            this.detailLoading.set(false);
          },
          error: (error: AtencionApiError) => {
            this.detailLoading.set(false);
            this.detailError.set(error.message);
          },
        });

      return;
    }

    this.service
      .getDiagnostico(id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.detail.set(response.data);
          this.abilities.set(response.can);
          this.detailLoading.set(false);
        },
        error: (error: AtencionApiError) => {
          this.detailLoading.set(false);
          this.detailError.set(error.message);
        },
      });
  }

  closeDetail(): void {
    this.detailOpen.set(false);
  }

  setDetailTab(tab: FichaTab): void {
    this.detailTab.set(tab);

    if (tab === 'pago') {
      this.loadPago();
    }

    if (tab === 'comprobante') {
      this.loadComprobante();
    }
  }

  loadPago(): void {
    if (!this.abilities().pago_disponible) {
      return;
    }

    const detail = this.detail();

    if (!detail || this.pagoLoading()) {
      return;
    }

    this.pagoLoading.set(true);
    this.pagoError.set(null);

    this.service
      .getPago(this.detailKind(), detail.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.pagoLoading.set(false);
          this.pago.set(response.data);
        },
        error: (error: AtencionApiError) => {
          this.pagoLoading.set(false);
          this.pagoError.set(error.message);
        },
      });
  }

  registrarPago(): void {
    const detail = this.detail();

    if (!detail) {
      return;
    }

    this.service
      .registrarPago(this.detailKind(), detail.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.toast.success('Pago registrado', 'La operación se completó correctamente.');
          this.loadPago();
        },
        error: (error: AtencionApiError) => this.pagoError.set(error.message),
      });
  }

  loadComprobante(): void {
    const detail = this.detail();

    if (!detail || this.comprobanteLoading()) {
      return;
    }

    this.comprobanteLoading.set(true);
    this.comprobanteError.set(null);

    this.service
      .getComprobante(this.detailKind(), detail.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.comprobanteLoading.set(false);
          this.comprobanteData.set(response.data);
        },
        error: (error: AtencionApiError) => {
          this.comprobanteLoading.set(false);
          this.comprobanteError.set(error.message);
        },
      });
  }

  downloadPdf(): void {
    const detail = this.detail();

    if (!detail) {
      return;
    }

    this.service
      .getComprobantePdf(this.detailKind(), detail.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (blob) => {
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = `comprobante-${this.detailKind()}-${detail.id}.pdf`;
          link.click();
          URL.revokeObjectURL(url);
        },
        error: (error: AtencionApiError) => this.comprobanteError.set(error.message),
      });
  }

  openBaja(kind: AtencionKind, id: number): void {
    this.bajaKind.set(kind);
    this.bajaId.set(id);
    this.bajaForm.reset({
      motivo: 'retiro_paciente',
      motivo_detalle: '',
      fecha_baja: this.today(),
    });
    this.motivoBaja.set('retiro_paciente');
    this.bajaError.set(null);
    this.bajaOpen.set(true);
  }

  closeBaja(): void {
    if (!this.bajaSaving()) {
      this.bajaOpen.set(false);
    }
  }

  confirmBaja(): void {
    if (this.bajaSaving() || this.bajaId() === null) {
      return;
    }

    this.bajaForm.markAllAsTouched();

    if (this.bajaForm.invalid) {
      return;
    }

    const raw = this.bajaForm.getRawValue();
    const payload: BajaAtencionPayload = {
      motivo: raw.motivo,
      motivo_detalle: raw.motivo_detalle.trim() || null,
      fecha_baja: raw.fecha_baja,
    };

    this.bajaSaving.set(true);
    this.bajaError.set(null);

    const onSuccess = (): void => {
      this.bajaSaving.set(false);
      this.bajaOpen.set(false);
      this.toast.success(this.texts.withdrawn, 'La baja quedó registrada en la bitácora.');
      this.load(1);

      if (this.detail()?.id === this.bajaId()) {
        this.openDetail(this.bajaKind(), this.bajaId()!);
      }
    };
    const onError = (error: AtencionApiError): void => {
      this.bajaSaving.set(false);
      this.bajaError.set(error.message);
    };

    if (this.bajaKind() === 'entrevista') {
      this.service
        .withdrawEntrevista(this.bajaId()!, payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({ next: onSuccess, error: onError });

      return;
    }

    this.service
      .withdrawDiagnostico(this.bajaId()!, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({ next: onSuccess, error: onError });
  }

  openLinkInterview(): void {
    const detail = this.detail();

    if (!detail || this.detailKind() !== 'diagnostico') {
      return;
    }

    this.linkDiagnosticoId.set(detail.id);
    this.linkError.set(null);
    this.selectedLinkInterview.set(null);
    this.linkOpen.set(true);

    this.service
      .entrevistasPrevias(detail.paciente.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.previousInterviewsForLink.set(response.data),
        error: (error: AtencionApiError) => this.linkError.set(error.message),
      });
  }

  closeLink(): void {
    if (!this.linkSaving()) {
      this.linkOpen.set(false);
    }
  }

  selectLinkInterview(value: string): void {
    this.selectedLinkInterview.set(value === '' ? null : Number(value));
  }

  confirmLink(): void {
    const diagnosticoId = this.linkDiagnosticoId();
    const entrevistaId = this.selectedLinkInterview();

    if (diagnosticoId === null || entrevistaId === null || this.linkSaving()) {
      return;
    }

    this.linkSaving.set(true);
    this.linkError.set(null);

    this.service
      .vincularEntrevista(diagnosticoId, entrevistaId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.linkSaving.set(false);
          this.linkOpen.set(false);
          this.toast.success(this.texts.linked, 'La entrevista se vinculó al diagnóstico.');
          this.openDetail('diagnostico', diagnosticoId);
          this.load(1);
        },
        error: (error: AtencionApiError) => {
          this.linkSaving.set(false);
          this.linkError.set(error.message);
        },
      });
  }

  statusColor(estado: string): 'success' | 'gray' {
    return estado === 'activa' ? 'success' : 'gray';
  }

  entityLabel(kind: AtencionKind): string {
    return kind === 'entrevista' ? 'Entrevista' : 'Diagnóstico';
  }

  requestPreview(): void {
    if (this.formMode() === 'edit') {
      return;
    }

    if (this.formKind() === 'entrevista') {
      this.requestInterviewPreview();
    } else {
      this.requestDiagnosisPreview();
    }
  }

  private requestInterviewPreview(): void {
    const raw = this.interviewForm.getRawValue();

    if (!raw.servicio_id || !raw.sucursal_id || !raw.categoria_tarifa_id || !raw.fecha) {
      this.resetPreview();
      return;
    }

    const requestId = ++this.previewRequestSeq;

    this.previewLoading.set(true);
    this.previewError.set(null);

    this.service
      .previewMontoEntrevista({
        servicio_id: Number(raw.servicio_id),
        sucursal_id: Number(raw.sucursal_id),
        categoria_tarifa_id: Number(raw.categoria_tarifa_id),
        fecha: raw.fecha,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          if (requestId === this.previewRequestSeq) {
            this.applyPreviewResponse(response.data, response.message);
          }
        },
        error: (error: AtencionApiError) => {
          if (requestId === this.previewRequestSeq) {
            this.handlePreviewError(error);
          }
        },
      });
  }

  private requestDiagnosisPreview(): void {
    const raw = this.diagnosisForm.getRawValue();

    if (!raw.tipo_diagnostico_id || !raw.sucursal_id || !raw.categoria_tarifa_id || !raw.fecha_inicio) {
      this.resetPreview();
      return;
    }

    const requestId = ++this.previewRequestSeq;

    this.previewLoading.set(true);
    this.previewError.set(null);

    this.service
      .previewMontoDiagnostico({
        tipo_diagnostico_id: Number(raw.tipo_diagnostico_id),
        sucursal_id: Number(raw.sucursal_id),
        categoria_tarifa_id: Number(raw.categoria_tarifa_id),
        fecha_inicio: raw.fecha_inicio,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          if (requestId === this.previewRequestSeq) {
            this.applyPreviewResponse(response.data, response.message);
          }
        },
        error: (error: AtencionApiError) => {
          if (requestId === this.previewRequestSeq) {
            this.handlePreviewError(error);
          }
        },
      });
  }

  private applyPreviewResponse(data: PreviewMonto | null, message: string | null): void {
    this.previewLoading.set(false);
    this.preview.set(data);
    this.expectedTariffId.set(data?.tarifa_id ?? null);
    this.previewError.set(message);
    this.tariffChanged.set(null);
    this.confirmTariff.set(false);
  }

  private handlePreviewError(error: AtencionApiError): void {
    this.previewLoading.set(false);
    this.preview.set(null);
    this.expectedTariffId.set(null);
    this.previewError.set(error.message);
  }

  private resetPreview(): void {
    this.previewRequestSeq++;
    this.preview.set(null);
    this.previewError.set(null);
    this.previewLoading.set(false);
    this.expectedTariffId.set(null);
    this.tariffChanged.set(null);
    this.confirmTariff.set(false);
  }

  private handleFormError(error: AtencionApiError): void {
    this.formSaving.set(false);
    this.formFieldErrors.set(error.errors ?? {});
    this.formError.set(error.message);

    if (error.code === 'TARIFA_CAMBIO' && error.tarifa_aplicable) {
      this.tariffChanged.set(error.tarifa_aplicable);
      this.preview.set(error.tarifa_aplicable);
      this.expectedTariffId.set(error.tarifa_aplicable.tarifa_id);
      this.confirmTariff.set(false);
    }
  }

  private loadTutores(pacienteId: number): void {
    this.formTutoresLoading.set(true);
    this.formTutoresError.set(null);

    this.pacientesService
      .getPaciente(pacienteId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.formTutoresLoading.set(false);
          const tutores = response.data.tutores ?? [];

          if (tutores.length === 0) {
            this.formTutoresError.set(
              'El paciente no tiene tutores disponibles. Registrá uno antes de crear la entrevista.',
            );
            return;
          }

          if (!tutores.some((tutor) => tutor.activo)) {
            this.formTutoresError.set(
              'El paciente no tiene tutores activos. Reactivá o registrá un tutor antes de crear la entrevista.',
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
            this.interviewForm.controls.tutor_id.setValue(
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

  private loadOptions(): void {
    this.sucursalesService
      .list({ estado: 'activa', page: 1, per_page: 50, sort: 'nombre', direction: 'asc' })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.branches.set(response.data),
        error: () => this.branches.set([]),
      });

    this.tarifasService
      .listCategorias({ estado: 'activo' })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.categories.set(response.data),
        error: () => this.categories.set([]),
      });

    this.loadInterviewServices();
    this.loadDiagnosticTypes();
    this.loadProfessionals();
  }

  private loadInterviewServices(): void {
    this.catalogoService
      .listTiposServicio({ estado: 'activo' })
      .pipe(
        switchMap((response) => {
          const tipo = response.data.find((item) => item.codigo === 'entrevista');

          if (!tipo) {
            return of([] as Servicio[]);
          }

          return this.loadAllPages<Servicio>((page) =>
            this.catalogoService.listServicios({
              tipo_servicio_id: tipo.id,
              estado: 'activo',
              page,
              per_page: 50,
            }),
          );
        }),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe({
        next: (servicios) => this.interviewServices.set(servicios),
        error: () => this.interviewServices.set([]),
      });
  }

  private loadDiagnosticTypes(): void {
    this.loadAllPages((page) =>
      this.tiposService.listTipos({ estado: 'activo', page, per_page: 50 }),
    )
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (types) =>
          this.diagnosticTypes.set(
            types.map((tipo) => ({
              id: tipo.id,
              nombre: tipo.nombre,
              dias_estimados: tipo.dias_estimados,
            })),
          ),
        error: () => this.diagnosticTypes.set([]),
      });
  }

  private loadProfessionals(): void {
    this.loadAllPages((page) =>
      this.profesionalesService.listProfesionales({
        estado: 'activo',
        page,
        per_page: 50,
      }),
    )
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (profesionales) => this.professionals.set(profesionales),
        error: () => this.professionals.set([]),
      });
  }

  private loadAllPages<T>(
    fetcher: (page: number) => import('rxjs').Observable<{ data: T[]; meta: { last_page: number } }>,
  ): import('rxjs').Observable<T[]> {
    return fetcher(1).pipe(
      switchMap((first) => {
        if (first.meta.last_page <= 1) {
          return of(first.data);
        }

        return forkJoin(
          Array.from({ length: first.meta.last_page - 1 }, (_, index) => fetcher(index + 2)),
        ).pipe(map((pages) => [...first.data, ...pages.flatMap((page) => page.data)]));
      }),
    );
  }

  private applyMeta(meta: {
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
  }): void {
    this.page.set(meta.current_page);
    this.lastPage.set(meta.last_page);
    this.total.set(meta.total);
    this.from.set(meta.from);
    this.to.set(meta.to);
  }

  private handleListError(error: AtencionApiError): void {
    this.loading.set(false);
    this.error.set(error);
    this.entrevistaRows.set([]);
    this.diagnosticoRows.set([]);
  }

  private validateFilterDates(): boolean {
    const { periodo_desde, periodo_hasta } = this.filters.getRawValue();

    if ((periodo_desde === '') !== (periodo_hasta === '')) {
      this.error.set({
        status: 422,
        message: 'Completá ambas fechas del período o dejá las dos vacías.',
      });

      return false;
    }

    if (periodo_desde !== '' && periodo_hasta !== '' && periodo_hasta < periodo_desde) {
      this.error.set({
        status: 422,
        message: 'La fecha "Hasta" no puede ser anterior a la fecha "Desde".',
      });

      return false;
    }

    return true;
  }

  private clearFormErrors(): void {
    this.formError.set(null);
    this.formFieldErrors.set({});
  }

  private today(): string {
    return new Intl.DateTimeFormat('en-CA', {
      timeZone: 'America/La_Paz',
    }).format(new Date());
  }

  private addDays(value: string, days: number): string {
    const [year, month, day] = value.split('-').map(Number);
    const date = new Date(year, month - 1, day, 12);
    date.setDate(date.getDate() + days);

    return new Intl.DateTimeFormat('en-CA', {
      timeZone: 'America/La_Paz',
    }).format(date);
  }
}
