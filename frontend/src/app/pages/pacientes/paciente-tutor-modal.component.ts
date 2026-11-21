import {
  Component,
  DestroyRef,
  EventEmitter,
  Input,
  OnInit,
  Output,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { InputDirective } from '../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../shared/components/ui/modal/modal.component';
import { TextareaDirective } from '../../shared/components/ui/textarea/textarea.directive';
import { PACIENTES_TEXTS, PacientesApiError, Tutor } from './pacientes.models';
import { PacientesService } from './pacientes.service';

type TutorModalMode = 'buscar' | 'nuevo';

@Component({
  selector: 'app-paciente-tutor-modal',
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    AlertComponent,
    ButtonComponent,
    InputDirective,
    TextareaDirective,
  ],
  templateUrl: './paciente-tutor-modal.component.html',
})
export class PacienteTutorModalComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(PacientesService);
  private readonly destroyRef = inject(DestroyRef);

  @Input({ required: true }) open = false;
  @Input({ required: true }) pacienteId = 0;
  @Input({ required: true }) pacienteNombre = '';

  @Output() closed = new EventEmitter<void>();
  @Output() saved = new EventEmitter<void>();

  readonly texts = PACIENTES_TEXTS;
  readonly mode = signal<TutorModalMode>('buscar');
  readonly results = signal<Tutor[]>([]);
  readonly saving = signal(false);
  readonly error = signal<PacientesApiError | null>(null);

  readonly searchControl = this.fb.nonNullable.control('');
  readonly form = this.fb.nonNullable.group({
    nombre_completo: ['', [Validators.required, Validators.maxLength(200)]],
    ci: [''],
    telefono: ['', [Validators.required, Validators.maxLength(30)]],
    telefono_alternativo: [''],
    email: [''],
    direccion: [''],
    parentesco: ['', [Validators.required, Validators.maxLength(50)]],
  });

  ngOnInit(): void {
    this.searchControl.valueChanges
      .pipe(debounceTime(350), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe((term) => this.search(term));
  }

  setMode(mode: TutorModalMode): void {
    this.mode.set(mode);
    this.error.set(null);
  }

  search(term: string): void {
    const search = term.trim();

    if (search.length < 2) {
      this.results.set([]);
      return;
    }

    this.service
      .listTutores({ search, per_page: 10, activo: true })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.results.set(response.data),
        error: (apiError: PacientesApiError) => this.error.set(apiError),
      });
  }

  associate(tutor: Tutor): void {
    if (this.saving()) {
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    this.service
      .associateTutor(this.pacienteId, tutor.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.saved.emit();
        },
        error: (apiError: PacientesApiError) => {
          this.saving.set(false);
          this.error.set(apiError);
        },
      });
  }

  createAndAssociate(): void {
    if (this.saving()) {
      return;
    }

    this.form.markAllAsTouched();

    if (this.form.invalid) {
      this.error.set({ status: 422, message: 'Completá los campos obligatorios del tutor.' });
      return;
    }

    const value = this.form.getRawValue();
    this.saving.set(true);
    this.error.set(null);

    this.service
      .createTutor({
        nombre_completo: value.nombre_completo.trim(),
        ci: value.ci.trim() || null,
        telefono: value.telefono.trim(),
        telefono_alternativo: value.telefono_alternativo.trim() || null,
        email: value.email.trim() || null,
        direccion: value.direccion.trim() || null,
        parentesco: value.parentesco.trim(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.service
            .associateTutor(this.pacienteId, response.data.id)
            .pipe(takeUntilDestroyed(this.destroyRef))
            .subscribe({
              next: () => {
                this.saving.set(false);
                this.saved.emit();
              },
              error: (apiError: PacientesApiError) => {
                this.saving.set(false);
                this.error.set(apiError);
              },
            });
        },
        error: (apiError: PacientesApiError) => {
          this.saving.set(false);
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
