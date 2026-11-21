import {
  Component,
  DestroyRef,
  EventEmitter,
  Input,
  OnChanges,
  OnInit,
  Output,
  SimpleChanges,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import { ToastService } from '../../core/services/toast.service';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { InputDirective } from '../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../shared/components/ui/modal/modal.component';
import { SelectFieldComponent } from '../../shared/components/ui/select/select-field.component';
import { SwitchComponent } from '../../shared/components/ui/switch/switch.component';
import { TextareaDirective } from '../../shared/components/ui/textarea/textarea.directive';
import { PacienteDateInputComponent } from './paciente-date-input.component';
import {
  DuplicateResponseData,
  PACIENTES_TEXTS,
  Paciente,
  PacienteCreatePayload,
  PacienteOptions,
  PacienteSexo,
  PacienteUpdatePayload,
  PacientesApiError,
  Tutor,
  TutorCreatePayload,
  TutorPrincipalPayload,
} from './pacientes.models';
import { PacientesService } from './pacientes.service';

type FormMode = 'create' | 'edit';
type TutorMode = 'nuevo' | 'existente';

@Component({
  selector: 'app-paciente-form-modal',
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    AlertComponent,
    ButtonComponent,
    InputDirective,
    SelectFieldComponent,
    SwitchComponent,
    TextareaDirective,
    PacienteDateInputComponent,
  ],
  templateUrl: './paciente-form-modal.component.html',
})
export class PacienteFormModalComponent implements OnInit, OnChanges {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(PacientesService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  @Input() open = false;
  @Input() mode: FormMode = 'create';
  @Input() paciente: Paciente | null = null;
  @Input() options: PacienteOptions | null = null;
  @Input() defaultBranchId: number | null = null;

  @Output() closed = new EventEmitter<void>();
  @Output() saved = new EventEmitter<Paciente>();

  readonly texts = PACIENTES_TEXTS;
  readonly saving = signal(false);
  readonly error = signal<PacientesApiError | null>(null);
  readonly duplicateData = signal<DuplicateResponseData | null>(null);
  readonly hardDuplicate = signal(false);
  readonly showDuplicatePanel = signal(false);
  readonly tutorMode = signal<TutorMode>('nuevo');
  readonly mostrarSensibles = signal(false);
  readonly tutorResults = signal<Tutor[]>([]);
  readonly selectedTutor = signal<Tutor | null>(null);
  readonly duplicateReason = signal('');

  readonly tutorSearch = this.fb.nonNullable.control('');
  readonly form = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.maxLength(100)]],
    apellido_paterno: ['', [Validators.required, Validators.maxLength(100)]],
    apellido_materno: [''],
    fecha_nacimiento: ['', [Validators.required]],
    sexo: ['F' as PacienteSexo, [Validators.required]],
    ci: [''],
    sucursal_registro_id: ['', [Validators.required]],
    tipo_diagnostico_id: [''],
    observaciones: [''],
    alergias: [''],
    condiciones_medicas: [''],
    tutor_nombre: [''],
    tutor_ci: [''],
    tutor_telefono: [''],
    tutor_telefono_alternativo: [''],
    tutor_email: [''],
    tutor_direccion: [''],
    tutor_parentesco_id: ['', [Validators.required]],
  });

  get isEdit(): boolean {
    return this.mode === 'edit';
  }

  get canViewSensitive(): boolean {
    return this.options?.puede_ver_sensibles === true;
  }

  get tutoresDisponibles(): boolean {
    return this.options?.tutores.disponible === true;
  }

  get parentescoOptions(): { value: string; label: string }[] {
    return (this.options?.parentescos ?? []).map((parentesco) => ({
      value: String(parentesco.id),
      label: parentesco.nombre,
    }));
  }

  get sexoOptions(): { value: string; label: string }[] {
    return (this.options?.sexos ?? []).map((sexo) => ({
      value: sexo.value,
      label: sexo.label,
    }));
  }

  get sucursalOptions(): { value: string; label: string }[] {
    return (this.options?.sucursales ?? []).map((sucursal) => ({
      value: String(sucursal.id),
      label: sucursal.nombre,
    }));
  }

  ngOnInit(): void {
    this.tutorSearch.valueChanges
      .pipe(debounceTime(350), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe((term) => this.searchTutors(term));
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['open']?.currentValue === true) {
      this.reset();
    }
  }

  reset(): void {
    this.error.set(null);
    this.duplicateData.set(null);
    this.hardDuplicate.set(false);
    this.showDuplicatePanel.set(false);
    this.duplicateReason.set('');
    this.selectedTutor.set(null);
    this.tutorResults.set([]);
    this.tutorSearch.setValue('', { emitEvent: false });
    this.tutorMode.set('nuevo');

    if (this.isEdit && this.paciente) {
      const paciente = this.paciente;

      this.form.reset({
        nombre: paciente.nombre,
        apellido_paterno: paciente.apellido_paterno,
        apellido_materno: paciente.apellido_materno ?? '',
        fecha_nacimiento: paciente.fecha_nacimiento,
        sexo: paciente.sexo,
        ci: paciente.ci ?? '',
        sucursal_registro_id: String(paciente.sucursal_registro.id),
        tipo_diagnostico_id: paciente.tipo_diagnostico_id
          ? String(paciente.tipo_diagnostico_id)
          : '',
        observaciones: paciente.observaciones ?? '',
        alergias: paciente.alergias ?? '',
        condiciones_medicas: paciente.condiciones_medicas ?? '',
        tutor_nombre: '',
        tutor_ci: '',
        tutor_telefono: '',
        tutor_telefono_alternativo: '',
        tutor_email: '',
        tutor_direccion: '',
        tutor_parentesco_id: '',
      });

      this.mostrarSensibles.set(Boolean(paciente.alergias || paciente.condiciones_medicas));
    } else {
      this.form.reset({
        nombre: '',
        apellido_paterno: '',
        apellido_materno: '',
        fecha_nacimiento: '',
        sexo: 'F',
        ci: '',
        sucursal_registro_id: this.defaultBranchId ? String(this.defaultBranchId) : '',
        tipo_diagnostico_id: '',
        observaciones: '',
        alergias: '',
        condiciones_medicas: '',
        tutor_nombre: '',
        tutor_ci: '',
        tutor_telefono: '',
        tutor_telefono_alternativo: '',
        tutor_email: '',
        tutor_direccion: '',
        tutor_parentesco_id: '',
      });

      this.mostrarSensibles.set(false);
    }
  }

  setTutorMode(mode: TutorMode): void {
    this.tutorMode.set(mode);
    this.error.set(null);
  }

  searchTutors(term: string): void {
    const search = term.trim();

    if (search.length < 2) {
      this.tutorResults.set([]);
      return;
    }

    this.service
      .listTutores({ search, per_page: 10, activo: true })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.tutorResults.set(response.data),
        error: () => this.tutorResults.set([]),
      });
  }

  selectTutor(tutor: Tutor): void {
    this.selectedTutor.set(tutor);
    this.error.set(null);
  }

  clearSelectedTutor(): void {
    this.selectedTutor.set(null);
  }

  submit(confirmDuplicate = false): void {
    if (this.saving()) {
      return;
    }

    this.error.set(null);

    if (!this.validateForm()) {
      return;
    }

    const payload = this.isEdit
      ? this.buildUpdatePayload()
      : this.buildCreatePayload(confirmDuplicate);

    if (payload === null) {
      return;
    }

    this.saving.set(true);

    const request = this.isEdit && this.paciente
      ? this.service.updatePaciente(this.paciente.id, payload as PacienteUpdatePayload)
      : this.service.createPaciente(payload as PacienteCreatePayload);

    request.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.toast.success(
          this.isEdit ? 'Paciente actualizado' : 'Paciente creado',
          'La operación quedó registrada en la bitácora.',
        );
        this.saved.emit(response.data);
      },
      error: (apiError: PacientesApiError) => {
        this.saving.set(false);
        this.handleError(apiError);
      },
    });
  }

  confirmDuplicate(): void {
    if (this.duplicateReason().trim() === '') {
      this.error.set({
        status: 422,
        message: this.texts.duplicateReasonRequired,
        errors: { motivo_confirmacion: [this.texts.duplicateReasonRequired] },
      });
      return;
    }

    this.submit(true);
  }

  close(): void {
    if (this.saving()) {
      return;
    }

    this.closed.emit();
  }

  openExistingFicha(): void {
    const existing = this.duplicateData()?.paciente_existente;

    if (existing?.puede_ver_ficha) {
      this.closed.emit();
      void this.router.navigate(['/pacientes', existing.id]);
    }
  }

  fieldError(field: string): string | null {
    return this.error()?.errors?.[field]?.[0] ?? null;
  }

  private validateForm(): boolean {
    this.form.markAllAsTouched();

    const required = [
      'nombre',
      'apellido_paterno',
      'fecha_nacimiento',
      'sexo',
      'sucursal_registro_id',
    ];

    if (required.some((field) => this.form.get(field)?.invalid)) {
      return false;
    }

    if (!this.isEdit) {
      if (!this.tutoresDisponibles) {
        this.error.set({ status: 409, message: 'El alta requiere el submódulo de tutores (04.02).' });
        return false;
      }

      if (this.tutorMode() === 'existente' && this.selectedTutor() === null) {
        this.error.set({
          status: 422,
          message: 'Seleccioná un tutor existente o creá uno nuevo.',
        });
        return false;
      }

      if (this.tutorMode() === 'nuevo') {
        const tutorRequired = ['tutor_nombre', 'tutor_telefono', 'tutor_parentesco_id'];

        if (tutorRequired.some((field) => !String(this.form.get(field)?.value ?? '').trim())) {
          this.error.set({
            status: 422,
            message: 'Completá nombre, teléfono y parentesco del tutor principal.',
          });
          return false;
        }
      }
    }

    return true;
  }

  private buildCreatePayload(confirmDuplicate: boolean): PacienteCreatePayload | null {
    const value = this.form.getRawValue();

    const tutorPrincipal = this.buildTutorPrincipal();

    if (tutorPrincipal === null) {
      return null;
    }

    const payload: PacienteCreatePayload = {
      nombre: value.nombre.trim(),
      apellido_paterno: value.apellido_paterno.trim(),
      apellido_materno: value.apellido_materno.trim() || null,
      fecha_nacimiento: value.fecha_nacimiento,
      sexo: value.sexo,
      ci: value.ci.trim() || null,
      tipo_diagnostico_id: this.options?.tipos_diagnostico.disponible && value.tipo_diagnostico_id
        ? Number(value.tipo_diagnostico_id)
        : null,
      sucursal_registro_id: Number(value.sucursal_registro_id),
      observaciones: value.observaciones.trim() || null,
      tutor_principal: tutorPrincipal,
    };

    if (this.canViewSensitive && this.mostrarSensibles()) {
      payload.alergias = value.alergias.trim() || null;
      payload.condiciones_medicas = value.condiciones_medicas.trim() || null;
    }

    if (confirmDuplicate) {
      payload.confirmar_duplicado = true;
      payload.motivo_confirmacion = this.duplicateReason().trim();
    }

    return payload;
  }

  private buildUpdatePayload(): PacienteUpdatePayload | null {
    if (!this.paciente) {
      return null;
    }

    const value = this.form.getRawValue();
    const payload: PacienteUpdatePayload = {
      nombre: value.nombre.trim(),
      apellido_paterno: value.apellido_paterno.trim(),
      apellido_materno: value.apellido_materno.trim() || null,
      fecha_nacimiento: value.fecha_nacimiento,
      sexo: value.sexo,
      ci: value.ci.trim() || null,
      tipo_diagnostico_id: this.options?.tipos_diagnostico.disponible && value.tipo_diagnostico_id
        ? Number(value.tipo_diagnostico_id)
        : null,
      observaciones: value.observaciones.trim() || null,
      version: this.paciente.version,
    };

    if (this.canViewSensitive && this.mostrarSensibles()) {
      payload.alergias = value.alergias.trim() || null;
      payload.condiciones_medicas = value.condiciones_medicas.trim() || null;
    }

    return payload;
  }

  private buildTutorPrincipal(): TutorPrincipalPayload | null {
    if (this.tutorMode() === 'existente') {
      const tutor = this.selectedTutor();

      return tutor === null ? null : { modo: 'existente', tutor_id: tutor.id };
    }

    const value = this.form.getRawValue();

    const tutor: TutorCreatePayload = {
      nombre_completo: value.tutor_nombre.trim(),
      ci: value.tutor_ci.trim() || null,
      telefono: value.tutor_telefono.trim(),
      telefono_alternativo: value.tutor_telefono_alternativo.trim() || null,
      email: value.tutor_email.trim() || null,
      direccion: value.tutor_direccion.trim() || null,
      parentesco_id: Number(value.tutor_parentesco_id),
      sucursal_registro_id: Number(value.sucursal_registro_id),
    };

    return { modo: 'nuevo', ...tutor };
  }

  private handleError(apiError: PacientesApiError): void {
    if (apiError.status === 409 && apiError.code === 'POTENTIAL_DUPLICATE') {
      this.duplicateData.set(apiError.data ?? null);
      this.hardDuplicate.set(false);
      this.showDuplicatePanel.set(true);
      return;
    }

    if (apiError.status === 409 && apiError.code === 'PATIENT_DOCUMENT_DUPLICATE') {
      this.duplicateData.set(apiError.data ?? null);
      this.hardDuplicate.set(true);
      this.showDuplicatePanel.set(true);
      return;
    }

    if (apiError.status === 409 && apiError.code === 'PATIENT_VERSION_CONFLICT') {
      this.error.set({ ...apiError, message: this.texts.versionConflict });
      return;
    }

    this.error.set(apiError);
  }
}
