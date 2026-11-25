import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { Router } from '@angular/router';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import { ToastService } from '../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { InputDirective } from '../../shared/components/ui/input/input.directive';
import { PaginationComponent } from '../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../shared/components/ui/select/select-field.component';
import { TABLE_IMPORTS } from '../../shared/components/ui/table';
import {
  EMPTY_PACIENTE_ABILITIES,
  ESTADO_FILTER_OPTIONS,
  PACIENTES_TEXTS,
  Paciente,
  PacienteEstado,
  PacienteListParams,
  PacienteOptions,
  PacientePageAbilities,
  PacienteSort,
  PacientesApiError,
} from './pacientes.models';
import { PacientesService } from './pacientes.service';
import { PacienteFormModalComponent } from './paciente-form-modal.component';

@Component({
  selector: 'app-pacientes-page',
  imports: [
    ReactiveFormsModule,
    PageBreadcrumbComponent,
    ComponentCardComponent,
    AlertComponent,
    BadgeComponent,
    ButtonComponent,
    InputDirective,
    PaginationComponent,
    SelectFieldComponent,
    PacienteFormModalComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './pacientes.page.html',
})
export class PacientesPageComponent implements OnInit {
  private readonly service = inject(PacientesService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = PACIENTES_TEXTS;
  readonly estadoFilterOptions = ESTADO_FILTER_OPTIONS;
  readonly searchControl = new FormControl('', { nonNullable: true });

  readonly rows = signal<Paciente[]>([]);
  readonly loading = signal(false);
  readonly exporting = signal(false);
  readonly searchHint = signal<string | null>(null);
  readonly error = signal<PacientesApiError | null>(null);
  readonly abilities = signal<PacientePageAbilities>(EMPTY_PACIENTE_ABILITIES);
  readonly options = signal<PacienteOptions | null>(null);

  readonly page = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly from = signal<number | null>(null);
  readonly to = signal<number | null>(null);
  readonly perPage = signal(10);
  readonly sort = signal<PacienteSort>('nombre_completo');
  readonly direction = signal<'asc' | 'desc'>('asc');
  readonly estadoFilter = signal<PacienteEstado | ''>('');
  readonly sucursalFilter = signal<number | ''>('');
  readonly sinDiagnostico = signal(false);

  readonly createOpen = signal(false);

  readonly sucursalFilterOptions = computed(() => [
    { value: '', label: 'Todas las sucursales' },
    ...(this.options()?.sucursales ?? []).map((sucursal) => ({
      value: String(sucursal.id),
      label: sucursal.nombre,
    })),
  ]);

  readonly sinDiagnosticoOptions = [
    { value: '', label: 'Todos los diagnósticos' },
    { value: '1', label: 'Solo sin diagnóstico' },
  ];

  readonly inscripcionFilterOptions = [
    { value: '', label: 'Disponible con Inscripciones (06)' },
  ];

  readonly servicioFilterOptions = [
    { value: '', label: 'Disponible con Inscripciones (06)' },
  ];

  readonly pagoFilterOptions = [
    { value: '', label: 'Disponible con Pagos y caja (08)' },
  ];

  ngOnInit(): void {
    this.loadOptions();
    this.load();

    this.searchControl.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe((value) => {
        const term = value.trim();

        if (term.length === 1) {
          this.searchHint.set(this.texts.searchTooShort);
          this.rows.set([]);
          this.total.set(0);
          this.page.set(1);
          this.lastPage.set(1);
          this.from.set(null);
          this.to.set(null);
          return;
        }

        this.searchHint.set(null);
        this.load(1);
      });
  }

  loadOptions(): void {
    this.service
      .getOptions()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.options.set(response.data);
          this.abilities.set(response.can);
        },
        error: (error: PacientesApiError) => this.error.set(error),
      });
  }

  load(page = this.page()): void {
    this.loading.set(true);
    this.error.set(null);

    const params: PacienteListParams = {
      search: this.searchControl.value || undefined,
      estado: this.estadoFilter() || undefined,
      sucursal_registro_id: this.sucursalFilter() || undefined,
      sin_diagnostico: this.sinDiagnostico() || undefined,
      page,
      per_page: this.perPage(),
      sort: this.sort(),
      direction: this.direction(),
    };

    this.service
      .listPacientes(params)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.rows.set(response.data);
          this.abilities.set(response.can);
          this.page.set(response.meta.current_page);
          this.lastPage.set(response.meta.last_page);
          this.total.set(response.meta.total);
          this.from.set(response.meta.from);
          this.to.set(response.meta.to);
          this.loading.set(false);
        },
        error: (error: PacientesApiError) => {
          this.error.set(error);
          this.loading.set(false);
        },
      });
  }

  onEstadoFilter(value: string): void {
    this.estadoFilter.set((value as PacienteEstado | '') || '');
    this.load(1);
  }

  onSucursalFilter(value: string): void {
    this.sucursalFilter.set(value === '' ? '' : Number(value));
    this.load(1);
  }

  onSinDiagnostico(value: string): void {
    this.sinDiagnostico.set(value === '1');
    this.load(1);
  }

  sortBy(sort: PacienteSort): void {
    if (this.sort() === sort) {
      this.direction.set(this.direction() === 'asc' ? 'desc' : 'asc');
    } else {
      this.sort.set(sort);
      this.direction.set('asc');
    }

    this.load(1);
  }

  goToPage(page: number): void {
    this.load(page);
  }

  openResumen(paciente: Paciente): void {
    void this.router.navigate(['/pacientes', paciente.id, 'resumen']);
  }

  openFicha(paciente: Paciente): void {
    void this.router.navigate(['/pacientes', paciente.id]);
  }

  exportCsv(): void {
    if (!this.abilities().export_listado || this.exporting()) {
      return;
    }

    this.exporting.set(true);
    this.error.set(null);

    this.service
      .exportPacientes({
        search: this.searchControl.value.trim() || undefined,
        estado: this.estadoFilter() || undefined,
        sucursal_registro_id: this.sucursalFilter() || undefined,
        sin_diagnostico: this.sinDiagnostico() || undefined,
        sort: this.sort(),
        direction: this.direction(),
        formato: 'csv',
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (blob) => {
          this.exporting.set(false);
          this.downloadBlob(blob);
          this.toast.success(
            this.texts.exportSuccess,
            'El archivo respeta los filtros y tu alcance.',
          );
        },
        error: (apiError: PacientesApiError) => {
          this.exporting.set(false);
          this.error.set(apiError);
        },
      });
  }

  openCreate(): void {
    this.createOpen.set(true);
  }

  closeCreate(): void {
    this.createOpen.set(false);
  }

  onCreated(paciente: Paciente): void {
    this.createOpen.set(false);
    this.toast.success('Paciente creado', 'La ficha quedó registrada y auditada.');
    void this.router.navigate(['/pacientes', paciente.id, 'resumen']);
  }

  sucursalName(paciente: Paciente): string {
    return paciente.sucursal_registro.nombre ?? `Sucursal ${paciente.sucursal_registro.id}`;
  }

  formatDateOnly(value: string | null): string {
    if (!value) {
      return '—';
    }

    const [year, month, day] = value.slice(0, 10).split('-');

    return year && month && day ? `${day}/${month}/${year}` : value;
  }

  formatDateTime(value: string | null): string {
    if (!value) {
      return '—';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
      ? value
      : new Intl.DateTimeFormat('es-BO', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        }).format(date);
  }

  estadoColor(estado: PacienteEstado): 'success' | 'warning' | 'gray' {
    return estado === 'activo' ? 'success' : estado === 'en_pausa' ? 'warning' : 'gray';
  }

  private downloadBlob(blob: Blob): void {
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');

    anchor.href = url;
    anchor.download = `pacientes_${new Date().toISOString().slice(0, 10)}.csv`;
    anchor.click();
    URL.revokeObjectURL(url);
  }

  sortIndicator(column: PacienteSort): string {
    if (this.sort() !== column) {
      return '↕';
    }

    return this.direction() === 'asc' ? '↑' : '↓';
  }

  ariaSort(column: PacienteSort): 'ascending' | 'descending' | 'none' {
    if (this.sort() !== column) {
      return 'none';
    }

    return this.direction() === 'asc' ? 'ascending' : 'descending';
  }
}
