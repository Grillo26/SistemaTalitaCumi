import { Component, DestroyRef, EventEmitter, Input, Output, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../shared/components/ui/modal/modal.component';
import { SelectFieldComponent } from '../../shared/components/ui/select/select-field.component';
import { TextareaDirective } from '../../shared/components/ui/textarea/textarea.directive';
import {
  PacientesApiError,
  TUTORES_TEXTS,
  Tutor,
} from './pacientes.models';
import { PacientesService } from './pacientes.service';

@Component({
  selector: 'app-tutor-status-modal',
  imports: [
    ModalComponent,
    AlertComponent,
    ButtonComponent,
    SelectFieldComponent,
    TextareaDirective,
  ],
  templateUrl: './tutor-status-modal.component.html',
})
export class TutorStatusModalComponent {
  private readonly service = inject(PacientesService);
  private readonly destroyRef = inject(DestroyRef);

  @Input({ required: true }) open = false;
  @Input({ required: true }) tutor!: Tutor;

  @Output() closed = new EventEmitter<void>();
  @Output() saved = new EventEmitter<Tutor>();

  readonly texts = TUTORES_TEXTS;
  readonly target = signal<'activo' | 'inactivo'>('activo');
  readonly motivo = signal('');
  readonly saving = signal(false);
  readonly error = signal<PacientesApiError | null>(null);

  readonly statusOptions = [
    { value: 'activo', label: this.texts.active },
    { value: 'inactivo', label: this.texts.inactive },
  ];

  ngOnChanges(): void {
    if (this.open) {
      this.target.set(this.tutor.activo ? 'inactivo' : 'activo');
      this.motivo.set('');
      this.error.set(null);
    }
  }

  submit(): void {
    if (this.saving()) {
      return;
    }

    this.error.set(null);

    if (this.target() === 'inactivo' && this.motivo().trim() === '') {
      this.error.set({
        status: 422,
        message: this.texts.statusReasonRequired,
        errors: { motivo: [this.texts.statusReasonRequired] },
      });
      return;
    }

    this.saving.set(true);

    this.service
      .changeTutorStatus(this.tutor.id, {
        activo: this.target() === 'activo',
        motivo: this.motivo().trim() || null,
        version: this.tutor.version,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.saving.set(false);
          this.saved.emit(response.data);
        },
        error: (apiError: PacientesApiError) => {
          this.saving.set(false);

          if (apiError.code === 'TUTOR_VERSION_CONFLICT') {
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
