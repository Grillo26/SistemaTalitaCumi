import { Component, EventEmitter, Input, Output, computed, inject, signal } from '@angular/core';
import { DestroyRef } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../shared/components/ui/modal/modal.component';
import { SelectFieldComponent } from '../../shared/components/ui/select/select-field.component';
import { TextareaDirective } from '../../shared/components/ui/textarea/textarea.directive';
import {
  ESTADO_OPTIONS,
  PACIENTES_TEXTS,
  Paciente,
  PacienteEstado,
  PacientesApiError,
} from './pacientes.models';
import { PacientesService } from './pacientes.service';

@Component({
  selector: 'app-paciente-status-modal',
  imports: [
    ModalComponent,
    AlertComponent,
    ButtonComponent,
    SelectFieldComponent,
    TextareaDirective,
  ],
  templateUrl: './paciente-status-modal.component.html',
})
export class PacienteStatusModalComponent {
  private readonly service = inject(PacientesService);
  private readonly destroyRef = inject(DestroyRef);

  @Input({ required: true }) open = false;
  @Input({ required: true }) paciente!: Paciente;

  @Output() closed = new EventEmitter<void>();
  @Output() saved = new EventEmitter<Paciente>();

  readonly texts = PACIENTES_TEXTS;
  readonly target = signal<PacienteEstado | ''>('');
  readonly motivo = signal('');
  readonly saving = signal(false);
  readonly error = signal<PacientesApiError | null>(null);

  readonly transitionOptions = computed(() => {
    const allowed: Record<PacienteEstado, PacienteEstado[]> = {
      activo: ['en_pausa', 'retirado'],
      en_pausa: ['activo', 'retirado'],
      retirado: ['activo'],
    };

    return ESTADO_OPTIONS.filter((option) => allowed[this.paciente.estado].includes(option.value));
  });

  readonly requiresReason = computed(() => {
    const target = this.target();

    return target === 'retirado' || this.paciente.estado === 'retirado' || target === 'en_pausa';
  });

  submit(): void {
    if (this.saving()) {
      return;
    }

    this.error.set(null);

    if (this.target() === '') {
      this.error.set({ status: 422, message: 'Seleccioná el estado destino.' });
      return;
    }

    if (this.requiresReason() && this.motivo().trim() === '') {
      this.error.set({
        status: 422,
        message: 'El motivo es obligatorio para este cambio de estado.',
        errors: { motivo: ['El motivo es obligatorio para este cambio de estado.'] },
      });
      return;
    }

    this.saving.set(true);

    this.service
      .changeStatus(this.paciente.id, {
        estado: this.target() as PacienteEstado,
        motivo: this.motivo().trim() || null,
        version: this.paciente.version,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.saving.set(false);
          this.saved.emit(response.data);
        },
        error: (apiError: PacientesApiError) => {
          this.saving.set(false);

          if (apiError.code === 'PATIENT_VERSION_CONFLICT') {
            this.error.set({ ...apiError, message: this.texts.versionConflict });
            return;
          }

          this.error.set(apiError);
        },
      });
  }

  close(): void {
    if (!this.saving()) {
      this.closed.emit();
    }
  }

  fieldError(field: string): string | null {
    return this.error()?.errors?.[field]?.[0] ?? null;
  }
}
