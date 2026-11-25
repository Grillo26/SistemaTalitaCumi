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
import { TextareaDirective } from '../../shared/components/ui/textarea/textarea.directive';
import {
  PacientesApiError,
  TUTORES_TEXTS,
  Tutor,
  TutorCreatePayload,
  TutorOptions,
  TutorUpdatePayload,
} from './pacientes.models';
import { PacientesService } from './pacientes.service';

export type TutorFormMode = 'create' | 'edit';

@Component({
  selector: 'app-tutor-form-modal',
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    AlertComponent,
    ButtonComponent,
    InputDirective,
    SelectFieldComponent,
    TextareaDirective,
  ],
  templateUrl: './tutor-form-modal.component.html',
})
export class TutorFormModalComponent implements OnChanges {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(PacientesService);
  private readonly destroyRef = inject(DestroyRef);

  @Input({ required: true }) open = false;
  @Input({ required: true }) mode: TutorFormMode = 'create';
  @Input() tutor: Tutor | null = null;
  @Input({ required: true }) options!: TutorOptions;
  @Input() identityEditable = true;

  @Output() closed = new EventEmitter<void>();
  @Output() saved = new EventEmitter<Tutor>();

  readonly texts = TUTORES_TEXTS;
  readonly saving = signal(false);
  readonly error = signal<PacientesApiError | null>(null);

  readonly form = this.fb.nonNullable.group({
    nombre_completo: ['', [Validators.required, Validators.maxLength(200)]],
    ci: [''],
    parentesco_id: ['', [Validators.required]],
    telefono: ['', [Validators.required, Validators.maxLength(30)]],
    telefono_alternativo: [''],
    email: [''],
    direccion: [''],
    sucursal_registro_id: [''],
  });

  get isEdit(): boolean {
    return this.mode === 'edit';
  }

  get canEditIdentity(): boolean {
    return !this.isEdit || this.identityEditable;
  }

  get parentescoOptions(): { value: string; label: string }[] {
    return this.options.parentescos.map((parentesco) => ({
      value: String(parentesco.id),
      label: parentesco.nombre,
    }));
  }

  get sucursalOptions(): { value: string; label: string }[] {
    return this.options.sucursales.map((sucursal) => ({
      value: String(sucursal.id),
      label: sucursal.nombre,
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

    if (!this.validate()) {
      return;
    }

    this.saving.set(true);

    const request = this.isEdit && this.tutor
      ? this.service.updateTutor(this.tutor.id, this.buildUpdatePayload())
      : this.service.createTutor(this.buildCreatePayload());

    request.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
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

  private reset(): void {
    this.error.set(null);

    if (this.isEdit && this.tutor) {
      const tutor = this.tutor;

      this.form.reset({
        nombre_completo: tutor.nombre_completo,
        ci: tutor.ci ?? '',
        parentesco_id: tutor.parentesco_id ? String(tutor.parentesco_id) : '',
        telefono: tutor.telefono,
        telefono_alternativo: tutor.telefono_alternativo ?? '',
        email: tutor.email ?? '',
        direccion: tutor.direccion ?? '',
        sucursal_registro_id: tutor.sucursal_registro
          ? String(tutor.sucursal_registro.id)
          : '',
      });

      if (!this.identityEditable) {
        this.form.controls.nombre_completo.disable({ emitEvent: false });
        this.form.controls.ci.disable({ emitEvent: false });
        this.form.controls.parentesco_id.disable({ emitEvent: false });
      } else {
        this.form.controls.nombre_completo.enable({ emitEvent: false });
        this.form.controls.ci.enable({ emitEvent: false });
        this.form.controls.parentesco_id.enable({ emitEvent: false });
      }

      return;
    }

    this.form.enable({ emitEvent: false });
    this.form.reset({
      nombre_completo: '',
      ci: '',
      parentesco_id: '',
      telefono: '',
      telefono_alternativo: '',
      email: '',
      direccion: '',
      sucursal_registro_id: this.options.sucursales.length === 1
        ? String(this.options.sucursales[0].id)
        : '',
    });
  }

  private validate(): boolean {
    const required = this.isEdit
      ? this.identityEditable
        ? ['nombre_completo', 'parentesco_id', 'telefono']
        : ['telefono']
      : ['nombre_completo', 'parentesco_id', 'telefono'];

    if (required.some((field) => this.form.get(field)?.invalid)) {
      this.error.set({
        status: 422,
        message: 'Completá los campos obligatorios del tutor.',
      });
      return false;
    }

    if (!this.isEdit && this.options.sucursales.length > 1
      && this.form.controls.sucursal_registro_id.value === '') {
      this.error.set({
        status: 422,
        message: 'Seleccioná la sucursal de registro del tutor.',
        errors: { sucursal_registro_id: ['Seleccioná la sucursal de registro del tutor.'] },
      });
      return false;
    }

    return true;
  }

  private buildCreatePayload(): TutorCreatePayload {
    const value = this.form.getRawValue();

    return {
      nombre_completo: value.nombre_completo.trim(),
      ci: value.ci.trim() || null,
      telefono: value.telefono.trim(),
      telefono_alternativo: value.telefono_alternativo.trim() || null,
      email: value.email.trim() || null,
      direccion: value.direccion.trim() || null,
      parentesco_id: Number(value.parentesco_id),
      sucursal_registro_id: value.sucursal_registro_id
        ? Number(value.sucursal_registro_id)
        : undefined,
    };
  }

  private buildUpdatePayload(): TutorUpdatePayload {
    const value = this.form.getRawValue();
    const payload: TutorUpdatePayload = {
      telefono: value.telefono.trim(),
      telefono_alternativo: value.telefono_alternativo.trim() || null,
      email: value.email.trim() || null,
      direccion: value.direccion.trim() || null,
      version: this.tutor?.version ?? 1,
    };

    if (this.identityEditable) {
      payload.nombre_completo = value.nombre_completo.trim();
      payload.ci = value.ci.trim() || null;
      payload.parentesco_id = Number(value.parentesco_id);
    }

    return payload;
  }
}
