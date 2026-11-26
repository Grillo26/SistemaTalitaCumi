import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, FormControl, ReactiveFormsModule, Validators } from '@angular/forms';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import { ToastService } from '../../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../../shared/components/ui/modal/modal.component';
import { PaginationComponent } from '../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { SwitchComponent } from '../../../shared/components/ui/switch/switch.component';
import { TABLE_IMPORTS } from '../../../shared/components/ui/table';
import { TextareaDirective } from '../../../shared/components/ui/textarea/textarea.directive';
import { Branch } from '../../organizacion/sucursales/sucursales.models';
import { SucursalesService } from '../../organizacion/sucursales/sucursales.service';
import { Servicio } from '../servicios/servicios.models';
import { TarifaDateInputComponent } from './tarifa-date-input.component';
import {
  AplicableParams,
  CategoriaPayload,
  CategoriaTarifa,
  CloseVigenciaPayload,
  EMPTY_TARIFAS_ABILITIES,
  ESTADO_CATEGORIA_OPTIONS,
  ESTADO_VIGENCIA_OPTIONS,
  EstadoCatalogo,
  EstadoVigencia,
  FormMode,
  SECTION_OPTIONS,
  StatusCategoriaPayload,
  TARIFAS_TEXTS,
  Tarifa,
  TarifaAplicable,
  TarifaConflict,
  TarifaPayload,
  TarifasAbilities,
  TarifasApiError,
  TarifasSection,
  formatBs,
} from './tarifas.models';
import { TarifasService } from './tarifas.service';

type TarifaFormKey = 'categoria' | 'tarifa' | 'close' | 'status' | 'aplicable';

@Component({
  selector: 'app-tarifas-page',
  imports: [
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
    SwitchComponent,
    TextareaDirective,
    TarifaDateInputComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './tarifas.page.html',
})
export class TarifasPageComponent implements OnInit {
  private readonly service = inject(TarifasService);
  private readonly sucursalesService = inject(SucursalesService);
  private readonly toast = inject(ToastService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = TARIFAS_TEXTS;
  readonly sectionOptions = SECTION_OPTIONS;
  readonly estadoCategoriaOptions = ESTADO_CATEGORIA_OPTIONS;
  readonly estadoVigenciaOptions = ESTADO_VIGENCIA_OPTIONS;

  readonly activeSection = signal<TarifasSection>('tarifas');
  readonly abilities = signal<TarifasAbilities>(EMPTY_TARIFAS_ABILITIES);
  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly error = signal<TarifasApiError | null>(null);

  readonly categorias = signal<CategoriaTarifa[]>([]);
  readonly tarifas = signal<Tarifa[]>([]);
  readonly branches = signal<Branch[]>([]);
  readonly servicios = signal<Servicio[]>([]);

  readonly servicioSearch = new FormControl('', { nonNullable: true });
  readonly servicioFilter = signal('');
  readonly sucursalFilter = signal('');
  readonly categoriaFilter = signal('');
  readonly estadoVigenciaFilter = signal<EstadoVigencia | ''>('');
  readonly tarifaSort = signal<
    'servicio' | 'categoria' | 'sucursal' | 'monto' | 'vigente_desde' | 'vigente_hasta' | 'updated_at'
  >('vigente_desde');
  readonly tarifaDirection = signal<'asc' | 'desc'>('desc');
  readonly tarifaPage = signal(1);
  readonly tarifaLastPage = signal(1);
  readonly tarifaTotal = signal(0);
  readonly tarifaFrom = signal<number | null>(null);
  readonly tarifaTo = signal<number | null>(null);

  readonly categoriaSearch = new FormControl('', { nonNullable: true });
  readonly categoriaEstado = signal<EstadoCatalogo | ''>('');
  readonly categoriaSort = signal<'nombre' | 'activo' | 'updated_at'>('nombre');
  readonly categoriaDirection = signal<'asc' | 'desc'>('asc');

  readonly categoriaForm = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.maxLength(100)]],
    descripcion: ['', [Validators.maxLength(2000)]],
    activo: [true],
  });

  readonly tarifaForm = this.fb.nonNullable.group({
    servicio_id: ['', [Validators.required]],
    categoria_tarifa_id: ['', [Validators.required]],
    sucursal_id: ['', [Validators.required]],
    monto: [
      '',
      [
        Validators.required,
        Validators.pattern(/^\d{1,10}(\.\d{1,2})?$/),
      ],
    ],
    vigente_desde: ['', [Validators.required]],
    vigente_hasta: [''],
  });

  readonly closeForm = this.fb.nonNullable.group({
    vigente_hasta: ['', [Validators.required]],
    motivo: ['', [Validators.required, Validators.maxLength(1000)]],
  });

  readonly statusForm = this.fb.nonNullable.group({
    motivo: ['', [Validators.maxLength(1000)]],
  });

  readonly aplicableForm = this.fb.nonNullable.group({
    servicio_id: ['', [Validators.required]],
    sucursal_id: ['', [Validators.required]],
    categoria_tarifa_id: ['', [Validators.required]],
    fecha: ['', [Validators.required]],
  });

  readonly categoriaFormOpen = signal(false);
  readonly categoriaFormMode = signal<FormMode>('create');
  readonly editingCategoria = signal<CategoriaTarifa | null>(null);
  readonly categoriaFieldErrors = signal<Record<string, string[]>>({});
  readonly categoriaFormError = signal<string | null>(null);
  readonly categoriaConcurrency = signal(false);

  readonly tarifaFormOpen = signal(false);
  readonly tarifaFormMode = signal<FormMode>('create');
  readonly editingTarifa = signal<Tarifa | null>(null);
  readonly tarifaFieldErrors = signal<Record<string, string[]>>({});
  readonly tarifaFormError = signal<string | null>(null);
  readonly tarifaConcurrency = signal(false);
  readonly tarifaConflict = signal<TarifaConflict | null>(null);

  readonly detailOpen = signal(false);
  readonly selectedTarifa = signal<Tarifa | null>(null);

  readonly closeOpen = signal(false);
  readonly closingTarifa = signal<Tarifa | null>(null);
  readonly closeFieldErrors = signal<Record<string, string[]>>({});
  readonly closeError = signal<string | null>(null);
  readonly closeConcurrency = signal(false);

  readonly statusOpen = signal(false);
  readonly statusCategoria = signal<CategoriaTarifa | null>(null);
  readonly statusMode = signal<'activate' | 'deactivate'>('deactivate');
  readonly statusFieldErrors = signal<Record<string, string[]>>({});
  readonly statusError = signal<string | null>(null);
  readonly statusActiveTariffsCount = signal<number | null>(null);
  readonly statusConcurrency = signal(false);

  readonly aplicableOpen = signal(false);
  readonly aplicableLoading = signal(false);
  readonly aplicableSearched = signal(false);
  readonly aplicableResult = signal<TarifaAplicable | null>(null);
  readonly aplicableError = signal<string | null>(null);
  readonly aplicableFieldErrors = signal<Record<string, string[]>>({});

  readonly canCreateTarifa = computed(() => this.abilities().tarifas.create);
  readonly canEditTarifa = computed(() => this.abilities().tarifas.update);
  readonly canCloseTarifa = computed(() => this.abilities().tarifas.close);
  readonly canCreateCategoria = computed(() => this.abilities().categorias.create);
  readonly canEditCategoria = computed(() => this.abilities().categorias.update);
  readonly canChangeCategoriaStatus = computed(
    () => this.abilities().categorias.change_status,
  );
  readonly isGlobalScope = computed(() => this.abilities().tarifas.global_scope);

  readonly servicioFilterOptions = computed(() => [
    { value: '', label: 'Todos los servicios' },
    ...this.servicios().map((servicio) => ({
      value: String(servicio.id),
      label: this.servicioLabel(servicio),
    })),
  ]);

  readonly servicioFormOptions = computed(() => {
    const options = new Map<string, string>();

    for (const servicio of this.servicios()) {
      options.set(String(servicio.id), this.servicioLabel(servicio));
    }

    const current = this.editingTarifa()?.servicio;

    if (current) {
      options.set(
        String(current.id),
        current.activo ? current.nombre : `${current.nombre} (inactivo)`,
      );
    }

    return [...options.entries()].map(([value, label]) => ({ value, label }));
  });

  readonly categoriaFilterOptions = computed(() => [
    { value: '', label: 'Todas las categorías' },
    ...this.categorias().map((categoria) => ({
      value: String(categoria.id),
      label: categoria.nombre,
    })),
  ]);

  readonly categoriaFormOptions = computed(() => {
    const options = new Map<string, string>();

    for (const categoria of this.categorias().filter((item) => item.activo)) {
      options.set(String(categoria.id), categoria.nombre);
    }

    const current = this.editingTarifa()?.categoria;

    if (current) {
      options.set(
        String(current.id),
        current.activo ? current.nombre : `${current.nombre} (inactiva)`,
      );
    }

    return [...options.entries()].map(([value, label]) => ({ value, label }));
  });

  readonly branchFilterOptions = computed(() => [
    { value: '', label: 'Todas las sucursales' },
    { value: 'global', label: 'Globales' },
    ...this.branches().map((branch) => ({
      value: String(branch.id),
      label: branch.nombre,
    })),
  ]);

  readonly branchFormOptions = computed(() => {
    const options: { value: string; label: string }[] = [];

    if (this.isGlobalScope()) {
      options.push({ value: 'global', label: 'Todas las sucursales (global)' });
    }

    for (const branch of this.branches().filter((item) => item.activo)) {
      options.push({ value: String(branch.id), label: branch.nombre });
    }

    const current = this.editingTarifa()?.sucursal;

    if (current && !options.some((option) => option.value === String(current.id))) {
      options.push({ value: String(current.id), label: current.nombre });
    }

    return options;
  });

  readonly aplicableServicioOptions = computed(() =>
    this.servicios().map((servicio) => ({
      value: String(servicio.id),
      label: this.servicioLabel(servicio),
    })),
  );

  readonly aplicableCategoriaOptions = computed(() =>
    this.categorias()
      .filter((categoria) => categoria.activo)
      .map((categoria) => ({ value: String(categoria.id), label: categoria.nombre })),
  );

  readonly aplicableBranchOptions = computed(() =>
    this.branches()
      .filter((branch) => branch.activo)
      .map((branch) => ({ value: String(branch.id), label: branch.nombre })),
  );

  ngOnInit(): void {
    this.loadBranches();
    this.loadServicios();
    this.loadCategorias();
    this.loadTarifas();

    this.subscribeSearch(this.servicioSearch, () => {
      this.tarifaPage.set(1);
      this.loadTarifas();
    });
    this.subscribeSearch(this.categoriaSearch, () => this.loadCategorias());
  }

  setSection(section: TarifasSection): void {
    this.activeSection.set(section);
    this.error.set(null);

    if (section === 'categorias') {
      this.loadCategorias();
    } else {
      this.loadTarifas();
    }
  }

  loadBranches(): void {
    this.sucursalesService
      .list({ per_page: 50, sort: 'nombre', direction: 'asc', estado: 'activa' })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.branches.set(response.data),
        error: (error: TarifasApiError) => this.error.set(error),
      });
  }

  loadServicios(): void {
    this.service
      .listServiciosActivos()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (servicios) => this.servicios.set(servicios),
        error: (error: TarifasApiError) => this.error.set(error),
      });
  }

  loadCategorias(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service
      .listCategorias({
        search: this.categoriaSearch.value.trim() || undefined,
        estado: this.categoriaEstado() || undefined,
        sort: this.categoriaSort(),
        direction: this.categoriaDirection(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.categorias.set(response.data);
          this.abilities.set(response.can);
          this.loading.set(false);
        },
        error: (error: TarifasApiError) => this.handleError(error),
      });
  }

  loadTarifas(): void {
    this.loading.set(true);
    this.error.set(null);

    const sucursalValue = this.sucursalFilter();

    this.service
      .listTarifas({
        search: this.servicioSearch.value.trim() || undefined,
        servicio_id: this.numberOrUndefined(this.servicioFilter()),
        categoria_tarifa_id: this.numberOrUndefined(this.categoriaFilter()),
        sucursal_id: sucursalValue !== '' && sucursalValue !== 'global'
          ? Number(sucursalValue)
          : undefined,
        solo_global: sucursalValue === 'global' || undefined,
        estado_vigencia: this.estadoVigenciaFilter() || undefined,
        page: this.tarifaPage(),
        per_page: 10,
        sort: this.tarifaSort(),
        direction: this.tarifaDirection(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.tarifas.set(response.data);
          this.abilities.set(response.can);
          this.tarifaPage.set(response.meta.current_page);
          this.tarifaLastPage.set(response.meta.last_page);
          this.tarifaTotal.set(response.meta.total);
          this.tarifaFrom.set(response.meta.from);
          this.tarifaTo.set(response.meta.to);
          this.loading.set(false);

          if (response.data.length === 0 && response.meta.current_page > 1) {
            this.tarifaPage.set(response.meta.current_page - 1);
            this.loadTarifas();
          }
        },
        error: (error: TarifasApiError) => this.handleError(error),
      });
  }

  onServicioFilter(value: string): void {
    this.servicioFilter.set(value);
    this.tarifaPage.set(1);
    this.loadTarifas();
  }

  onSucursalFilter(value: string): void {
    this.sucursalFilter.set(value);
    this.tarifaPage.set(1);
    this.loadTarifas();
  }

  onCategoriaFilter(value: string): void {
    this.categoriaFilter.set(value);
    this.tarifaPage.set(1);
    this.loadTarifas();
  }

  onEstadoVigenciaFilter(value: string): void {
    this.estadoVigenciaFilter.set(value as EstadoVigencia | '');
    this.tarifaPage.set(1);
    this.loadTarifas();
  }

  onCategoriaEstado(value: string): void {
    this.categoriaEstado.set(value as EstadoCatalogo | '');
    this.loadCategorias();
  }

  sortTarifas(
    field: 'servicio' | 'categoria' | 'sucursal' | 'monto' | 'vigente_desde' | 'vigente_hasta' | 'updated_at',
  ): void {
    if (this.tarifaSort() === field) {
      this.tarifaDirection.set(this.tarifaDirection() === 'asc' ? 'desc' : 'asc');
    } else {
      this.tarifaSort.set(field);
      this.tarifaDirection.set('asc');
    }

    this.tarifaPage.set(1);
    this.loadTarifas();
  }

  sortCategorias(field: 'nombre' | 'activo' | 'updated_at'): void {
    if (this.categoriaSort() === field) {
      this.categoriaDirection.set(this.categoriaDirection() === 'asc' ? 'desc' : 'asc');
    } else {
      this.categoriaSort.set(field);
      this.categoriaDirection.set('asc');
    }

    this.loadCategorias();
  }

  goToTarifaPage(page: number): void {
    this.tarifaPage.set(page);
    this.loadTarifas();
  }

  openCategoriaCreate(): void {
    if (!this.canCreateCategoria()) {
      return;
    }

    this.categoriaFormMode.set('create');
    this.editingCategoria.set(null);
    this.categoriaForm.reset({ nombre: '', descripcion: '', activo: true });
    this.categoriaForm.enable();
    this.clearCategoriaErrors();
    this.categoriaFormOpen.set(true);
  }

  openCategoriaEdit(categoria: CategoriaTarifa): void {
    if (!this.canEditCategoria()) {
      return;
    }

    this.service
      .getCategoria(categoria.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.abilities.set(response.can);
          this.categoriaFormMode.set('edit');
          this.editingCategoria.set(response.data);
          this.categoriaForm.reset({
            nombre: response.data.nombre,
            descripcion: response.data.descripcion ?? '',
            activo: response.data.activo,
          });
          this.categoriaForm.controls.activo.disable();
          this.clearCategoriaErrors();
          this.categoriaFormOpen.set(true);
        },
        error: (error: TarifasApiError) => this.handleError(error),
      });
  }

  closeCategoriaForm(): void {
    if (!this.saving()) {
      this.categoriaFormOpen.set(false);
    }
  }

  saveCategoria(): void {
    if (this.saving()) {
      return;
    }

    this.categoriaForm.markAllAsTouched();

    if (this.categoriaForm.invalid) {
      return;
    }

    const raw = this.categoriaForm.getRawValue();
    const payload: CategoriaPayload = {
      nombre: raw.nombre.trim(),
      descripcion: this.nullIfEmpty(raw.descripcion),
    };

    this.saving.set(true);
    this.clearCategoriaErrors();

    if (this.categoriaFormMode() === 'create') {
      payload.activo = raw.activo;

      this.service
        .createCategoria(payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: () => this.handleCategoriaSuccess('Categoría creada'),
          error: (error: TarifasApiError) => this.applyCategoriaFormError(error),
        });
      return;
    }

    const categoria = this.editingCategoria();

    if (!categoria) {
      this.saving.set(false);
      return;
    }

    payload.version = categoria.version;

    this.service
      .updateCategoria(categoria.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => this.handleCategoriaSuccess('Categoría actualizada'),
        error: (error: TarifasApiError) => this.applyCategoriaFormError(error),
      });
  }

  openTarifaCreate(): void {
    if (!this.canCreateTarifa()) {
      return;
    }

    this.tarifaFormMode.set('create');
    this.editingTarifa.set(null);
    this.tarifaForm.reset({
      servicio_id: '',
      categoria_tarifa_id: '',
      sucursal_id: this.defaultFormBranch(),
      monto: '',
      vigente_desde: this.todayIso(),
      vigente_hasta: '',
    });
    this.tarifaForm.enable();
    this.clearTarifaErrors();
    this.tarifaFormOpen.set(true);
  }

  openTarifaEdit(tarifa: Tarifa): void {
    if (!this.canEditTarifa() || !tarifa.can.update) {
      return;
    }

    this.service
      .getTarifa(tarifa.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const latest = response.data;
          this.abilities.set(response.can);
          this.tarifaFormMode.set('edit');
          this.editingTarifa.set(latest);
          this.tarifaForm.reset({
            servicio_id: String(latest.servicio?.id ?? ''),
            categoria_tarifa_id: String(latest.categoria?.id ?? ''),
            sucursal_id: latest.sucursal === null ? 'global' : String(latest.sucursal.id),
            monto: latest.monto,
            vigente_desde: latest.vigente_desde,
            vigente_hasta: latest.vigente_hasta ?? '',
          });
          this.clearTarifaErrors();
          this.tarifaFormOpen.set(true);
        },
        error: (error: TarifasApiError) => this.handleError(error),
      });
  }

  closeTarifaForm(): void {
    if (!this.saving()) {
      this.tarifaFormOpen.set(false);
    }
  }

  saveTarifa(): void {
    if (this.saving()) {
      return;
    }

    this.tarifaForm.markAllAsTouched();

    if (this.tarifaForm.invalid) {
      return;
    }

    const raw = this.tarifaForm.getRawValue();
    const payload: TarifaPayload = {
      servicio_id: Number(raw.servicio_id),
      categoria_tarifa_id: Number(raw.categoria_tarifa_id),
      sucursal_id: raw.sucursal_id === 'global' ? null : Number(raw.sucursal_id),
      monto: raw.monto.trim(),
      vigente_desde: raw.vigente_desde,
    };

    this.saving.set(true);
    this.clearTarifaErrors();

    if (this.tarifaFormMode() === 'create') {
      payload.vigente_hasta = this.nullIfEmpty(raw.vigente_hasta);

      this.service
        .createTarifa(payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: () => this.handleTarifaSuccess('Tarifa creada'),
          error: (error: TarifasApiError) => this.applyTarifaFormError(error),
        });
      return;
    }

    const tarifa = this.editingTarifa();

    if (!tarifa) {
      this.saving.set(false);
      return;
    }

    payload.version = tarifa.version;

    this.service
      .updateTarifa(tarifa.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => this.handleTarifaSuccess('Tarifa actualizada'),
        error: (error: TarifasApiError) => this.applyTarifaFormError(error),
      });
  }

  openTarifaDetail(tarifa: Tarifa): void {
    this.service
      .getTarifa(tarifa.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.selectedTarifa.set(response.data);
          this.abilities.set(response.can);
          this.detailOpen.set(true);
        },
        error: (error: TarifasApiError) => this.handleError(error),
      });
  }

  closeTarifaDetail(): void {
    this.detailOpen.set(false);
  }

  openClose(tarifa: Tarifa): void {
    if (!this.canCloseTarifa() || !tarifa.can.close) {
      return;
    }

    this.detailOpen.set(false);
    this.closingTarifa.set(tarifa);
    this.closeForm.reset({
      vigente_hasta: this.todayIso(),
      motivo: '',
    });
    this.clearCloseErrors();
    this.closeOpen.set(true);
  }

  closeClose(): void {
    if (!this.saving()) {
      this.closeOpen.set(false);
    }
  }

  confirmClose(): void {
    if (this.saving()) {
      return;
    }

    const tarifa = this.closingTarifa();

    if (!tarifa) {
      return;
    }

    this.closeForm.markAllAsTouched();

    if (this.closeForm.invalid) {
      return;
    }

    const raw = this.closeForm.getRawValue();
    const payload: CloseVigenciaPayload = {
      vigente_hasta: raw.vigente_hasta,
      motivo: raw.motivo.trim(),
      version: tarifa.version,
    };

    this.saving.set(true);
    this.clearCloseErrors();

    this.service
      .closeVigencia(tarifa.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.closeOpen.set(false);
          this.toast.success('Vigencia cerrada', 'La tarifa dejó de aplicar a nuevas inscripciones.');
          this.loadTarifas();
        },
        error: (error: TarifasApiError) => {
          this.saving.set(false);
          this.closeFieldErrors.set(error.errors ?? {});
          this.closeError.set(error.message);
          this.closeConcurrency.set(error.code === 'VERSION_CONFLICT');
        },
      });
  }

  openStatus(categoria: CategoriaTarifa, mode: 'activate' | 'deactivate'): void {
    if (!this.canChangeCategoriaStatus()) {
      return;
    }

    this.statusCategoria.set(categoria);
    this.statusMode.set(mode);
    this.statusForm.reset({ motivo: '' });
    this.statusFieldErrors.set({});
    this.statusError.set(null);
    this.statusActiveTariffsCount.set(null);
    this.statusConcurrency.set(false);
    this.statusOpen.set(true);
  }

  closeStatus(): void {
    if (!this.saving()) {
      this.statusOpen.set(false);
    }
  }

  confirmStatus(): void {
    if (this.saving()) {
      return;
    }

    const categoria = this.statusCategoria();

    if (!categoria) {
      return;
    }

    const motive = this.statusForm.getRawValue().motivo.trim();

    if (this.statusMode() === 'deactivate' && motive === '') {
      this.statusForm.markAllAsTouched();
      return;
    }

    const payload: StatusCategoriaPayload = {
      version: categoria.version,
      ...(this.statusMode() === 'deactivate' ? { motivo: motive } : {}),
    };

    this.saving.set(true);
    this.statusFieldErrors.set({});
    this.statusError.set(null);
    this.statusActiveTariffsCount.set(null);
    this.statusConcurrency.set(false);

    const request$ = this.statusMode() === 'activate'
      ? this.service.activateCategoria(categoria.id, payload)
      : this.service.deactivateCategoria(categoria.id, payload);

    request$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.saving.set(false);
        this.statusOpen.set(false);
        this.toast.success(
          this.statusMode() === 'deactivate' ? 'Categoría desactivada' : 'Categoría activada',
          'El cambio quedó registrado en la bitácora.',
        );
        this.loadCategorias();
      },
      error: (error: TarifasApiError) => {
        this.saving.set(false);
        this.statusFieldErrors.set(error.errors ?? {});
        this.statusError.set(error.message);
        this.statusActiveTariffsCount.set(error.active_tariffs_count ?? null);
        this.statusConcurrency.set(error.code === 'VERSION_CONFLICT');
      },
    });
  }

  openAplicable(): void {
    this.aplicableForm.reset({
      servicio_id: '',
      sucursal_id: this.defaultAplicableBranch(),
      categoria_tarifa_id: '',
      fecha: this.todayIso(),
    });
    this.aplicableResult.set(null);
    this.aplicableSearched.set(false);
    this.aplicableError.set(null);
    this.aplicableFieldErrors.set({});
    this.aplicableOpen.set(true);
  }

  closeAplicable(): void {
    if (!this.aplicableLoading()) {
      this.aplicableOpen.set(false);
    }
  }

  consultarAplicable(): void {
    if (this.aplicableLoading()) {
      return;
    }

    this.aplicableForm.markAllAsTouched();

    if (this.aplicableForm.invalid) {
      return;
    }

    const raw = this.aplicableForm.getRawValue();
    const params: AplicableParams = {
      servicio_id: Number(raw.servicio_id),
      sucursal_id: Number(raw.sucursal_id),
      categoria_tarifa_id: Number(raw.categoria_tarifa_id),
      fecha: raw.fecha,
    };

    this.aplicableLoading.set(true);
    this.aplicableError.set(null);
    this.aplicableFieldErrors.set({});

    this.service
      .consultarAplicable(params)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.aplicableResult.set(response.data);
          this.aplicableSearched.set(true);
          this.abilities.set(response.can);
          this.aplicableLoading.set(false);
        },
        error: (error: TarifasApiError) => {
          this.aplicableLoading.set(false);
          this.aplicableSearched.set(true);
          this.aplicableFieldErrors.set(error.errors ?? {});
          this.aplicableError.set(error.message);
        },
      });
  }

  fieldError(form: TarifaFormKey, field: string): string | null {
    const errors =
      form === 'categoria'
        ? this.categoriaFieldErrors()
        : form === 'tarifa'
          ? this.tarifaFieldErrors()
          : form === 'close'
            ? this.closeFieldErrors()
            : form === 'status'
              ? this.statusFieldErrors()
              : this.aplicableFieldErrors();

    const backend = errors[field]?.[0];

    if (backend) {
      return backend;
    }

    const control =
      form === 'categoria'
        ? this.categoriaForm.get(field)
        : form === 'tarifa'
          ? this.tarifaForm.get(field)
          : form === 'close'
            ? this.closeForm.get(field)
            : form === 'status'
              ? this.statusForm.get(field)
              : this.aplicableForm.get(field);

    if (!control || !control.touched || control.valid) {
      return null;
    }

    if (control.hasError('required')) {
      return 'Este campo es obligatorio.';
    }
    if (control.hasError('maxlength')) {
      return 'El valor ingresado es demasiado largo.';
    }
    if (control.hasError('pattern')) {
      return 'Usá un monto no negativo con hasta dos decimales.';
    }

    return 'Revisá este campo.';
  }

  statusFieldError(): string | null {
    const backend = this.statusFieldErrors()['motivo']?.[0];

    if (backend) {
      return backend;
    }

    if (this.statusMode() === 'deactivate' && this.statusForm.getRawValue().motivo.trim() === '') {
      return 'El motivo es obligatorio para desactivar.';
    }

    return null;
  }

  formatDate(value: string | null): string {
    return value ? this.formatDateValue(value) : '—';
  }

  formatMonto(value: string | null): string {
    return formatBs(value);
  }

  estadoVigenciaColor(estado: EstadoVigencia): 'success' | 'info' | 'gray' {
    return estado === 'vigente' ? 'success' : estado === 'futura' ? 'info' : 'gray';
  }

  estadoVigenciaLabel(estado: EstadoVigencia): string {
    return estado === 'vigente' ? 'Vigente' : estado === 'futura' ? 'Futura' : 'Cerrada';
  }

  sortIndicator(current: string, field: string, direction: string): string {
    return current === field ? (direction === 'asc' ? '↑' : '↓') : '';
  }

  ariaSort(current: string, field: string, direction: string): 'ascending' | 'descending' | 'none' {
    return current === field
      ? direction === 'asc'
        ? 'ascending'
        : 'descending'
      : 'none';
  }

  servicioLabel(servicio: Servicio): string {
    return servicio.area?.nombre
      ? `${servicio.nombre} — ${servicio.area.nombre}`
      : servicio.nombre;
  }

  private handleCategoriaSuccess(title: string): void {
    this.saving.set(false);
    this.categoriaFormOpen.set(false);
    this.toast.success(title, 'Los datos se guardaron correctamente.');
    this.loadCategorias();
    this.loadTarifas();
  }

  private handleTarifaSuccess(title: string): void {
    this.saving.set(false);
    this.tarifaFormOpen.set(false);
    this.detailOpen.set(false);
    this.toast.success(title, 'Los datos se guardaron correctamente.');
    this.loadTarifas();
  }

  private applyCategoriaFormError(error: TarifasApiError): void {
    this.saving.set(false);
    this.categoriaFieldErrors.set(error.errors ?? {});
    this.categoriaFormError.set(error.message);
    this.categoriaConcurrency.set(error.code === 'VERSION_CONFLICT');
  }

  private applyTarifaFormError(error: TarifasApiError): void {
    this.saving.set(false);
    this.tarifaFieldErrors.set(error.errors ?? {});
    this.tarifaFormError.set(error.message);
    this.tarifaConflict.set(error.conflict ?? null);
    this.tarifaConcurrency.set(error.code === 'VERSION_CONFLICT');
  }

  private clearCategoriaErrors(): void {
    this.categoriaFieldErrors.set({});
    this.categoriaFormError.set(null);
    this.categoriaConcurrency.set(false);
  }

  private clearTarifaErrors(): void {
    this.tarifaFieldErrors.set({});
    this.tarifaFormError.set(null);
    this.tarifaConcurrency.set(false);
    this.tarifaConflict.set(null);
  }

  private clearCloseErrors(): void {
    this.closeFieldErrors.set({});
    this.closeError.set(null);
    this.closeConcurrency.set(false);
  }

  private handleError(error: TarifasApiError): void {
    this.loading.set(false);
    this.error.set(error);
  }

  private subscribeSearch(control: FormControl<string>, callback: () => void): void {
    control.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe(callback);
  }

  private defaultFormBranch(): string {
    if (this.isGlobalScope()) {
      return 'global';
    }

    return this.branches().find((branch) => branch.activo)?.id.toString() ?? '';
  }

  private defaultAplicableBranch(): string {
    return this.branches().find((branch) => branch.activo)?.id.toString() ?? '';
  }

  private numberOrUndefined(value: string): number | undefined {
    return value === '' || value === 'global' ? undefined : Number(value);
  }

  private nullIfEmpty(value: string): string | null {
    const normalized = value.trim();

    return normalized === '' ? null : normalized;
  }

  private todayIso(): string {
    return new Intl.DateTimeFormat('en-CA', {
      timeZone: 'America/La_Paz',
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    }).format(new Date());
  }

  private formatDateValue(value: string): string {
    const date = new Date(`${value}T12:00:00`);

    if (Number.isNaN(date.getTime())) {
      return '—';
    }

    return new Intl.DateTimeFormat('es-BO', {
      dateStyle: 'medium',
      timeZone: 'America/La_Paz',
    }).format(date);
  }
}
