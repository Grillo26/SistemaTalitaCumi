import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { ToastService } from '../../../../../core/services/toast.service';
import { AlertComponent } from '../../../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../../../../shared/components/ui/modal/modal.component';
import { SelectFieldComponent } from '../../../../../shared/components/ui/select/select-field.component';
import { TextareaDirective } from '../../../../../shared/components/ui/textarea/textarea.directive';
import {
  CAMBIOS_TEXTS,
  fechaLegible,
  type CambiosApiError,
  type HallazgoCambio,
  type MotivoTurno,
  type PreviewReprogramacion,
  type ReprogramacionResultado,
  type TurnoCambioDetalle,
  type WhatsappCambio,
} from '../../cambios-turnos.models';
import { CambiosTurnosService } from '../../cambios-turnos.service';

@Component({
  selector: 'app-reprogramar-turno-modal',
  imports: [
    AlertComponent,
    ButtonComponent,
    InputDirective,
    ModalComponent,
    SelectFieldComponent,
    TextareaDirective,
  ],
  templateUrl: './reprogramar-turno-modal.component.html',
})
export class ReprogramarTurnoModalComponent {
  private readonly service = inject(CambiosTurnosService);
  private readonly toast = inject(ToastService);

  readonly open = input(false);
  readonly turnoId = input<number | null>(null);

  readonly closed = output<void>();
  readonly completed = output<ReprogramacionResultado>();

  readonly texts = CAMBIOS_TEXTS;
  readonly fechaLegible = fechaLegible;

  readonly detalle = signal<TurnoCambioDetalle | null>(null);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);
  readonly preview = signal<PreviewReprogramacion | null>(null);
  readonly previewLoading = signal(false);
  readonly saving = signal(false);
  readonly resultado = signal<ReprogramacionResultado | null>(null);
  readonly whatsapp = signal<WhatsappCambio | null>(null);

  readonly motivoId = signal('');
  readonly motivoTexto = signal('');
  readonly fecha = signal('');
  readonly horaInicio = signal('');
  readonly profesionalId = signal('sin_asignar');
  readonly salaId = signal('sin_sala');
  readonly confirmarAdvertencias = signal(false);

  readonly turno = computed(() => this.detalle()?.turno ?? null);
  readonly esSalaGrupal = computed(() => this.detalle()?.origen?.tipo === 'inscripcion_sala');
  readonly esDiagnostico = computed(() => this.detalle()?.turno.atencion_diagnostico_id != null);
  readonly motivos = computed<MotivoTurno[]>(() => this.detalle()?.motivos.reprogramar ?? []);
  readonly motivoSeleccionado = computed(
    () => this.motivos().find((motivo) => String(motivo.id) === this.motivoId()) ?? null,
  );
  readonly requiereTexto = computed(() => this.motivoSeleccionado()?.requiere_texto === true);
  readonly profesionalOpciones = computed(() => [
    { value: 'sin_asignar', label: this.texts.unassigned },
    ...(this.detalle()?.profesionales ?? []).map((profesional) => ({
      value: String(profesional.id),
      label: profesional.nombre,
    })),
  ]);
  readonly salaOpciones = computed(() => {
    if (this.esDiagnostico()) {
      return [{ value: 'sin_sala', label: this.texts.sinSala }];
    }

    return [
      ...(this.esSalaGrupal() ? [] : [{ value: 'sin_sala', label: this.texts.sinSala }]),
      ...(this.detalle()?.salas ?? []).map((sala) => ({
        value: String(sala.id),
        label: sala.nombre,
      })),
    ];
  });
  readonly motivoOpciones = computed(() =>
    this.motivos().map((motivo) => ({ value: String(motivo.id), label: motivo.nombre })),
  );
  readonly bloqueos = computed<HallazgoCambio[]>(() =>
    (this.preview()?.hallazgos ?? []).filter((hallazgo) => hallazgo.severidad === 'bloqueo'),
  );
  readonly advertencias = computed<HallazgoCambio[]>(() =>
    (this.preview()?.hallazgos ?? []).filter((hallazgo) => hallazgo.severidad === 'advertencia'),
  );
  readonly puedeConfirmar = computed(() => {
    const preview = this.preview();

    if (preview === null || this.saving()) {
      return false;
    }

    if (this.bloqueos().length > 0) {
      return false;
    }

    return (
      this.advertencias().length === 0 ||
      this.confirmarAdvertencias()
    );
  });

  constructor() {
    effect(() => {
      const abierto = this.open();
      const id = this.turnoId();

      if (abierto && id !== null) {
        this.cargar(id);
      }
    });
  }

  private cargar(id: number): void {
    this.loading.set(true);
    this.error.set(null);
    this.preview.set(null);
    this.resultado.set(null);
    this.whatsapp.set(null);
    this.confirmarAdvertencias.set(false);

    this.service.getTurno(id).subscribe({
      next: (response) => {
        this.loading.set(false);
        this.detalle.set(response.data);
        const turno = response.data.turno;
        this.fecha.set(turno.fecha);
        this.horaInicio.set(turno.hora_inicio);
        this.profesionalId.set(
          turno.profesional ? String(turno.profesional.id) : 'sin_asignar',
        );
        this.salaId.set(
          response.data.turno.atencion_diagnostico_id != null
            ? 'sin_sala'
            : (turno.sala ? String(turno.sala.id) : 'sin_sala'),
        );
        this.motivoId.set(
          response.data.motivos.reprogramar[0]
            ? String(response.data.motivos.reprogramar[0].id)
            : '',
        );
        this.motivoTexto.set('');
      },
      error: (error: CambiosApiError) => {
        this.loading.set(false);
        this.error.set(error.message);
      },
    });
  }

  previsualizar(): void {
    const turno = this.turno();

    if (turno === null || this.previewLoading()) {
      return;
    }

    if (this.motivoId() === '' || this.fecha() === '' || this.horaInicio() === '') {
      this.error.set('Seleccioná el motivo, la nueva fecha y la hora.');
      return;
    }

    if (this.requiereTexto() && this.motivoTexto().trim() === '') {
      this.error.set(this.texts.otherRequired);
      return;
    }

    this.previewLoading.set(true);
    this.error.set(null);

    this.service.previewReprogramacion(turno.id, this.payload()).subscribe({
      next: (response) => {
        this.previewLoading.set(false);
        this.preview.set(response.data);
        this.confirmarAdvertencias.set(false);
      },
      error: (error: CambiosApiError) => {
        this.previewLoading.set(false);
        this.error.set(error.message);
      },
    });
  }

  confirmar(): void {
    const turno = this.turno();

    if (turno === null || !this.puedeConfirmar()) {
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    this.service.reprogramar(turno.id, {
      ...this.payload(),
      confirmar_advertencias: this.advertencias().length > 0 && this.confirmarAdvertencias(),
    }).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.resultado.set(response.data);
        this.toast.success(this.texts.successReprogram, this.texts.reprogramTitle);
        this.completed.emit(response.data);
        this.cargarWhatsapp(response.data.turno_nuevo.id);
      },
      error: (error: CambiosApiError) => {
        this.saving.set(false);
        this.error.set(error.message);
      },
    });
  }

  private cargarWhatsapp(turnoNuevoId: number): void {
    this.service.getWhatsapp(turnoNuevoId, 'reprogramado').subscribe({
      next: (response) => this.whatsapp.set(response.data),
      error: () => this.whatsapp.set({ disponible: false, mensaje: this.texts.whatsappUnavailable }),
    });
  }

  abrirWhatsapp(): void {
    const url = this.whatsapp()?.whatsapp_url;

    if (url) {
      window.open(url, '_blank', 'noopener');
    }
  }

  cerrar(): void {
    if (!this.saving()) {
      this.closed.emit();
    }
  }

  private payload(): Record<string, unknown> {
    return {
      fecha: this.fecha(),
      hora_inicio: this.horaInicio(),
      motivo_id: Number(this.motivoId()),
      motivo_texto: this.motivoTexto().trim() === '' ? null : this.motivoTexto().trim(),
      estado_esperado: this.turno()?.estado,
      profesional_id:
        this.profesionalId() === 'sin_asignar' ? null : Number(this.profesionalId()),
      sala_id: this.salaId() === 'sin_sala' ? null : Number(this.salaId()),
      confirmar_advertencias:
        this.advertencias().length > 0 && this.confirmarAdvertencias(),
    };
  }
}
