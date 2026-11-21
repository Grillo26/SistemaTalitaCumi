import {
  Component,
  DestroyRef,
  EventEmitter,
  Input,
  OnChanges,
  Output,
  SimpleChanges,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { InputDirective } from '../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../shared/components/ui/modal/modal.component';
import { SelectFieldComponent } from '../../shared/components/ui/select/select-field.component';
import {
  ContactoEmergencia,
  PACIENTES_TEXTS,
  PacientesApiError,
  TutorParentescoOption,
} from './pacientes.models';
import { PacientesService } from './pacientes.service';

@Component({
  selector: 'app-contacto-emergencia-modal',
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    AlertComponent,
    ButtonComponent,
    InputDirective,
    SelectFieldComponent,
  ],
  templateUrl: './contacto-emergencia-modal.component.html',
})
export class ContactoEmergenciaModalComponent implements OnChanges {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(PacientesService);
  private readonly destroyRef = inject(DestroyRef);

  @Input({ required: true }) open = false;
  @Input({ required: true }) pacienteId = 0;
  @Input() contacto: ContactoEmergencia | null = null;
  @Input({ required: true }) parentescos: TutorParentescoOption[] = [];

  @Output() closed = new EventEmitter<void>();
  @Output() saved = new EventEmitter<ContactoEmergencia>();

  readonly texts = PACIENTES_TEXTS;
  readonly saving = signal(false);
  readonly error = signal<PacientesApiError | null>(null);

  readonly form = this.fb.nonNullable.group({
    nombre_completo: ['', [Validators.required, Validators.maxLength(200)]],
    parentesco_id: ['', [Validators.required]],
    telefono: ['', [Validators.required, Validators.maxLength(30)]],
  });

  get isEdit(): boolean {
    return this.contacto !== null;
  }

  get parentescoOptions(): { value: string; label: string }[] {
    return this.parentescos.map((parentesco) => ({
      value: String(parentesco.id),
      label: parentesco.nombre,
    }));
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['open']?.currentValue === true) {
      this.reset();
    }
  }

  submit(): void {
    if (this.saving()) {
      return;
    }

    this.error.set(null);
    this.form.markAllAsTouched();

    if (this.form.invalid) {
      this.error.set({
        status: 422,
        message: 'Completá nombre, parentesco y teléfono del contacto.',
      });
      return;
    }

    const value = this.form.getRawValue();
    const payload = {
      nombre_completo: value.nombre_completo.trim(),
      parentesco_id: Number(value.parentesco_id),
      telefono: value.telefono.trim(),
    };

    this.saving.set(true);

    const request = this.isEdit && this.contacto
      ? this.service.updateContactoEmergencia(this.pacienteId, this.contacto.id, {
          ...payload,
          version: this.contacto.version,
        })
      : this.service.createContactoEmergencia(this.pacienteId, payload);

    request.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.saved.emit(response.data);
      },
      error: (apiError: PacientesApiError) => {
        this.saving.set(false);

        if (apiError.code === 'CONTACT_EMERGENCY_VERSION_CONFLICT') {
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

  private reset(): void {
    this.error.set(null);

    this.form.reset({
      nombre_completo: this.contacto?.nombre_completo ?? '',
      parentesco_id: this.contacto?.parentesco_id
        ? String(this.contacto.parentesco_id)
        : '',
      telefono: this.contacto?.telefono ?? '',
    });
  }
}
