import {
  Component,
  DestroyRef,
  EventEmitter,
  Input,
  OnChanges,
  Output,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../shared/components/ui/modal/modal.component';
import { TextareaDirective } from '../../shared/components/ui/textarea/textarea.directive';
import {
  Consentimiento,
  FICHA_INGRESO_TEXTS,
  FichaIngresoApiError,
} from './ficha-ingreso.models';
import { FichaIngresoService } from './ficha-ingreso.service';

@Component({
  selector: 'app-consentimiento-revoke-modal',
  imports: [ReactiveFormsModule, ModalComponent, AlertComponent, ButtonComponent, TextareaDirective],
  templateUrl: './consentimiento-revoke-modal.component.html',
})
export class ConsentimientoRevokeModalComponent implements OnChanges {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(FichaIngresoService);
  private readonly destroyRef = inject(DestroyRef);

  @Input({ required: true }) open = false;
  @Input({ required: true }) pacienteId = 0;
  @Input({ required: true }) fichaId = 0;
  @Input() consentimiento: Consentimiento | null = null;

  @Output() closed = new EventEmitter<void>();
  @Output() revoked = new EventEmitter<Consentimiento>();

  readonly texts = FICHA_INGRESO_TEXTS;
  readonly saving = signal(false);
  readonly error = signal<FichaIngresoApiError | null>(null);
  private wasOpen = false;

  readonly form = this.fb.nonNullable.group({
    motivo: ['', [Validators.required, Validators.maxLength(1000)]],
  });

  ngOnChanges(): void {
    if (this.open && !this.wasOpen) {
      this.form.reset({ motivo: '' });
      this.error.set(null);
    }

    this.wasOpen = this.open;
  }

  submit(): void {
    const consentimiento = this.consentimiento;

    if (consentimiento === null || this.saving()) {
      return;
    }

    this.form.markAllAsTouched();
    this.error.set(null);

    if (this.form.invalid) {
      this.error.set({
        status: 422,
        message: this.texts.consentRevokeReason,
        errors: { motivo: [this.texts.consentRevokeReason] },
      });
      return;
    }

    this.saving.set(true);

    this.service
      .revokeConsentimiento(
        this.pacienteId,
        this.fichaId,
        consentimiento.id,
        this.form.controls.motivo.value.trim(),
      )
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.saving.set(false);
          this.revoked.emit(response.data);
        },
        error: (apiError: FichaIngresoApiError) => {
          this.saving.set(false);

          if (apiError.code === 'CONSENT_NOT_REVOCABLE') {
            this.error.set({ ...apiError, message: this.texts.consentNotRevocable });
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
