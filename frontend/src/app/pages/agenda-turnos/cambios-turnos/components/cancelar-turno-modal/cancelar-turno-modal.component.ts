import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { ToastService } from '../../../../../core/services/toast.service';
import { AlertComponent } from '../../../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../../../../shared/components/ui/modal/modal.component';
import { SelectFieldComponent } from '../../../../../shared/components/ui/select/select-field.component';
import { TextareaDirective } from '../../../../../shared/components/ui/textarea/textarea.directive';
import {
  CAMBIOS_TEXTS,
  fechaLegible,
  type CambiosApiError,
  type CancelacionResultado,
  type MotivoTurno,
  type PreviewCancelacion,
  type TurnoCambioDetalle,
  type WhatsappCambio,
} from '../../cambios-turnos.models';
import { CambiosTurnosService } from '../../cambios-turnos.service';

@Component({
  selector: 'app-cancelar-turno-modal',
  imports: [
    AlertComponent,
    ButtonComponent,
    ModalComponent,
    SelectFieldComponent,
    TextareaDirective,
  ],
  templateUrl: './cancelar-turno-modal.component.html',
})
export class CancelarTurnoModalComponent {
  private readonly service = inject(CambiosTurnosService);
  private readonly toast = inject(ToastService);

  readonly open = input(false);
  readonly turnoId = input<number | null>(null);

  readonly closed = output<void>();
  readonly completed = output<CancelacionResultado>();

  readonly texts = CAMBIOS_TEXTS;
  readonly fechaLegible = fechaLegible;

  readonly detalle = signal<TurnoCambioDetalle | null>(null);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);
  readonly preview = signal<PreviewCancelacion | null>(null);
  readonly previewLoading = signal(false);
  readonly saving = signal(false);
  readonly resultado = signal<CancelacionResultado | null>(null);
  readonly whatsapp = signal<WhatsappCambio | null>(null);
  readonly motivoId = signal('');
  readonly motivoTexto = signal('');

  readonly turno = computed(() => this.detalle()?.turno ?? null);
  readonly motivos = computed<MotivoTurno[]>(() => this.detalle()?.motivos.cancelar ?? []);
  readonly motivoSeleccionado = computed(
    () => this.motivos().find((motivo) => String(motivo.id) === this.motivoId()) ?? null,
  );
  readonly requiereTexto = computed(() => this.motivoSeleccionado()?.requiere_texto === true);
  readonly motivoOpciones = computed(() =>
    this.motivos().map((motivo) => ({ value: String(motivo.id), label: motivo.nombre })),
  );
  readonly puedeConfirmar = computed(() => this.preview() !== null && !this.saving());

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

    this.service.getTurno(id).subscribe({
      next: (response) => {
        this.loading.set(false);
        this.detalle.set(response.data);
        this.motivoId.set(
          response.data.motivos.cancelar[0]
            ? String(response.data.motivos.cancelar[0].id)
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

    if (this.motivoId() === '') {
      this.error.set(this.texts.reasonRequired);
      return;
    }

    if (this.requiereTexto() && this.motivoTexto().trim() === '') {
      this.error.set(this.texts.otherRequired);
      return;
    }

    this.previewLoading.set(true);
    this.error.set(null);

    this.service.previewCancelacion(turno.id, this.payload()).subscribe({
      next: (response) => {
        this.previewLoading.set(false);
        this.preview.set(response.data);
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

    this.service.cancelar(turno.id, this.payload()).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.resultado.set(response.data);
        this.toast.success(this.texts.successCancel, this.texts.cancelTitle);
        this.completed.emit(response.data);
        this.cargarWhatsapp(turno.id);
      },
      error: (error: CambiosApiError) => {
        this.saving.set(false);
        this.error.set(error.message);
      },
    });
  }

  private cargarWhatsapp(turnoId: number): void {
    this.service.getWhatsapp(turnoId, 'cancelado').subscribe({
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
      motivo_id: Number(this.motivoId()),
      motivo_texto: this.motivoTexto().trim() === '' ? null : this.motivoTexto().trim(),
      estado_esperado: this.turno()?.estado,
    };
  }
}
