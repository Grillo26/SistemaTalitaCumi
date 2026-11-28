import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { DOCUMENT } from '@angular/common';
import { AgendaRefreshService } from '../../../../../core/services/agenda-refresh.service';
import { ToastService } from '../../../../../core/services/toast.service';
import { AlertComponent } from '../../../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../../../../shared/components/ui/modal/modal.component';
import { SelectFieldComponent } from '../../../../../shared/components/ui/select/select-field.component';
import {
  DIA_SEMANA_LABEL,
  PROGRAMACION_TEXTS,
  type FilaPreview,
  type HallazgoProgramacion,
  type LoteResultado,
  type ModoProgramacion,
  type OrigenProgramacionData,
  type PermisosProgramacion,
  type PreviewGeneracionData,
  type PreviewRegeneracionData,
  type ProgramacionApiError,
  type ResumenGeneracion,
  type TipoOrigenProgramacion,
  type TurnoResumen,
} from '../../programacion-turnos.models';
import { ProgramacionTurnosService } from '../../programacion-turnos.service';
import type { ProfesionalAgendaOpcion } from '../../programacion-turnos.models';

type Paso = 1 | 2 | 3 | 4;

@Component({
  selector: 'app-generar-turnos-wizard',
  imports: [AlertComponent, ButtonComponent, ModalComponent, SelectFieldComponent],
  templateUrl: './generar-turnos-wizard.component.html',
})
export class GenerarTurnosWizardComponent {
  private readonly service = inject(ProgramacionTurnosService);
  private readonly refresh = inject(AgendaRefreshService);
  private readonly toast = inject(ToastService);
  private readonly document = inject(DOCUMENT);

  readonly open = input(false);
  readonly tipo = input<TipoOrigenProgramacion>('paquete_sesiones');
  readonly origenId = input<number | null>(null);
  readonly modo = input<ModoProgramacion>('generar');
  readonly can = input<PermisosProgramacion | null>(null);

  readonly closed = output<void>();
  readonly completed = output<LoteResultado>();

  readonly texts = PROGRAMACION_TEXTS;

  readonly paso = signal<Paso>(1);
  readonly loadingOrigen = signal(false);
  readonly previewLoading = signal(false);
  readonly saving = signal(false);
  readonly error = signal<string | null>(null);
  readonly origenData = signal<OrigenProgramacionData | null>(null);
  readonly previewGeneracion = signal<PreviewGeneracionData | null>(null);
  readonly previewRegeneracion = signal<PreviewRegeneracionData | null>(null);
  readonly confirmarAdvertencias = signal(false);
  readonly usarProfesionalOrigen = signal(true);
  readonly profesionalId = signal<number | null>(null);
  readonly profesionales = signal<ProfesionalAgendaOpcion[]>([]);
  readonly resultado = signal<LoteResultado | null>(null);
  readonly claveIdempotencia = signal('');

  readonly origen = computed(() => this.origenData()?.origen ?? null);
  readonly resumen = computed<ResumenGeneracion | null>(() => {
    if (this.modo() === 'regenerar') {
      return this.previewRegeneracion()?.resumen ?? null;
    }

    return this.previewGeneracion()?.resumen ?? null;
  });
  readonly filas = computed<FilaPreview[]>(() => {
    if (this.modo() === 'regenerar') {
      return this.previewRegeneracion()?.filas ?? [];
    }

    return this.previewGeneracion()?.filas ?? [];
  });
  readonly hallazgosGlobales = computed<HallazgoProgramacion[]>(() => {
    if (this.modo() === 'regenerar') {
      return this.previewRegeneracion()?.hallazgos ?? [];
    }

    return this.previewGeneracion()?.hallazgos ?? [];
  });
  readonly cancelar = computed<TurnoResumen[]>(() => this.previewRegeneracion()?.cancelar ?? []);
  readonly preservar = computed<TurnoResumen[]>(() => this.previewRegeneracion()?.preservar ?? []);
  readonly profesionalOpciones = computed(() => {
    const origen = this.origen();

    return [
      {
        value: 'origen',
        label: origen?.profesional
          ? `Profesional del origen: ${origen.profesional.nombre}`
          : `${this.texts.unassigned} (según el origen)`,
      },
      { value: 'sin_asignar', label: this.texts.unassigned },
      ...this.profesionales().map((profesional) => ({
        value: String(profesional.id),
        label: profesional.nombre_completo,
      })),
    ];
  });
  readonly profesionalSeleccionado = computed(() => {
    if (this.usarProfesionalOrigen()) {
      return 'origen';
    }

    return this.profesionalId() === null ? 'sin_asignar' : String(this.profesionalId());
  });
  readonly puedeConfirmar = computed(() => {
    const resumen = this.resumen();

    if (resumen === null) {
      return false;
    }

    const sinBloqueos = resumen.bloqueados === 0;
    const advertenciasOk =
      !resumen.requiere_confirmar_advertencias || this.confirmarAdvertencias();
    const objetivoCero =
      this.modo() === 'regenerar' && this.previewRegeneracion()?.objetivo === 0;

    return sinBloqueos && (objetivoCero || resumen.generables > 0) && advertenciasOk;
  });
  readonly previewAvanzable = computed(() => {
    const resumen = this.resumen();

    if (resumen === null) {
      return false;
    }

    const objetivoCero =
      this.modo() === 'regenerar' && this.previewRegeneracion()?.objetivo === 0;

    return resumen.bloqueados === 0 && (objetivoCero || resumen.generables > 0);
  });
  readonly pasosVisibles = computed<{ numero: Paso; etiqueta: string }[]>(() => {
    const pasos: { numero: Paso; etiqueta: string }[] = [
      { numero: 1, etiqueta: this.texts.stepPattern },
      { numero: 2, etiqueta: this.texts.stepPreview },
    ];

    if (this.modo() === 'generar') {
      pasos.push({ numero: 3, etiqueta: this.texts.stepProfessional });
    }

    pasos.push({ numero: 4, etiqueta: this.texts.stepConfirm });

    return pasos;
  });

  constructor() {
    effect(() => {
      const abierto = this.open();
      const id = this.origenId();

      if (abierto && id !== null) {
        this.iniciar(id);
      }
    });
  }

  private iniciar(id: number): void {
    this.paso.set(1);
    this.loadingOrigen.set(true);
    this.previewLoading.set(false);
    this.saving.set(false);
    this.error.set(null);
    this.origenData.set(null);
    this.previewGeneracion.set(null);
    this.previewRegeneracion.set(null);
    this.confirmarAdvertencias.set(false);
    this.usarProfesionalOrigen.set(true);
    this.profesionalId.set(null);
    this.profesionales.set([]);
    this.resultado.set(null);
    this.claveIdempotencia.set(this.nuevaClave());

    this.service.getOrigen(this.tipo(), id).subscribe({
      next: (response) => {
        this.origenData.set(response.data);
        this.loadingOrigen.set(false);

        if (response.data.origen.sucursal_id) {
          this.cargarProfesionales(response.data.origen.sucursal_id);
        }
      },
      error: (error: ProgramacionApiError) => {
        this.loadingOrigen.set(false);
        this.error.set(this.mensajeError(error));
      },
    });
  }

  private cargarProfesionales(sucursalId: number): void {
    this.service.getProfesionales(sucursalId).subscribe({
      next: (response) => this.profesionales.set(response.data.profesionales ?? []),
      error: () => this.profesionales.set([]),
    });
  }

  siguiente(): void {
    if (this.saving()) {
      return;
    }

    if (this.paso() === 1) {
      if (this.origen() === null) {
        return;
      }

      this.cargarPreview(true);
      return;
    }

    if (this.paso() === 2) {
      this.paso.set(this.modo() === 'regenerar' ? 4 : 3);
      return;
    }

    if (this.paso() === 3) {
      this.paso.set(4);
    }
  }

  anterior(): void {
    if (this.saving()) {
      return;
    }

    if (this.paso() === 2) {
      this.paso.set(1);
      return;
    }

    if (this.paso() === 3) {
      this.paso.set(2);
      return;
    }

    if (this.paso() === 4) {
      this.paso.set(this.modo() === 'regenerar' ? 2 : 3);
    }
  }

  cambiarProfesional(value: string): void {
    if (value === 'origen') {
      this.usarProfesionalOrigen.set(true);
      this.profesionalId.set(null);
    } else if (value === 'sin_asignar') {
      this.usarProfesionalOrigen.set(false);
      this.profesionalId.set(null);
    } else {
      this.usarProfesionalOrigen.set(false);
      this.profesionalId.set(Number(value));
    }

    this.confirmarAdvertencias.set(false);
    this.cargarPreview(false);
  }

  cargarPreview(avanzar: boolean): void {
    const origen = this.origen();

    if (origen === null || this.previewLoading()) {
      return;
    }

    this.previewLoading.set(true);
    this.error.set(null);

    const request =
      this.modo() === 'regenerar'
        ? this.service.previewRegeneracion(this.tipo(), origen.id)
        : this.service.previewGeneracion(this.tipo(), origen.id, {
            usar_profesional_origen: this.usarProfesionalOrigen(),
            profesional_id: this.profesionalId(),
          });

    request.subscribe({
      next: (response) => {
        this.previewLoading.set(false);
        this.confirmarAdvertencias.set(false);

        if (this.modo() === 'regenerar') {
          this.previewRegeneracion.set(response.data as PreviewRegeneracionData);
        } else {
          this.previewGeneracion.set(response.data as PreviewGeneracionData);
        }

        if (avanzar) {
          this.paso.set(2);
        }
      },
      error: (error: ProgramacionApiError) => {
        this.previewLoading.set(false);
        this.error.set(this.mensajeError(error));
      },
    });
  }

  confirmar(): void {
    const origen = this.origen();

    if (origen === null || this.saving() || !this.puedeConfirmar()) {
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    const payload = {
      clave_idempotencia: this.claveIdempotencia(),
      confirmar_advertencias: this.confirmarAdvertencias(),
      usar_profesional_origen: this.usarProfesionalOrigen(),
      profesional_id: this.usarProfesionalOrigen() ? null : this.profesionalId(),
    };

    const request =
      this.modo() === 'regenerar'
        ? this.service.regenerar(this.tipo(), origen.id, payload)
        : this.service.generar(this.tipo(), origen.id, payload);

    request.subscribe({
      next: (response) => {
        this.saving.set(false);
        this.resultado.set(response.data);
        this.refresh.notificarCambio();
        this.toast.success(
          this.modo() === 'regenerar'
            ? this.texts.successRegenerated
            : this.texts.successGenerated,
          `${this.texts.created}: ${response.data.cantidad_creados}`,
        );
        this.completed.emit(response.data);
      },
      error: (error: ProgramacionApiError) => {
        this.saving.set(false);
        this.error.set(this.mensajeError(error));

        if (error.status === 409 || error.status === 422) {
          this.cargarPreview(false);
        }
      },
    });
  }

  cerrar(): void {
    if (this.saving()) {
      return;
    }

    this.closed.emit();
  }

  finalizar(): void {
    this.closed.emit();
  }

  diaSemana(fecha: string): string {
    const date = new Date(`${fecha}T00:00:00`);
    const dia = date.getDay() === 0 ? 7 : date.getDay();

    return DIA_SEMANA_LABEL[dia] ?? '';
  }

  diasLegibles(dias: number[]): string {
    return dias.map((dia) => DIA_SEMANA_LABEL[dia] ?? String(dia)).join(', ');
  }

  fechaLegible(fecha: string): string {
    const [anio, mes, dia] = fecha.split('-');

    return `${dia}/${mes}/${anio}`;
  }

  imprimirPreview(): void {
    this.document.body.classList.add('programacion-printing');
    window.print();
    this.document.body.classList.remove('programacion-printing');
  }

  estadoFilaLabel(estado: string): string {
    switch (estado) {
      case 'generable':
        return this.texts.willGenerate;
      case 'feriado':
        return this.texts.holiday;
      case 'pasada':
        return this.texts.past;
      case 'omitida':
        return this.texts.omitted;
      default:
        return estado;
    }
  }

  estadoFilaClase(estado: string): string {
    switch (estado) {
      case 'generable':
        return 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400';
      case 'feriado':
        return 'bg-highlight-50 text-highlight-700 dark:bg-highlight-500/10 dark:text-highlight-400';
      case 'pasada':
        return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300';
      case 'omitida':
        return 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400';
      default:
        return 'bg-gray-100 text-gray-600';
    }
  }

  estadoTurnoClase(estado: string): string {
    switch (estado) {
      case 'programado':
        return 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400';
      case 'asistio':
        return 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400';
      case 'falto':
        return 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400';
      case 'cancelado':
        return 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400';
      default:
        return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300';
    }
  }

  private mensajeError(error: ProgramacionApiError): string {
    switch (error.code) {
      case 'BRANCH_ACCESS_DENIED':
        return this.texts.noBranch;
      case 'PATRON_NO_DISPONIBLE':
        return this.texts.patternUnavailable;
      case 'TURNOS_YA_GENERADOS':
        return 'La entidad ya tiene turnos generados. Usá Regenerar para reemplazar los futuros programados.';
      case 'CLAVE_IDEMPOTENCIA_REUTILIZADA':
        return this.texts.idempotencyReused;
      case 'ADVERTENCIAS_NO_CONFIRMADAS':
        return 'Hay advertencias nuevas. Revisá la previsualización y confirmalas para continuar.';
      case 'GENERACION_BLOQUEADA':
        return 'La generación quedó bloqueada por un conflicto. Revisá la previsualización actualizada.';
      case 'ORIGEN_NO_GENERABLE':
        return error.message;
      default:
        return error.message || this.texts.saveError;
    }
  }

  private nuevaClave(): string {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
      return crypto.randomUUID();
    }

    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (caracter) => {
      const aleatorio = (Math.random() * 16) | 0;
      const valor = caracter === 'x' ? aleatorio : (aleatorio & 0x3) | 0x8;

      return valor.toString(16);
    });
  }
}
