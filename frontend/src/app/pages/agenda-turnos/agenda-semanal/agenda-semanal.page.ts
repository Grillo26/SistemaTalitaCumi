import { DOCUMENT } from '@angular/common';
import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { Subject, debounceTime, timer } from 'rxjs';
import { environment } from '../../../../environments/environment';
import { AgendaRefreshService } from '../../../core/services/agenda-refresh.service';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { AgendaCalendarioComponent } from './components/agenda-calendario/agenda-calendario.component';
import { AgendaFiltrosComponent } from './components/agenda-filtros/agenda-filtros.component';
import { AgendaImpresionComponent } from './components/agenda-impresion/agenda-impresion.component';
import { AgendaToolbarComponent } from './components/agenda-toolbar/agenda-toolbar.component';
import { AgendaVistaAgrupadaComponent } from './components/agenda-vista-agrupada/agenda-vista-agrupada.component';
import { ResumenSemanaComponent } from './components/resumen-semana/resumen-semana.component';
import { TurnoDetallePanelComponent } from './components/turno-detalle-panel/turno-detalle-panel.component';
import { AGENDA_TEXTS } from './agenda-semanal.constants';
import {
  EMPTY_FILTROS,
  type AgendaApiError,
  type AgendaFiltros,
  type AgendaSemanaParams,
  type AgendaSemanalData,
  type AgendaVista,
  type OpcionesFiltrosData,
  type SucursalDisponible,
  type TurnoDetalleVista,
} from './agenda-semanal.models';
import { AgendaSemanalService } from './agenda-semanal.service';
import {
  addDays,
  addWeeks,
  formatGeneratedAt,
  formatWeekRange,
  mondayOf,
  todayInAgendaTimezone,
  toApiParams,
} from './agenda-semanal.utils';

@Component({
  selector: 'app-agenda-semanal-page',
  imports: [
    PageBreadcrumbComponent,
    ComponentCardComponent,
    AlertComponent,
    AgendaToolbarComponent,
    AgendaFiltrosComponent,
    AgendaCalendarioComponent,
    AgendaVistaAgrupadaComponent,
    ResumenSemanaComponent,
    TurnoDetallePanelComponent,
    AgendaImpresionComponent,
  ],
  templateUrl: './agenda-semanal.page.html',
})
export class AgendaSemanalPageComponent implements OnInit {
  private readonly service = inject(AgendaSemanalService);
  private readonly refreshService = inject(AgendaRefreshService);
  private readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);
  private readonly document = inject(DOCUMENT);
  private readonly destroyRef = inject(DestroyRef);

  private readonly filtrosSubject = new Subject<void>();
  private requestSeq = 0;

  readonly texts = AGENDA_TEXTS;

  readonly sucursales = signal<SucursalDisponible[]>([]);
  readonly sucursalId = signal<number | null>(null);
  readonly semana = signal<string>(mondayOf(todayInAgendaTimezone()));
  readonly data = signal<AgendaSemanalData | null>(null);
  readonly opciones = signal<OpcionesFiltrosData | null>(null);
  readonly filtros = signal<AgendaFiltros>({ ...EMPTY_FILTROS });
  readonly vista = signal<AgendaVista>('semana');

  readonly loading = signal(false);
  readonly loadingOpciones = signal(false);
  readonly error = signal<string | null>(null);
  readonly filtrosError = signal<string | null>(null);
  readonly noBranches = signal(false);
  readonly exporting = signal(false);
  readonly lastUpdated = signal<string | null>(null);

  readonly detalleOpen = signal(false);
  readonly detalleLoading = signal(false);
  readonly detalle = signal<TurnoDetalleVista | null>(null);
  readonly detalleError = signal<string | null>(null);

  readonly canExportar = computed(() => this.auth.hasPermission('turno.exportar'));

  readonly weekLabel = computed(() => {
    const agenda = this.data();

    if (agenda !== null) {
      return formatWeekRange(agenda.semana.inicio_visible, agenda.semana.fin_visible);
    }

    return formatWeekRange(this.semana(), addDays(this.semana(), 5));
  });

  readonly weekSubtitle = computed(() => {
    const agenda = this.data();

    return agenda === null
      ? ''
      : `Horario ${agenda.rango_horario.hora_inicio} – ${agenda.rango_horario.hora_fin}`;
  });

  readonly visibleDays = computed(
    () => this.data()?.semana.dias.filter((dia) => dia.visible) ?? [],
  );

  readonly emptyMessage = computed(() =>
    this.filtros().profesional_id !== ''
      ? this.texts.emptyProfessional
      : this.texts.emptyWeek,
  );

  readonly disponibilidadParcial = computed(() => {
    const agenda = this.data();

    if (agenda === null) {
      return false;
    }

    return Object.values(agenda.disponibilidad).some((available) => !available);
  });

  readonly lastUpdatedLabel = computed(() => formatGeneratedAt(this.lastUpdated()));

  ngOnInit(): void {
    this.cargarSucursales();

    this.filtrosSubject
      .pipe(debounceTime(300), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.cargarSemana(false));

    this.refreshService.changes$
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.cargarSemana(false));

    if (environment.agendaRefreshSeconds > 0) {
      timer(environment.agendaRefreshSeconds * 1000, environment.agendaRefreshSeconds * 1000)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe(() => {
          if (
            this.document.visibilityState !== 'hidden' &&
            this.sucursalId() !== null &&
            !this.loading()
          ) {
            this.cargarSemana(false);
          }
        });
    }
  }

  cargarSucursales(): void {
    this.loading.set(true);
    this.error.set(null);
    this.noBranches.set(false);

    this.service.getSucursales().subscribe({
      next: (response) => {
        this.sucursales.set(response.data);

        if (response.data.length === 0) {
          this.noBranches.set(true);
          this.loading.set(false);
          return;
        }

        const defaultId = response.data.some((branch) => branch.id === response.predeterminada_id)
          ? response.predeterminada_id
          : (response.data[0]?.id ?? null);

        if (defaultId === null) {
          this.noBranches.set(true);
          this.loading.set(false);
          return;
        }

        this.sucursalId.set(defaultId);
        this.cargarOpciones(defaultId);
        this.cargarSemana();
      },
      error: (error: unknown) => {
        const apiError = asAgendaError(error);

        if (apiError.code === 'SIN_SUCURSALES_ASIGNADAS') {
          this.noBranches.set(true);
        } else {
          this.error.set(apiError.message);
        }

        this.loading.set(false);
      },
    });
  }

  cargarSemana(showLoading = true): void {
    const branchId = this.sucursalId();

    if (branchId === null) {
      return;
    }

    const sequence = ++this.requestSeq;

    if (showLoading) {
      this.loading.set(true);
    }

    this.error.set(null);

    const params: AgendaSemanaParams = {
      sucursal_id: branchId,
      semana: this.semana(),
      ...toApiParams(this.filtros()),
    };

    this.service.getSemana(params).subscribe({
      next: (response) => {
        if (sequence !== this.requestSeq) {
          return;
        }

        this.data.set(response.data);
        this.semana.set(response.data.semana.inicio);
        this.lastUpdated.set(new Date().toISOString());
        this.loading.set(false);
      },
      error: (error: unknown) => {
        if (sequence !== this.requestSeq) {
          return;
        }

        const apiError = asAgendaError(error);

        if (apiError.code === 'SIN_SUCURSALES_ASIGNADAS') {
          this.noBranches.set(true);
        } else {
          this.error.set(apiError.message);
        }

        this.loading.set(false);
      },
    });
  }

  cargarOpciones(branchId: number): void {
    this.loadingOpciones.set(true);
    this.filtrosError.set(null);

    this.service.getOpciones(branchId).subscribe({
      next: (response) => {
        this.opciones.set(response.data);
        this.loadingOpciones.set(false);
      },
      error: (error: unknown) => {
        const apiError = asAgendaError(error);
        this.opciones.set(null);
        this.filtrosError.set(apiError.message);
        this.loadingOpciones.set(false);
      },
    });
  }

  onSucursalChange(branchId: number): void {
    this.sucursalId.set(branchId);
    this.filtros.set({ ...EMPTY_FILTROS });
    this.data.set(null);
    this.cargarOpciones(branchId);
    this.cargarSemana();
  }

  onFiltrosChange(filtros: AgendaFiltros): void {
    this.filtros.set(filtros);
    this.filtrosSubject.next();
  }

  limpiarFiltros(): void {
    this.filtros.set({ ...EMPTY_FILTROS });
    this.filtrosSubject.next();
  }

  onVistaChange(vista: AgendaVista): void {
    this.vista.set(vista);
  }

  semanaAnterior(): void {
    this.semana.set(addWeeks(this.semana(), -1));
    this.cargarSemana();
  }

  semanaSiguiente(): void {
    this.semana.set(addWeeks(this.semana(), 1));
    this.cargarSemana();
  }

  irAHoy(): void {
    this.semana.set(mondayOf(todayInAgendaTimezone()));
    this.cargarSemana();
  }

  actualizar(): void {
    this.cargarSemana(false);
  }

  recargar(): void {
    if (this.sucursales().length === 0 || this.noBranches()) {
      this.cargarSucursales();
      return;
    }

    this.cargarSemana();
  }

  verDetalle(turnoId: number): void {
    this.detalleOpen.set(true);
    this.detalleLoading.set(true);
    this.detalle.set(null);
    this.detalleError.set(null);

    this.service.getDetalle(turnoId).subscribe({
      next: (response) => {
        const mensaje =
          response.mensaje_acciones ??
          (response.acciones.length === 0 ? this.texts.actionsUnavailable : null);

        this.detalle.set({
          turno: response.data,
          acciones: response.acciones,
          mensaje,
        });
        this.detalleLoading.set(false);
      },
      error: (error: unknown) => {
        const apiError = asAgendaError(error);
        this.detalleError.set(apiError.message);
        this.detalleLoading.set(false);
      },
    });
  }

  cerrarDetalle(): void {
    this.detalleOpen.set(false);
    this.detalle.set(null);
    this.detalleError.set(null);
  }

  exportar(): void {
    const agenda = this.data();

    if (agenda === null || !this.canExportar()) {
      return;
    }

    this.exporting.set(true);

    const params: AgendaSemanaParams = {
      sucursal_id: agenda.sucursal.id,
      semana: agenda.semana.inicio,
      ...toApiParams(this.filtros()),
    };

    this.service.exportar(params).subscribe({
      next: (blob) => {
        this.descargar(blob, `agenda-${agenda.sucursal.codigo}-${agenda.semana.inicio}.pdf`);
        this.exporting.set(false);
      },
      error: (error: unknown) => {
        const apiError = asAgendaError(error);
        this.toast.error(this.texts.exportPdf, apiError.message);
        this.exporting.set(false);
      },
    });
  }

  imprimir(): void {
    if (this.data() === null) {
      return;
    }

    this.document.body.classList.add('agenda-printing');

    setTimeout(() => {
      this.document.defaultView?.print();
      this.document.body.classList.remove('agenda-printing');
    }, 50);
  }

  private descargar(blob: Blob, filename: string): void {
    const url = URL.createObjectURL(blob);
    const anchor = this.document.createElement('a');

    anchor.href = url;
    anchor.download = filename;
    anchor.click();
    URL.revokeObjectURL(url);
  }
}

function asAgendaError(error: unknown): AgendaApiError {
  if (typeof error === 'object' && error !== null) {
    const candidate = error as Partial<AgendaApiError>;

    return {
      status: candidate.status ?? 0,
      message: candidate.message ?? 'No se pudo completar la operación.',
      code: candidate.code,
      errors: candidate.errors,
      modulo: candidate.modulo,
    };
  }

  return {
    status: 0,
    message: 'No se pudo completar la operación.',
  };
}
