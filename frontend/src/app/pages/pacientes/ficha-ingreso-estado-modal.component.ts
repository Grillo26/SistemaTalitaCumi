import { Component, DestroyRef, EventEmitter, Input, OnChanges, Output, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../shared/components/ui/modal/modal.component';
import { TextareaDirective } from '../../shared/components/ui/textarea/textarea.directive';
import {
  FichaIngreso,
  FICHA_INGRESO_TEXTS,
  FichaIngresoApiError,
} from './ficha-ingreso.models';
import { FichaIngresoService } from './ficha-ingreso.service';

export type FichaEstadoAccion = 'archivar' | 'reactivar';

@Component({
  selector: 'app-ficha-ingreso-estado-modal',
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    AlertComponent,
    ButtonComponent,
    TextareaDirective,
  ],
  templateUrl: './ficha-ingreso-estado-modal.component.html',
})
export class FichaIngresoEstadoModalComponent implements OnChanges {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(FichaIngresoService);
  private readonly destroyRef = inject(DestroyRef);

  @Input({ required: true }) open = false;
  @Input({ required: true }) accion: FichaEstadoAccion = 'archivar';
  @Input({ required: true }) pacienteId = 0;
  @Input({ required: true }) ficha!: FichaIngreso;

  @Output() closed = new EventEmitter<void>();
  @Output() saved = new EventEmitter<FichaIngreso>();

  readonly texts = FICHA_INGRESO_TEXTS;
  readonly saving = signal(false);
  readonly error = signal<FichaIngresoApiError | null>(null);

  readonly form = this.fb.nonNullable.group({
    motivo: ['', [Validators.required, Validators.maxLength(1000)]],
  });

  get esArchivar(): boolean {
    return this.accion === 'archivar';
  }

  ngOnChanges(): void {
    if (this.open) {
      this.form.reset({ motivo: '' });
      this.error.set(null);
    }
  }

  submit(): void {
    if (this.saving()) {
      return;
    }

    this.form.markAllAsTouched();

    if (this.form.invalid) {
      this.error.set({
        status: 422,
        message: this.esArchivar ? this.texts.archiveReason : this.texts.reactivateReason,
      });
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    const motivo = this.form.controls.motivo.value.trim();
    const request = this.esArchivar
      ? this.service.archive(this.pacienteId, this.ficha.id, motivo, this.ficha.version)
      : this.service.reactivate(this.pacienteId, this.ficha.id, motivo, this.ficha.version);

    request.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.saved.emit(response.data);
      },
      error: (apiError: FichaIngresoApiError) => {
        this.saving.set(false);

        if (apiError.code === 'FICHA_VERSION_CONFLICT') {
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
