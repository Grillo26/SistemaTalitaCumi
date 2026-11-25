import {
  Component,
  DestroyRef,
  OnInit,
  computed,
  inject,
  signal,
} from '@angular/core';
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
  EMPTY_TUTOR_PAGE_ABILITIES,
  PacientesApiError,
  TUTORES_TEXTS,
  Tutor,
  TutorListParams,
  TutorOptions,
  TutorPageAbilities,
  TutorSort,
} from './pacientes.models';
import { PacientesService } from './pacientes.service';
import { TutorFormModalComponent } from './tutor-form-modal.component';

@Component({
  selector: 'app-tutores-page',
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
    TutorFormModalComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './tutores.page.html',
})
export class TutoresPageComponent implements OnInit {
  private readonly service = inject(PacientesService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = TUTORES_TEXTS;
  readonly searchControl = new FormControl('', { nonNullable: true });

  readonly rows = signal<Tutor[]>([]);
  readonly loading = signal(false);
  readonly error = signal<PacientesApiError | null>(null);
  readonly abilities = signal<TutorPageAbilities>(EMPTY_TUTOR_PAGE_ABILITIES);
  readonly options = signal<TutorOptions | null>(null);

  readonly page = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly from = signal<number | null>(null);
  readonly to = signal<number | null>(null);
  readonly perPage = signal(10);
  readonly sort = signal<TutorSort>('nombre_completo');
  readonly direction = signal<'asc' | 'desc'>('asc');
  readonly estadoFilter = signal<'' | 'activo' | 'inactivo'>('');
  readonly sucursalFilter = signal<number | ''>('');
  readonly createOpen = signal(false);
  readonly exporting = signal(false);

  readonly estadoFilterOptions = [
    { value: '', label: this.texts.allStates },
    { value: 'activo', label: this.texts.active },
    { value: 'inactivo', label: this.texts.inactive },
  ];

  readonly sucursalFilterOptions = computed(() => [
    { value: '', label: this.texts.allBranches },
    ...(this.options()?.sucursales ?? []).map((sucursal) => ({
      value: String(sucursal.id),
      label: sucursal.nombre,
    })),
  ]);

  ngOnInit(): void {
    this.loadOptions();
    this.load();

    this.searchControl.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.load(1));
  }

  loadOptions(): void {
    this.service
      .getTutorOptions()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.options.set(response.data);
          this.abilities.set(response.can);
        },
        error: (apiError: PacientesApiError) => this.error.set(apiError),
      });
  }

  load(page = this.page()): void {
    this.loading.set(true);
    this.error.set(null);

    const params: TutorListParams = {
      search: this.searchControl.value || undefined,
      activo: this.estadoFilter() === ''
        ? undefined
        : this.estadoFilter() === 'activo',
      sucursal_registro_id: this.sucursalFilter() || undefined,
      page,
      per_page: this.perPage(),
      sort: this.sort(),
      direction: this.direction(),
    };

    this.service
      .listTutores(params)
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
        error: (apiError: PacientesApiError) => {
          this.error.set(apiError);
          this.loading.set(false);
        },
      });
  }

  onEstadoFilter(value: string): void {
    this.estadoFilter.set((value as '' | 'activo' | 'inactivo') || '');
    this.load(1);
  }

  onSucursalFilter(value: string): void {
    this.sucursalFilter.set(value === '' ? '' : Number(value));
    this.load(1);
  }

  sortBy(sort: TutorSort): void {
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

  openFicha(tutor: Tutor): void {
    void this.router.navigate(['/pacientes/tutores', tutor.id]);
  }

  openCreate(): void {
    this.createOpen.set(true);
  }

  closeCreate(): void {
    this.createOpen.set(false);
  }

  onCreated(tutor: Tutor): void {
    this.createOpen.set(false);
    this.toast.success('Tutor creado', 'La ficha quedó registrada y auditada.');
    void this.router.navigate(['/pacientes/tutores', tutor.id]);
  }

  exportTutores(): void {
    if (this.exporting()) {
      return;
    }

    this.exporting.set(true);
    this.error.set(null);

    const params: TutorListParams = {
      search: this.searchControl.value || undefined,
      activo: this.estadoFilter() === ''
        ? undefined
        : this.estadoFilter() === 'activo',
      sucursal_registro_id: this.sucursalFilter() || undefined,
      sort: this.sort(),
      direction: this.direction(),
    };

    this.service
      .exportTutores(params)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (blob) => {
          this.downloadBlob(blob);
          this.exporting.set(false);
          this.toast.success(this.texts.exportSuccess, 'El archivo respeta los filtros y el alcance.');
        },
        error: (apiError: PacientesApiError) => {
          this.exporting.set(false);
          this.error.set(apiError);
        },
      });
  }

  private downloadBlob(blob: Blob): void {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `tutores-${new Date().toISOString().slice(0, 10)}.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    window.setTimeout(() => URL.revokeObjectURL(url), 1000);
  }

  estadoColor(estado: 'activo' | 'inactivo'): 'success' | 'gray' {
    return estado === 'activo' ? 'success' : 'gray';
  }

  sucursalName(tutor: Tutor): string {
    return tutor.sucursal_registro?.nombre
      ?? (tutor.sucursal_registro ? `Sucursal ${tutor.sucursal_registro.id}` : '—');
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

  sortIndicator(column: TutorSort): string {
    if (this.sort() !== column) {
      return '↕';
    }

    return this.direction() === 'asc' ? '↑' : '↓';
  }

  ariaSort(column: TutorSort): 'ascending' | 'descending' | 'none' {
    if (this.sort() !== column) {
      return 'none';
    }

    return this.direction() === 'asc' ? 'ascending' : 'descending';
  }
}
