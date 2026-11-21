import {
  Component,
  DestroyRef,
  EventEmitter,
  Input,
  OnChanges,
  Output,
  computed,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ToastService } from '../../core/services/toast.service';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { InputDirective } from '../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../shared/components/ui/modal/modal.component';
import { SwitchComponent } from '../../shared/components/ui/switch/switch.component';
import { TextareaDirective } from '../../shared/components/ui/textarea/textarea.directive';
import {
  ConsentimientoTipo,
  FICHA_INGRESO_TEXTS,
  FichaIngresoApiError,
} from './ficha-ingreso.models';
import { FichaIngresoService } from './ficha-ingreso.service';

@Component({
  selector: 'app-consentimiento-tipos-modal',
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    AlertComponent,
    BadgeComponent,
    ButtonComponent,
    InputDirective,
    SwitchComponent,
    TextareaDirective,
  ],
  templateUrl: './consentimiento-tipos-modal.component.html',
})
export class ConsentimientoTiposModalComponent implements OnChanges {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(FichaIngresoService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  @Input({ required: true }) open = false;

  @Output() closed = new EventEmitter<void>();
  @Output() changed = new EventEmitter<void>();

  readonly texts = FICHA_INGRESO_TEXTS;
  readonly tipos = signal<ConsentimientoTipo[]>([]);
  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly error = signal<FichaIngresoApiError | null>(null);
  readonly editing = signal<ConsentimientoTipo | null>(null);
  readonly isEditing = computed(() => this.editing() !== null);
  private wasOpen = false;

  readonly form = this.fb.nonNullable.group({
    codigo: ['', [Validators.required, Validators.pattern(/^[a-z0-9_]+$/), Validators.maxLength(60)]],
    nombre: ['', [Validators.required, Validators.maxLength(150)]],
    descripcion: [''],
    orden: [0, [Validators.min(0), Validators.max(9999)]],
    activo: [true],
  });

  ngOnChanges(): void {
    if (this.open && !this.wasOpen) {
      this.resetForm();
      this.load();
    }

    this.wasOpen = this.open;
  }

  load(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service
      .tipos(true)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.tipos.set(response.data);
          this.loading.set(false);
        },
        error: (apiError: FichaIngresoApiError) => {
          this.loading.set(false);
          this.error.set(apiError);
        },
      });
  }

  edit(tipo: ConsentimientoTipo): void {
    this.editing.set(tipo);
    this.error.set(null);
    this.form.reset({
      codigo: tipo.codigo,
      nombre: tipo.nombre,
      descripcion: tipo.descripcion ?? '',
      orden: tipo.orden,
      activo: tipo.activo,
    });
  }

  nuevo(): void {
    this.editing.set(null);
    this.resetForm();
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
        message: 'Revisá los datos del tipo de consentimiento.',
        errors: {
          codigo: this.form.controls.codigo.invalid
            ? ['El código es obligatorio y solo admite minúsculas, números y guiones bajos.']
            : [],
          nombre: this.form.controls.nombre.invalid ? ['El nombre es obligatorio.'] : [],
        },
      });
      return;
    }

    const value = this.form.getRawValue();
    const base = {
      nombre: value.nombre.trim(),
      descripcion: value.descripcion.trim() || null,
      orden: Number(value.orden) || 0,
      activo: value.activo,
    };
    const editing = this.editing();
    const request = editing
      ? this.service.updateTipo(editing.id, { ...base, version: editing.version })
      : this.service.createTipo({ ...base, codigo: value.codigo.trim().toLowerCase() });

    this.saving.set(true);

    request.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.saving.set(false);
        this.toast.success(
          editing ? this.texts.consentCatalogUpdated : this.texts.consentCatalogCreated,
          'El catálogo quedó actualizado.',
        );
        this.editing.set(null);
        this.resetForm();
        this.load();
        this.changed.emit();
      },
      error: (apiError: FichaIngresoApiError) => {
        this.saving.set(false);

        if (apiError.code === 'CONSENT_TYPE_VERSION_CONFLICT') {
          this.error.set({ ...apiError, message: this.texts.consentCatalogVersionConflict });
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

  private resetForm(): void {
    this.form.reset({
      codigo: '',
      nombre: '',
      descripcion: '',
      orden: 0,
      activo: true,
    });
    this.error.set(null);
  }
}
