import { CommonModule } from '@angular/common';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../../shared/components/ui/modal/modal.component';
import { PaginationComponent } from '../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { TABLE_IMPORTS } from '../../../shared/components/ui/table';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { BitacoraDateInputComponent } from './bitacora-date-input.component';
import {
  ApiError,
  AuditEntry,
  AuditFilterOptions,
  AuditPaginationMeta,
  AuditReconstruction,
  BITACORA_TEXTS,
  CriticalFilter,
} from './bitacora.models';
import { BitacoraService } from './bitacora.service';

@Component({
  selector: 'app-bitacora-page',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    PageBreadcrumbComponent,
    ComponentCardComponent,
    ButtonComponent,
    BadgeComponent,
    AlertComponent,
    PaginationComponent,
    InputDirective,
    ModalComponent,
    SelectFieldComponent,
    BitacoraDateInputComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './bitacora.page.html',
})
export class BitacoraPageComponent implements OnInit {
  private readonly service = inject(BitacoraService);
  private readonly auth = inject(AuthService);

  readonly texts = BITACORA_TEXTS;

  readonly entries = signal<AuditEntry[]>([]);
  readonly loading = signal(false);
  readonly error = signal<ApiError | null>(null);
  readonly meta = signal<AuditPaginationMeta | null>(null);
  readonly options = signal<AuditFilterOptions | null>(null);
  readonly optionsError = signal<ApiError | null>(null);

  readonly fechaDesde = new FormControl('', { nonNullable: true });
  readonly fechaHasta = new FormControl('', { nonNullable: true });
  readonly usuarioFilter = signal<number | null>(null);
  readonly moduloFilter = signal('');
  readonly entidadFilter = signal('');
  readonly accionFilter = signal('');
  readonly resultadoFilter = signal('');
  readonly criticoFilter = signal<CriticalFilter>('');
  readonly sucursalFilter = signal<number | null>(null);
  readonly page = signal(1);
  readonly perPage = 20;

  readonly branches = computed(() => this.auth.user()?.branches ?? []);

  readonly userOptions = computed(() => [
    { value: '', label: 'Todos los usuarios' },
    ...(this.options()?.usuarios ?? []).map((user) => ({
      value: String(user.id),
      label: `${user.nombre} · ${user.email}`,
    })),
  ]);

  readonly moduleOptions = computed(() => [
    { value: '', label: 'Todos los módulos' },
    ...(this.options()?.modulos ?? []).map((module) => ({
      value: module.clave,
      label: module.label,
    })),
  ]);

  readonly entityOptions = computed(() => [
    { value: '', label: 'Todas las entidades' },
    ...(this.options()?.entidades ?? []).map((entity) => ({
      value: entity.clave,
      label: entity.label,
    })),
  ]);

  readonly actionOptions = computed(() => [
    { value: '', label: 'Todas las acciones' },
    ...(this.options()?.acciones ?? []).map((action) => ({
      value: action.clave,
      label: action.label,
    })),
  ]);

  readonly resultOptions = computed(() => [
    { value: '', label: 'Todos los resultados' },
    ...(this.options()?.resultados ?? []).map((result) => ({
      value: result.clave,
      label: result.label,
    })),
  ]);

  readonly branchOptions = computed(() => [
    { value: '', label: 'Todas las sucursales' },
    ...this.branches().map((branch) => ({
      value: String(branch.id),
      label: `${branch.codigo} · ${branch.nombre}`,
    })),
  ]);

  readonly criticalOptions = [
    { value: '', label: 'Todos' },
    { value: '1', label: 'Solo críticos' },
    { value: '0', label: 'No críticos' },
  ];

  readonly reconstructEntityOptions = computed(() =>
    (this.options()?.entidades ?? []).map((entity) => ({
      value: entity.clave,
      label: entity.label,
    })),
  );

  readonly retentionYears = computed(() => this.options()?.retencion_anios ?? null);

  readonly detailOpen = signal(false);
  readonly selected = signal<AuditEntry | null>(null);
  readonly detailLoading = signal(false);
  readonly detailError = signal<ApiError | null>(null);

  readonly reconstructEntidad = new FormControl('usuario', { nonNullable: true });
  readonly reconstructId = new FormControl<number | null>(null);
  readonly reconstructFecha = new FormControl(this.today(), { nonNullable: true });
  readonly reconstructResult = signal<AuditReconstruction | null>(null);
  readonly reconstructLoading = signal(false);
  readonly reconstructError = signal<string | null>(null);

  ngOnInit(): void {
    this.loadOptions();
    this.loadEntries();
  }

  loadOptions(): void {
    this.optionsError.set(null);

    this.service.options().subscribe({
      next: (response) => this.options.set(response.data),
      error: (apiError: ApiError) => this.optionsError.set(apiError),
    });
  }

  loadEntries(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service
      .list({
        fecha_desde: this.fechaDesde.value || undefined,
        fecha_hasta: this.fechaHasta.value || undefined,
        usuario_id: this.usuarioFilter(),
        modulo: this.moduloFilter() || undefined,
        entidad: this.entidadFilter() || undefined,
        accion: this.accionFilter() || undefined,
        resultado: this.resultadoFilter() || undefined,
        critico: this.criticoFilter() === '' ? undefined : this.criticoFilter() === '1',
        sucursal_id: this.sucursalFilter(),
        page: this.page(),
        per_page: this.perPage,
      })
      .subscribe({
        next: (response) => {
          this.entries.set(response.data);
          this.meta.set(response.meta);
          this.loading.set(false);
        },
        error: (apiError: ApiError) => {
          this.error.set(apiError);
          this.loading.set(false);
        },
      });
  }

  applyFilters(): void {
    this.page.set(1);
    this.loadEntries();
  }

  clearFilters(): void {
    this.fechaDesde.reset('');
    this.fechaHasta.reset('');
    this.usuarioFilter.set(null);
    this.moduloFilter.set('');
    this.entidadFilter.set('');
    this.accionFilter.set('');
    this.resultadoFilter.set('');
    this.criticoFilter.set('');
    this.sucursalFilter.set(null);
    this.page.set(1);
    this.loadEntries();
  }

  onUserChange(value: string): void {
    this.usuarioFilter.set(value === '' ? null : Number(value));
    this.applyFilters();
  }

  onModuleChange(value: string): void {
    this.moduloFilter.set(value);
    this.applyFilters();
  }

  onEntityChange(value: string): void {
    this.entidadFilter.set(value);
    this.applyFilters();
  }

  onActionChange(value: string): void {
    this.accionFilter.set(value);
    this.applyFilters();
  }

  onResultChange(value: string): void {
    this.resultadoFilter.set(value);
    this.applyFilters();
  }

  onCriticalChange(value: string): void {
    this.criticoFilter.set(value as CriticalFilter);
    this.applyFilters();
  }

  onBranchChange(value: string): void {
    this.sucursalFilter.set(value === '' ? null : Number(value));
    this.applyFilters();
  }

  goToPage(page: number): void {
    this.page.set(page);
    this.loadEntries();
  }

  openDetail(entry: AuditEntry): void {
    this.selected.set(entry);
    this.detailOpen.set(true);
    this.detailLoading.set(true);
    this.detailError.set(null);

    this.service.get(entry.id).subscribe({
      next: (response) => {
        this.selected.set(response.data);
        this.detailLoading.set(false);
      },
      error: (apiError: ApiError) => {
        this.detailError.set(apiError);
        this.detailLoading.set(false);
      },
    });
  }

  closeDetail(): void {
    this.detailOpen.set(false);
    this.selected.set(null);
    this.detailError.set(null);
  }

  reconstruct(): void {
    const entidad = this.reconstructEntidad.value;
    const entidadId = this.reconstructId.value;
    const fecha = this.reconstructFecha.value;

    this.reconstructError.set(null);
    this.reconstructResult.set(null);

    if (entidad === '' || entidadId === null || entidadId < 1 || fecha === '') {
      this.reconstructError.set('Completá entidad, identificador y fecha.');
      return;
    }

    this.reconstructLoading.set(true);

    this.service.reconstruct(entidad, entidadId, fecha).subscribe({
      next: (response) => {
        this.reconstructResult.set(response.data);
        this.reconstructLoading.set(false);
      },
      error: (apiError: ApiError) => {
        this.reconstructError.set(apiError.message);
        this.reconstructLoading.set(false);
      },
    });
  }

  formatDate(value: string | null): string {
    if (value === null) return '—';

    return new Intl.DateTimeFormat('es-BO', {
      dateStyle: 'short',
      timeStyle: 'medium',
    }).format(new Date(value));
  }

  resultColor(result: string | null): 'success' | 'error' | 'warning' | 'gray' {
    return result === 'exito'
      ? 'success'
      : result === 'fallo'
        ? 'error'
        : result === 'denegado'
          ? 'warning'
          : 'gray';
  }

  private today(): string {
    const date = new Date();

    return [
      date.getFullYear(),
      String(date.getMonth() + 1).padStart(2, '0'),
      String(date.getDate()).padStart(2, '0'),
    ].join('-');
  }
}
