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
import { SelectFieldComponent } from '../../shared/components/ui/select/select-field.component';
import { TextareaDirective } from '../../shared/components/ui/textarea/textarea.directive';
import { PacienteDateInputComponent } from './paciente-date-input.component';
import {
  Consentimiento,
  ConsentimientoTipo,
  ConsentimientoTutorResumen,
  FICHA_INGRESO_TEXTS,
  FichaIngresoApiError,
} from './ficha-ingreso.models';
import { FichaIngresoService } from './ficha-ingreso.service';

@Component({
  selector: 'app-consentimiento-form-modal',
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    AlertComponent,
    ButtonComponent,
    SelectFieldComponent,
    TextareaDirective,
    PacienteDateInputComponent,
  ],
  templateUrl: './consentimiento-form-modal.component.html',
})
export class ConsentimientoFormModalComponent implements OnChanges {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(FichaIngresoService);
  private readonly destroyRef = inject(DestroyRef);

  @Input({ required: true }) open = false;
  @Input({ required: true }) pacienteId = 0;
  @Input({ required: true }) fichaId = 0;
  @Input() tipos: ConsentimientoTipo[] = [];
  @Input() tutores: ConsentimientoTutorResumen[] = [];

  @Output() closed = new EventEmitter<void>();
  @Output() saved = new EventEmitter<Consentimiento>();

  readonly texts = FICHA_INGRESO_TEXTS;
  readonly saving = signal(false);
  readonly error = signal<FichaIngresoApiError | null>(null);
  private wasOpen = false;

  readonly form = this.fb.nonNullable.group({
    tipo_id: ['', [Validators.required]],
    tutor_id: ['', [Validators.required]],
    fecha: ['', [Validators.required]],
    resultado: ['otorgado' as 'otorgado' | 'negado', [Validators.required]],
    motivo: [''],
  });

  get tipoOptions(): { value: string; label: string }[] {
    return this.tipos.map((tipo) => ({ value: String(tipo.id), label: tipo.nombre }));
  }

  get tutorOptions(): { value: string; label: string }[] {
    return this.tutores.map((tutor) => ({
      value: String(tutor.id),
      label: tutor.parentesco
        ? `${tutor.nombre_completo} · ${tutor.parentesco}`
        : tutor.nombre_completo,
    }));
  }

  readonly resultadoOptions = [
    { value: 'otorgado', label: this.texts.consentGranted },
    { value: 'negado', label: this.texts.consentDenied },
  ];

  ngOnChanges(): void {
    if (this.open && !this.wasOpen) {
      this.resetForm();
    }

    this.wasOpen = this.open;
  }

  submit(): void {
    if (this.saving()) {
      return;
    }

    this.form.markAllAsTouched();
    this.error.set(null);

    if (this.form.invalid) {
      this.error.set({
        status: 422,
        message: this.texts.consentTypeRequired,
        errors: {
          consentimiento_tipo_id: this.form.controls.tipo_id.invalid
            ? [this.texts.consentTypeRequired]
            : [],
          tutor_id: this.form.controls.tutor_id.invalid ? [this.texts.consentTutorRequired] : [],
          fecha: this.form.controls.fecha.invalid ? [this.texts.consentDateRequired] : [],
        },
      });
      return;
    }

    const value = this.form.getRawValue();
    this.saving.set(true);

    this.service
      .registerConsentimiento(this.pacienteId, this.fichaId, {
        consentimiento_tipo_id: Number(value.tipo_id),
        tutor_id: Number(value.tutor_id),
        resultado: value.resultado,
        fecha: value.fecha,
        motivo: value.motivo.trim() || null,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.saving.set(false);
          this.saved.emit(response.data);
        },
        error: (apiError: FichaIngresoApiError) => {
          this.saving.set(false);
          this.error.set(this.normalize(apiError));
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

  private resetForm(): void {
    this.form.reset({
      tipo_id: '',
      tutor_id: '',
      fecha: this.today(),
      resultado: 'otorgado',
      motivo: '',
    });
    this.error.set(null);
  }

  private today(): string {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }

  private normalize(error: FichaIngresoApiError): FichaIngresoApiError {
    return matchError(error, this.texts);
  }
}

function matchError(
  error: FichaIngresoApiError,
  texts: typeof FICHA_INGRESO_TEXTS,
): FichaIngresoApiError {
  switch (error.code) {
    case 'CONSENT_ALREADY_ACTIVE':
      return { ...error, message: texts.consentDuplicate };
    case 'CONSENT_TYPE_INACTIVE':
      return { ...error, message: texts.consentTypeInactive };
    case 'CONSENT_TUTOR_REQUIRED':
      return { ...error, message: texts.consentsNoTutors };
    default:
      return error;
  }
}
