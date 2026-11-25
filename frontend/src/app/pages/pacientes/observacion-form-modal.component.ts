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
  FichaIngresoApiError,
  FichaIngresoObservacion,
  FICHA_INGRESO_TEXTS,
} from './ficha-ingreso.models';
import { FichaIngresoService } from './ficha-ingreso.service';

@Component({
  selector: 'app-observacion-form-modal',
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    AlertComponent,
    ButtonComponent,
    SelectFieldComponent,
    TextareaDirective,
    PacienteDateInputComponent,
  ],
  templateUrl: './observacion-form-modal.component.html',
})
export class ObservacionFormModalComponent implements OnChanges {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(FichaIngresoService);
  private readonly destroyRef = inject(DestroyRef);

  @Input({ required: true }) open = false;
  @Input({ required: true }) pacienteId = 0;
  @Input({ required: true }) fichaId = 0;
  @Input() observaciones: FichaIngresoObservacion[] = [];

  @Output() closed = new EventEmitter<void>();
  @Output() saved = new EventEmitter<FichaIngresoObservacion>();

  readonly texts = FICHA_INGRESO_TEXTS;
  readonly saving = signal(false);
  readonly error = signal<FichaIngresoApiError | null>(null);
  private wasOpen = false;

  readonly form = this.fb.nonNullable.group({
    fecha: ['', [Validators.required]],
    texto: ['', [Validators.required, Validators.maxLength(4000)]],
    referencia: [''],
  });

  get referenciaOptions(): { value: string; label: string }[] {
    return [
      { value: '', label: this.texts.observationReferenceNone },
      ...this.observaciones.map((observacion) => ({
        value: String(observacion.id),
        label: `${this.formatDate(observacion.fecha)} · ${
          observacion.texto ? this.truncate(observacion.texto) : this.texts.observationProtected
        }`,
      })),
    ];
  }

  ngOnChanges(): void {
    if (this.open && !this.wasOpen) {
      this.form.reset({
        fecha: this.today(),
        texto: '',
        referencia: '',
      });
      this.error.set(null);
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
        message: this.texts.observationRequired,
        errors: {
          fecha: this.form.controls.fecha.invalid ? [this.texts.observationDate] : [],
          texto: this.form.controls.texto.invalid ? [this.texts.observationText] : [],
        },
      });
      return;
    }

    const value = this.form.getRawValue();
    this.saving.set(true);

    this.service
      .createObservacion(this.pacienteId, this.fichaId, {
        fecha: value.fecha,
        texto: value.texto.trim(),
        observacion_referencia_id: value.referencia ? Number(value.referencia) : null,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.saving.set(false);
          this.saved.emit(response.data);
        },
        error: (apiError: FichaIngresoApiError) => {
          this.saving.set(false);
          this.error.set(
            apiError.code === 'FICHA_SENSITIVE_FIELDS_FORBIDDEN'
              ? { ...apiError, message: this.texts.observationProtected }
              : apiError,
          );
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

  private today(): string {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }

  private formatDate(value: string): string {
    const [year, month, day] = value.slice(0, 10).split('-');

    return year && month && day ? `${day}/${month}/${year}` : value;
  }

  private truncate(value: string): string {
    return value.length > 60 ? `${value.slice(0, 57)}...` : value;
  }
}
