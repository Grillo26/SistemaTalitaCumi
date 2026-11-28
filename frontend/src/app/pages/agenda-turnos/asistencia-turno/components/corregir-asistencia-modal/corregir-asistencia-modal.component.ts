import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { AlertComponent } from '../../../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../../../../shared/components/ui/modal/modal.component';
import { TextareaDirective } from '../../../../../shared/components/ui/textarea/textarea.directive';
import {
  ASISTENCIA_TEXTS,
  ESTADO_ASISTENCIA_LABEL,
  fechaAsistencia,
  type AsistenciaApiError,
  type AsistenciaResultado,
  type AsistenciaTurnoData,
} from '../../asistencia-turno.models';
import { AsistenciaTurnoService } from '../../asistencia-turno.service';

@Component({
  selector: 'app-corregir-asistencia-modal',
  imports: [AlertComponent, ButtonComponent, ModalComponent, TextareaDirective],
  templateUrl: './corregir-asistencia-modal.component.html',
})
export class CorregirAsistenciaModalComponent {
  private readonly service = inject(AsistenciaTurnoService);

  readonly open = input(false);
  readonly data = input<AsistenciaTurnoData | null>(null);

  readonly closed = output<void>();
  readonly completed = output<AsistenciaResultado>();

  readonly texts = ASISTENCIA_TEXTS;
  readonly fecha = fechaAsistencia;
  readonly estadoLabel = ESTADO_ASISTENCIA_LABEL;

  readonly motivo = signal('');
  readonly confirmar = signal(false);
  readonly saving = signal(false);
  readonly error = signal<string | null>(null);

  readonly correccion = computed(() => this.data()?.correccion ?? null);
  readonly destino = computed(() => this.correccion()?.destino ?? null);
  readonly requiereMotivo = computed(() => this.correccion()?.requiere_motivo === true);
  readonly puedeConfirmar = computed(
    () =>
      !this.saving() &&
      this.destino() !== null &&
      this.confirmar() &&
      (!this.requiereMotivo() || this.motivo().trim() !== ''),
  );

  constructor() {
    effect(() => {
      if (this.open()) {
        this.motivo.set('');
        this.confirmar.set(false);
        this.saving.set(false);
        this.error.set(null);
      }
    });
  }

  confirmarCorreccion(): void {
    const data = this.data();

    if (data === null || !this.puedeConfirmar()) {
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    this.service.corregir(
      data.turno.id,
      this.destino() as 'asistio' | 'falto',
      data.turno.version,
      this.motivo().trim() === '' ? null : this.motivo().trim(),
    ).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.completed.emit(response.data);
      },
      error: (error: AsistenciaApiError) => {
        this.saving.set(false);
        this.error.set(error.message);
      },
    });
  }

  cerrar(): void {
    if (!this.saving()) {
      this.closed.emit();
    }
  }
}
