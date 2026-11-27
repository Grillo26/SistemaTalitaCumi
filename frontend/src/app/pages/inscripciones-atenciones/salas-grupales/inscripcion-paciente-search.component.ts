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
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { debounceTime, distinctUntilChanged, filter, switchMap } from 'rxjs';
import { Paciente } from '../../pacientes/pacientes.models';
import { PacientesService } from '../../pacientes/pacientes.service';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';

/**
 * Buscador mínimo de paciente por nombre, apellido, CI o código.
 *
 * Consume la API real de Pacientes. No usa datos de ejemplo.
 */
@Component({
  selector: 'app-inscripcion-paciente-search',
  imports: [ReactiveFormsModule, InputDirective, BadgeComponent],
  host: { class: 'block' },
  template: `
    <div class="relative">
      <label class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ label }}{{ required ? ' *' : '' }}
      </label>
      <input
        app-input
        type="text"
        [formControl]="search"
        placeholder="Nombre, apellido, CI o código"
        aria-label="Buscar paciente"
        autocomplete="off"
      />

      @if (selected; as paciente) {
        <div
          class="mt-2 flex flex-wrap items-center gap-2 rounded-xl border border-gray-200 p-3 dark:border-gray-800"
        >
          <span class="text-sm font-medium text-gray-800 dark:text-white/90">
            {{ paciente.nombre_completo }}
          </span>
          @if (paciente.codigo) {
            <app-badge color="gray" variant="light" size="sm">{{ paciente.codigo }}</app-badge>
          }
          <app-badge
            [color]="paciente.estado === 'activo' ? 'success' : 'warning'"
            variant="light"
            size="sm"
          >
            {{ paciente.estado_label }}
          </app-badge>
          <button
            type="button"
            class="ms-auto text-xs font-medium text-brand-500 hover:text-brand-600"
            (click)="clear()"
          >
            Cambiar
          </button>
        </div>
      }

      @if (!selected && (results().length > 0 || loading() || error())) {
        <div
          class="absolute z-30 mt-2 max-h-72 w-full overflow-auto rounded-xl border border-gray-200 bg-white p-2 shadow-lg dark:border-gray-800 dark:bg-gray-900"
        >
          @if (loading()) {
            <p class="px-3 py-2 text-sm text-gray-500">Buscando pacientes...</p>
          } @else if (error(); as message) {
            <p class="px-3 py-2 text-sm text-error-600">{{ message }}</p>
          } @else {
            @for (paciente of results(); track paciente.id) {
              <button
                type="button"
                class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-left hover:bg-gray-50 dark:hover:bg-white/[0.04]"
                (click)="select(paciente)"
              >
                <span class="min-w-0 flex-1">
                  <span class="block truncate text-sm font-medium text-gray-800 dark:text-white/90">
                    {{ paciente.nombre_completo }}
                  </span>
                  <span class="block text-xs text-gray-500">
                    {{ paciente.codigo || 'Sin código' }}
                    @if (paciente.ci) {
                      · CI {{ paciente.ci }}
                    }
                  </span>
                </span>
                <app-badge
                  [color]="paciente.estado === 'activo' ? 'success' : 'warning'"
                  variant="light"
                  size="sm"
                >
                  {{ paciente.estado_label }}
                </app-badge>
              </button>
            }
          }
        </div>
      }
    </div>
  `,
})
export class InscripcionPacienteSearchComponent implements OnChanges {
  private readonly pacientesService = inject(PacientesService);
  private readonly destroyRef = inject(DestroyRef);

  @Input() selected: Paciente | null = null;
  @Input() label = 'Paciente';
  @Input() required = false;
  @Output() selectedChange = new EventEmitter<Paciente | null>();

  readonly search = new FormControl('', { nonNullable: true });
  readonly results = signal<Paciente[]>([]);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  constructor() {
    this.search.valueChanges
      .pipe(
        debounceTime(300),
        distinctUntilChanged(),
        filter((term) => term.trim().length === 0 || term.trim().length >= 2),
        switchMap((term) => {
          const value = term.trim();

          if (value === '') {
            this.results.set([]);
            this.loading.set(false);
            this.error.set(null);
            return [];
          }

          this.loading.set(true);
          this.error.set(null);

          return this.pacientesService.listPacientes({
            search: value,
            page: 1,
            per_page: 10,
            sort: 'nombre_completo',
            direction: 'asc',
          });
        }),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe({
        next: (response) => {
          this.results.set(response.data);
          this.loading.set(false);
        },
        error: (error: { message?: string }) => {
          this.loading.set(false);
          this.results.set([]);
          this.error.set(error.message ?? 'No se pudieron buscar pacientes.');
        },
      });
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['selected'] && !this.selected) {
      this.search.setValue('', { emitEvent: false });
      this.results.set([]);
    }
  }

  select(paciente: Paciente): void {
    this.selectedChange.emit(paciente);
    this.search.setValue(paciente.nombre_completo, { emitEvent: false });
    this.results.set([]);
    this.error.set(null);
  }

  clear(): void {
    this.selectedChange.emit(null);
    this.search.setValue('', { emitEvent: false });
    this.results.set([]);
    this.error.set(null);
  }
}
