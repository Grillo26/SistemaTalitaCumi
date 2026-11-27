import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { Observable, forkJoin } from 'rxjs';
import { debounceTime } from 'rxjs/operators';
import { ToastService } from '../../../core/services/toast.service';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../../shared/components/ui/modal/modal.component';
import { PaginationComponent } from '../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { TABLE_IMPORTS } from '../../../shared/components/ui/table';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { InscripcionDateInputComponent } from '../salas-grupales/inscripcion-date-input.component';
import { Branch } from '../../organizacion/sucursales/sucursales.models';
import { SucursalesService } from '../../organizacion/sucursales/sucursales.service';
import { Servicio } from '../../catalogo-servicios/servicios/servicios.models';
import { CatalogoServiciosService } from '../../catalogo-servicios/servicios/servicios.service';
import {
  CambioEstadoModalComponent,
  CambioEstadoModalResult,
} from './cambio-estado-modal.component';
import { EstadoBadgeComponent } from './estado-badge.component';
import { HistorialEstadosComponent } from './historial-estados.component';
import {
  AccionEstado,
  CambioEstadoPayload,
  ESTADOS_TEXTS,
  ESTADO_INSCRIPCION_OPTIONS,
  ESTADO_PACIENTE_OPTIONS,
  EstadoAbilities,
  EstadoApiError,
  EstadoInscripcionRow,
  EstadosTab,
  ExportFormat,
  HistorialEstadoItem,
  MotivoOption,
  OpcionesEstado,
  PacienteEstadoRow,
  PaginationMeta,
  PrechequeoEstado,
  PrechequeoPaciente,
  PreviewRetiroPaciente,
  ReporteEstadosData,
  TIPO_ENTIDAD_OPTIONS,
  TipoEntidad,
  diasLabel,
  estadoColor,
  formatDate,
} from './estados.models';
import {
  RetiroPacienteModalComponent,
  RetiroPacienteModalResult,
} from './retiro-paciente-modal.component';
import { EstadosService } from './estados.service';

const EMPTY_ABILITIES: EstadoAbilities = {
  view: false,
  reports: false,
  export: false,
  patient: { pause: false, reactivate: false, retire: false },
  inscription: { pause: false, reactivate: false },
  types: {
    inscripcion_sala: { view: false, withdraw: false },
    paquete_sesiones: { view: false, withdraw: false },
  },
};

interface CambioModalState {
  mode: 'paciente' | 'inscripcion';
  action: AccionEstado;
  tipo: TipoEntidad | null;
  id: number;
  nombre: string;
  estadoActual: string;
  estadoActualLabel: string;
  motivoOptions: MotivoOption[];
  requiresMotive: boolean;
  requiresDate: boolean;
}

@Component({
  selector: 'app-estados-page',
  imports: [
    ReactiveFormsModule,
    PageBreadcrumbComponent,
    ComponentCardComponent,
    AlertComponent,
    ButtonComponent,
    InputDirective,
    ModalComponent,
    PaginationComponent,
    SelectFieldComponent,
    InscripcionDateInputComponent,
    EstadoBadgeComponent,
    HistorialEstadosComponent,
    CambioEstadoModalComponent,
    RetiroPacienteModalComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './estados.page.html',
})
export class EstadosPageComponent implements OnInit {
  private readonly service = inject(EstadosService);
  private readonly sucursales = inject(SucursalesService);
  private readonly catalogo = inject(CatalogoServiciosService);
  private readonly toast = inject(ToastService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = ESTADOS_TEXTS;
  readonly estadoPacienteOptions = ESTADO_PACIENTE_OPTIONS;
  readonly estadoInscripcionOptions = ESTADO_INSCRIPCION_OPTIONS;
  readonly tipoOptions = TIPO_ENTIDAD_OPTIONS;
  readonly formatDate = formatDate;
  readonly diasLabel = diasLabel;
  readonly estadoColor = estadoColor;

  readonly activeTab = signal<EstadosTab>('pacientes');
  readonly abilities = signal<EstadoAbilities>(EMPTY_ABILITIES);
  readonly opciones = signal<OpcionesEstado | null>(null);
  readonly branches = signal<Branch[]>([]);
  readonly services = signal<Servicio[]>([]);
  readonly optionsError = signal<string | null>(null);

  readonly patients = signal<PacienteEstadoRow[]>([]);
  readonly patientMeta = signal<PaginationMeta>(this.emptyMeta());
  readonly patientLoading = signal(false);
  readonly patientError = signal<string | null>(null);

  readonly inscriptions = signal<EstadoInscripcionRow[]>([]);
  readonly inscriptionMeta = signal<PaginationMeta>(this.emptyMeta());
  readonly inscriptionLoading = signal(false);
  readonly inscriptionError = signal<string | null>(null);

  readonly reportData = signal<ReporteEstadosData | null>(null);
  readonly reportLoading = signal(false);
  readonly reportError = signal<string | null>(null);
  readonly exporting = signal<ExportFormat | null>(null);

  readonly cambioOpen = signal(false);
  readonly cambio = signal<CambioModalState | null>(null);
  readonly cambioSaving = signal(false);
  readonly cambioError = signal<string | null>(null);
  readonly cambioPrecheck = signal<PrechequeoEstado | null>(null);
  readonly cambioLoading = signal(false);

  readonly retiroOpen = signal(false);
  readonly retiroPreview = signal<PreviewRetiroPaciente | null>(null);
  readonly retiroPrecheck = signal<PrechequeoPaciente | null>(null);
  readonly retiroSaving = signal(false);
  readonly retiroError = signal<string | null>(null);
  readonly retiroLoading = signal(false);

  readonly historialOpen = signal(false);
  readonly historialLoading = signal(false);
  readonly historialError = signal<string | null>(null);
  readonly historialItems = signal<HistorialEstadoItem[]>([]);
  readonly historialTitle = signal('');

  readonly patientForm = this.fb.nonNullable.group({
    search: [''],
    estado: [''],
    sucursal_id: [''],
  });

  readonly inscriptionForm = this.fb.nonNullable.group({
    search: [''],
    tipo_entidad: [''],
    estado: [''],
    sucursal_id: [''],
    servicio_id: [''],
    periodo_desde: [''],
    periodo_hasta: [''],
  });

  readonly reportForm = this.fb.nonNullable.group({
    tipo: ['inscripciones'],
    estado: [''],
    sucursal_id: [''],
    servicio_id: [''],
    periodo_desde: [''],
    periodo_hasta: [''],
  });

  readonly today = this.dateToday();

  readonly branchOptions = computed(() => [
    { value: '', label: this.texts.allBranches },
    ...this.branches().map((branch) => ({ value: String(branch.id), label: branch.nombre })),
  ]);

  readonly serviceOptions = computed(() => [
    { value: '', label: this.texts.allServices },
    ...this.services().map((servicio) => ({ value: String(servicio.id), label: servicio.nombre })),
  ]);

  readonly typeOptions = computed(() => {
    const allowed = this.abilities().types;

    return this.tipoOptions.filter(
      (option) =>
        option.value === '' ||
        (option.value === 'inscripcion_sala' && allowed.inscripcion_sala.view) ||
        (option.value === 'paquete_sesiones' && allowed.paquete_sesiones.view),
    );
  });

  readonly reportRows = computed(() => this.reportData()?.data ?? []);
  readonly reportIsPatients = computed(() => this.reportData()?.tipo === 'pacientes');

  ngOnInit(): void {
    this.patientForm.controls.search.valueChanges
      .pipe(debounceTime(300), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.loadPatients(1));

    this.loadOptions();
    this.loadPatients(1);
  }

  setTab(tab: EstadosTab): void {
    if (this.activeTab() === tab) {
      return;
    }

    this.activeTab.set(tab);

    if (tab === 'pacientes') {
      this.loadPatients(1);
    } else if (tab === 'inscripciones') {
      this.loadInscriptions(1);
    } else {
      this.loadReport(1);
    }
  }

  loadPatients(page = 1): void {
    this.patientLoading.set(true);
    this.patientError.set(null);
    const raw = this.patientForm.getRawValue();

    this.service
      .listPacientes({
        search: raw.search.trim() || undefined,
        estado: raw.estado || undefined,
        sucursal_id: raw.sucursal_id ? Number(raw.sucursal_id) : '',
        page,
        per_page: 20,
        sort: 'nombre_completo',
        direction: 'asc',
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.patients.set(response.data);
          this.patientMeta.set(response.meta);
          this.abilities.set(response.can);
          this.patientLoading.set(false);
        },
        error: (error: EstadoApiError) => {
          this.patientLoading.set(false);
          this.patientError.set(this.errorMessage(error));
          this.patients.set([]);
        },
      });
  }

  clearPatients(): void {
    this.patientForm.reset({ search: '', estado: '', sucursal_id: '' });
    this.loadPatients(1);
  }

  goToPatientPage(page: number): void {
    if (page >= 1 && page <= this.patientMeta().last_page) {
      this.loadPatients(page);
    }
  }

  loadInscriptions(page = 1): void {
    this.inscriptionLoading.set(true);
    this.inscriptionError.set(null);
    const raw = this.inscriptionForm.getRawValue();

    this.service
      .listInscripciones({
        search: raw.search.trim() || undefined,
        tipo_entidad: raw.tipo_entidad || undefined,
        estado: raw.estado || undefined,
        sucursal_id: raw.sucursal_id ? Number(raw.sucursal_id) : '',
        servicio_id: raw.servicio_id ? Number(raw.servicio_id) : '',
        periodo_desde: raw.periodo_desde || undefined,
        periodo_hasta: raw.periodo_hasta || undefined,
        page,
        per_page: 20,
        sort: 'fecha_fin',
        direction: 'asc',
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.inscriptions.set(response.data);
          this.inscriptionMeta.set(response.meta);
          this.abilities.set(response.can);
          this.inscriptionLoading.set(false);
        },
        error: (error: EstadoApiError) => {
          this.inscriptionLoading.set(false);
          this.inscriptionError.set(this.errorMessage(error));
          this.inscriptions.set([]);
        },
      });
  }

  clearInscriptions(): void {
    this.inscriptionForm.reset({
      search: '',
      tipo_entidad: '',
      estado: '',
      sucursal_id: '',
      servicio_id: '',
      periodo_desde: '',
      periodo_hasta: '',
    });
    this.loadInscriptions(1);
  }

  goToInscriptionPage(page: number): void {
    if (page >= 1 && page <= this.inscriptionMeta().last_page) {
      this.loadInscriptions(page);
    }
  }

  loadReport(page = 1): void {
    this.reportLoading.set(true);
    this.reportError.set(null);
    const raw = this.reportForm.getRawValue();
    const tipo = raw.tipo === 'pacientes' ? 'pacientes' : 'inscripciones';

    this.service
      .getReporte(tipo, {
        estado: raw.estado || undefined,
        sucursal_id: raw.sucursal_id ? Number(raw.sucursal_id) : '',
        servicio_id: raw.servicio_id ? Number(raw.servicio_id) : '',
        periodo_desde: raw.periodo_desde || undefined,
        periodo_hasta: raw.periodo_hasta || undefined,
        page,
        per_page: 20,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.reportData.set(response.data);
          this.abilities.set(response.can as EstadoAbilities);
          this.reportLoading.set(false);
        },
        error: (error: EstadoApiError) => {
          this.reportLoading.set(false);
          this.reportError.set(this.errorMessage(error));
          this.reportData.set(null);
        },
      });
  }

  clearReport(): void {
    this.reportForm.reset({
      tipo: 'inscripciones',
      estado: '',
      sucursal_id: '',
      servicio_id: '',
      periodo_desde: '',
      periodo_hasta: '',
    });
    this.loadReport(1);
  }

  goToReportPage(page: number): void {
    const meta = this.reportData()?.meta;

    if (meta && page >= 1 && page <= meta.last_page) {
      this.loadReport(page);
    }
  }

  patientCount(estado: string): number {
    const counts = this.reportData()?.conteos.pacientes as Record<string, number> | undefined;

    return counts?.[estado] ?? 0;
  }

  inscriptionCount(estado: string): number {
    const counts = this.reportData()?.conteos.inscripciones as
      | Record<string, number>
      | undefined;

    return counts?.[estado] ?? 0;
  }

  exportar(formato: ExportFormat): void {
    if (this.exporting() !== null) {
      return;
    }

    this.exporting.set(formato);
    const raw = this.reportForm.getRawValue();
    const tipo = raw.tipo === 'pacientes' ? 'pacientes' : 'inscripciones';

    this.service
      .exportar(formato, tipo, {
        estado: raw.estado || undefined,
        sucursal_id: raw.sucursal_id ? Number(raw.sucursal_id) : '',
        servicio_id: raw.servicio_id ? Number(raw.servicio_id) : '',
        periodo_desde: raw.periodo_desde || undefined,
        periodo_hasta: raw.periodo_hasta || undefined,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (blob) => {
          this.exporting.set(null);
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = `estados-${tipo}.${formato === 'excel' ? 'xlsx' : 'pdf'}`;
          link.click();
          URL.revokeObjectURL(url);
        },
        error: (error: EstadoApiError) => {
          this.exporting.set(null);
          this.toast.error(
            this.texts.reports,
            error.code === 'MODULO_NO_DISPONIBLE'
              ? this.texts.exportUnavailable
              : this.errorMessage(error),
          );
        },
      });
  }

  openPausePatient(row: PacienteEstadoRow): void {
    this.openCambio({
      mode: 'paciente',
      action: 'pausar',
      tipo: null,
      id: row.id,
      nombre: row.nombre_completo,
      estadoActual: row.estado,
      estadoActualLabel: row.estado_label,
      motivoOptions: this.opciones()?.motivos.paciente.pausar ?? [],
      requiresMotive: true,
      requiresDate: false,
    });
  }

  openReactivatePatient(row: PacienteEstadoRow): void {
    this.openCambio({
      mode: 'paciente',
      action: 'reactivar',
      tipo: null,
      id: row.id,
      nombre: row.nombre_completo,
      estadoActual: row.estado,
      estadoActualLabel: row.estado_label,
      motivoOptions: [],
      requiresMotive: false,
      requiresDate: false,
    });
  }

  openRetirePatient(row: PacienteEstadoRow): void {
    this.retiroLoading.set(true);
    this.retiroError.set(null);

    this.service
      .prechequeoRetiroPaciente(row.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.retiroPreview.set(response.data.preview);
          this.retiroPrecheck.set(response.data.prechequeo);
          this.retiroOpen.set(true);
          this.retiroLoading.set(false);
        },
        error: (error: EstadoApiError) => {
          this.retiroLoading.set(false);
          this.toast.error(this.texts.retirePatient, this.errorMessage(error));
        },
      });
  }

  openPauseInscription(row: EstadoInscripcionRow): void {
    this.openInscriptionCambio(row, 'pausar', true, false);
  }

  openReactivateInscription(row: EstadoInscripcionRow): void {
    this.openInscriptionCambio(row, 'reactivar', false, false);
  }

  openRetireInscription(row: EstadoInscripcionRow): void {
    this.openInscriptionCambio(row, 'retirar', true, true);
  }

  private openInscriptionCambio(
    row: EstadoInscripcionRow,
    action: AccionEstado,
    requiresMotive: boolean,
    requiresDate: boolean,
  ): void {
    this.cambioLoading.set(true);
    this.cambioError.set(null);

    this.service
      .prechequeoInscripcion(row.tipo_entidad, row.id, action)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const motivos = this.opciones();
          const motivoOptions =
            action === 'retirar'
              ? (motivos?.motivos[row.tipo_entidad].retirar ?? [])
              : action === 'pausar'
                ? (motivos?.motivos[row.tipo_entidad].pausar ?? [])
                : [];

          this.openCambio({
            mode: 'inscripcion',
            action,
            tipo: row.tipo_entidad,
            id: row.id,
            nombre: row.paciente.nombre_completo,
            estadoActual: row.estado_efectivo,
            estadoActualLabel: row.estado_efectivo_label,
            motivoOptions,
            requiresMotive,
            requiresDate,
          });
          this.cambioPrecheck.set(response.data);
          this.cambioLoading.set(false);
        },
        error: (error: EstadoApiError) => {
          this.cambioLoading.set(false);
          this.toast.error(this.texts.title, this.errorMessage(error));
        },
      });
  }

  private openCambio(state: CambioModalState): void {
    this.cambio.set(state);
    this.cambioError.set(null);
    this.cambioSaving.set(false);
    this.cambioOpen.set(true);
  }

  closeCambio(): void {
    if (!this.cambioSaving()) {
      this.cambioOpen.set(false);
      this.cambio.set(null);
      this.cambioPrecheck.set(null);
    }
  }

  confirmCambio(result: CambioEstadoModalResult): void {
    const state = this.cambio();

    if (!state || this.cambioSaving()) {
      return;
    }

    this.cambioSaving.set(true);
    this.cambioError.set(null);
    const request$ =
      state.mode === 'paciente'
        ? state.action === 'pausar'
          ? this.service.pausarPaciente(state.id, this.cambioPayload(state, result))
          : this.service.reactivarPaciente(state.id, this.cambioPayload(state, result))
        : state.action === 'pausar'
          ? this.service.pausarInscripcion(
              state.tipo!,
              state.id,
              this.cambioPayload(state, result),
            )
          : state.action === 'reactivar'
            ? this.service.reactivarInscripcion(
                state.tipo!,
                state.id,
                this.cambioPayload(state, result),
              )
            : this.service.retirarInscripcion(
                state.tipo!,
                state.id,
                this.cambioPayload(state, result),
              );

    request$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.cambioSaving.set(false);
        this.cambioOpen.set(false);
        this.cambio.set(null);
        this.cambioPrecheck.set(null);
        this.toast.success(this.texts.title, 'Cambio de estado registrado.');
        this.refreshCurrentTab();
      },
      error: (error: EstadoApiError) => {
        this.cambioSaving.set(false);
        this.cambioError.set(this.errorMessage(error));
        this.handleConflict(error);
      },
    });
  }

  closeRetiro(): void {
    if (!this.retiroSaving()) {
      this.retiroOpen.set(false);
      this.retiroPreview.set(null);
      this.retiroPrecheck.set(null);
      this.retiroError.set(null);
    }
  }

  confirmRetiro(result: RetiroPacienteModalResult): void {
    const preview = this.retiroPreview();

    if (!preview || this.retiroSaving()) {
      return;
    }

    this.retiroSaving.set(true);
    this.retiroError.set(null);

    this.service
      .retirarPaciente(preview.paciente.id, {
        estado_esperado: preview.paciente.estado,
        motivo: result.motivo,
        motivo_detalle: result.motivo_detalle,
        fecha_baja: result.fecha_baja,
        inscripciones: result.inscripciones.map((item) => ({
          tipo_entidad: item.tipo_entidad,
          id: item.id,
          estado_esperado: item.estado_esperado as EstadoInscripcionRow['estado_efectivo'],
        })),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.retiroSaving.set(false);
          this.retiroOpen.set(false);
          this.retiroPreview.set(null);
          this.retiroPrecheck.set(null);
          this.toast.success(
            this.texts.retirePatient,
            'Paciente e inscripciones retiradas con el mismo motivo y fecha.',
          );
          this.refreshCurrentTab();
        },
        error: (error: EstadoApiError) => {
          this.retiroSaving.set(false);
          this.retiroError.set(this.errorMessage(error));
          this.handleConflict(error);
        },
      });
  }

  openHistoryPatient(row: PacienteEstadoRow): void {
    this.openHistory(`Historial de ${row.nombre_completo}`, () =>
      this.service.historialPaciente(row.id),
    );
  }

  openHistoryInscription(row: EstadoInscripcionRow): void {
    this.openHistory(`Historial de ${row.tipo_entidad_label} #${row.id}`, () =>
      this.service.historialInscripcion(row.tipo_entidad, row.id),
    );
  }

  private openHistory(
    title: string,
    request: () => Observable<{ data: HistorialEstadoItem[]; can: unknown }>,
  ): void {
    this.historialTitle.set(title);
    this.historialItems.set([]);
    this.historialError.set(null);
    this.historialLoading.set(true);
    this.historialOpen.set(true);

    request()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.historialItems.set(response.data);
          this.historialLoading.set(false);
        },
        error: (error: EstadoApiError) => {
          this.historialLoading.set(false);
          this.historialError.set(this.errorMessage(error));
        },
      });
  }

  closeHistory(): void {
    this.historialOpen.set(false);
    this.historialItems.set([]);
    this.historialError.set(null);
  }

  private cambioPayload(
    state: CambioModalState,
    result: CambioEstadoModalResult,
  ): CambioEstadoPayload {
    const payload: CambioEstadoPayload = {
      estado_esperado: state.estadoActual,
      motivo: result.motivo,
      motivo_detalle: result.motivo_detalle,
      fecha_baja: result.fecha_baja ?? undefined,
    };

    if (state.mode === 'paciente') {
      payload.estado = state.action === 'pausar' ? 'en_pausa' : 'activo';
    }

    return payload;
  }

  private handleConflict(error: EstadoApiError): void {
    if (error.code === 'ESTADO_DESACTUALIZADO') {
      this.toast.info(this.texts.title, this.texts.stateChanged);
      this.refreshCurrentTab();
    }
  }

  private refreshCurrentTab(): void {
    if (this.activeTab() === 'pacientes') {
      this.loadPatients(this.patientMeta().current_page);
    } else if (this.activeTab() === 'inscripciones') {
      this.loadInscriptions(this.inscriptionMeta().current_page);
    } else {
      this.loadReport(this.reportData()?.meta.current_page ?? 1);
    }
  }

  private loadOptions(): void {
    this.optionsError.set(null);

    forkJoin({
      opciones: this.service.getOpciones(),
      branches: this.sucursales.list({
        estado: 'activa',
        page: 1,
        per_page: 50,
        sort: 'nombre',
        direction: 'asc',
      }),
      services: this.catalogo.listServicios({ estado: 'activo', page: 1, per_page: 50 }),
    })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: ({ opciones, branches, services }) => {
          this.opciones.set(opciones.data);
          this.abilities.set(opciones.can as EstadoAbilities);
          this.branches.set(branches.data);
          this.services.set(services.data);
        },
        error: (error: EstadoApiError) => {
          this.optionsError.set(this.errorMessage(error));
        },
      });
  }

  errorMessage(error: EstadoApiError): string {
    if (error.code === 'SIN_SUCURSALES_ASIGNADAS') {
      return this.texts.noBranches;
    }

    if (error.code === 'RETIRO_FUERA_DE_SUCURSAL') {
      return error.message;
    }

    if (error.status === 403) {
      return this.texts.forbidden;
    }

    if (error.code === 'ESTADO_DESACTUALIZADO') {
      return this.texts.stateChanged;
    }

    return error.message || 'No se pudo completar la operación.';
  }

  private emptyMeta(): PaginationMeta {
    return {
      current_page: 1,
      last_page: 1,
      per_page: 20,
      total: 0,
      from: null,
      to: null,
    };
  }

  private dateToday(): string {
    const now = new Date();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${now.getFullYear()}-${month}-${day}`;
  }
}
