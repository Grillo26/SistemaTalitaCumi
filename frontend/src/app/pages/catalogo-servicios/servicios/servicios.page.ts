import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, FormControl, ReactiveFormsModule, Validators } from '@angular/forms';
import { debounceTime, distinctUntilChanged, Observable } from 'rxjs';
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
import {
  Area,
  AreaPayload,
  CATALOGO_TEXTS,
  COLOR_PRESETS,
  CatalogoServiciosAbilities,
  CatalogoServiciosApiError,
  CatalogoServiciosSection,
  DEFAULT_AREA_COLOR,
  DetailResponse,
  EMPTY_CATALOGO_SERVICIOS_ABILITIES,
  ESTADO_OPTIONS,
  EstadoCatalogo,
  FormMode,
  SECTION_OPTIONS,
  Servicio,
  ServicioPayload,
  StatusEntityType,
  StatusMode,
  StatusPayload,
  TipoServicio,
  TipoServicioPayload,
} from './servicios.models';
import { CatalogoServiciosService } from './servicios.service';

type StatusTarget =
  | { type: 'area'; entity: Area }
  | { type: 'servicio'; entity: Servicio }
  | { type: 'tipo-servicio'; entity: TipoServicio };

type FormKey = 'area' | 'servicio' | 'tipo';

@Component({
  selector: 'app-catalogo-servicios-page',
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
    TextareaDirective,
    SwitchComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './servicios.page.html',
})
export class CatalogoServiciosPageComponent implements OnInit {
  private readonly service = inject(CatalogoServiciosService);
  private readonly toast = inject(ToastService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = CATALOGO_TEXTS;
  readonly sectionOptions = SECTION_OPTIONS;
  readonly estadoOptions = ESTADO_OPTIONS;
  readonly colorPresets = COLOR_PRESETS;

  readonly activeSection = signal<CatalogoServiciosSection>('servicios');
  readonly abilities = signal<CatalogoServiciosAbilities>(EMPTY_CATALOGO_SERVICIOS_ABILITIES);
  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly error = signal<CatalogoServiciosApiError | null>(null);

  readonly areas = signal<Area[]>([]);
  readonly tipos = signal<TipoServicio[]>([]);
  readonly servicios = signal<Servicio[]>([]);

  readonly areaSearch = new FormControl('', { nonNullable: true });
  readonly areaEstado = signal<EstadoCatalogo | ''>('');
  readonly areaSort = signal<'nombre' | 'activo' | 'updated_at'>('nombre');
  readonly areaDirection = signal<'asc' | 'desc'>('asc');

  readonly tipoSearch = new FormControl('', { nonNullable: true });
  readonly tipoEstado = signal<EstadoCatalogo | ''>('');
  readonly tipoSort = signal<'nombre' | 'activo' | 'updated_at'>('nombre');
  readonly tipoDirection = signal<'asc' | 'desc'>('asc');

  readonly servicioSearch = new FormControl('', { nonNullable: true });
  readonly servicioAreaFilter = signal('');
  readonly servicioTipoFilter = signal('');
  readonly servicioEstado = signal<EstadoCatalogo | ''>('');
  readonly servicioSort = signal<
    'nombre' | 'area' | 'tipo' | 'duracion_minutos' | 'activo' | 'updated_at'
  >('nombre');
  readonly servicioDirection = signal<'asc' | 'desc'>('asc');
  readonly servicioPage = signal(1);
  readonly servicioLastPage = signal(1);
  readonly servicioTotal = signal(0);
  readonly servicioFrom = signal<number | null>(null);
  readonly servicioTo = signal<number | null>(null);

  readonly areaForm = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.maxLength(100)]],
    descripcion: ['', [Validators.maxLength(2000)]],
    color: [
      DEFAULT_AREA_COLOR,
      [
        Validators.required,
        Validators.pattern(/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/),
      ],
    ],
    activo: [true],
  });

  readonly servicioForm = this.fb.nonNullable.group({
    area_id: ['', [Validators.required]],
    tipo_servicio_id: ['', [Validators.required]],
    nombre: ['', [Validators.required, Validators.maxLength(150)]],
    descripcion: ['', [Validators.maxLength(2000)]],
    duracion_minutos: [50, [Validators.required, Validators.min(1), Validators.max(480)]],
    activo: [true],
  });

  readonly tipoForm = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.maxLength(100)]],
    activo: [true],
  });

  readonly statusForm = this.fb.nonNullable.group({
    motivo: ['', [Validators.required, Validators.maxLength(1000)]],
  });

  readonly areaFormOpen = signal(false);
  readonly areaFormMode = signal<FormMode>('create');
  readonly editingArea = signal<Area | null>(null);
  readonly areaFieldErrors = signal<Record<string, string[]>>({});
  readonly areaFormError = signal<string | null>(null);
  readonly areaConcurrency = signal(false);

  readonly servicioFormOpen = signal(false);
  readonly servicioFormMode = signal<FormMode>('create');
  readonly editingServicio = signal<Servicio | null>(null);
  readonly servicioFieldErrors = signal<Record<string, string[]>>({});
  readonly servicioFormError = signal<string | null>(null);
  readonly servicioConcurrency = signal(false);

  readonly tipoFormOpen = signal(false);
  readonly tipoFormMode = signal<FormMode>('create');
  readonly editingTipo = signal<TipoServicio | null>(null);
  readonly tipoFieldErrors = signal<Record<string, string[]>>({});
  readonly tipoFormError = signal<string | null>(null);
  readonly tipoConcurrency = signal(false);

  readonly detailOpen = signal(false);
  readonly selectedServicio = signal<Servicio | null>(null);

  readonly statusOpen = signal(false);
  readonly statusTarget = signal<StatusTarget | null>(null);
  readonly statusMode = signal<StatusMode>('deactivate');
  readonly statusFieldErrors = signal<Record<string, string[]>>({});
  readonly statusError = signal<string | null>(null);
  readonly statusActiveServicesCount = signal<number | null>(null);
  readonly statusConcurrency = signal(false);

  readonly canCreateArea = computed(() => this.abilities().areas.create);
  readonly canEditArea = computed(() => this.abilities().areas.update);
  readonly canChangeAreaStatus = computed(() => this.abilities().areas.change_status);
  readonly canCreateServicio = computed(() => this.abilities().servicios.create);
  readonly canEditServicio = computed(() => this.abilities().servicios.update);
  readonly canChangeServicioStatus = computed(() => this.abilities().servicios.change_status);
  readonly canCreateTipo = computed(() => this.abilities().tipos_servicio.create);
  readonly canEditTipo = computed(() => this.abilities().tipos_servicio.update);
  readonly canChangeTipoStatus = computed(() => this.abilities().tipos_servicio.change_status);

  readonly areaFilterOptions = computed(() => [
    { value: '', label: 'Todas las áreas' },
    ...this.areas().map((area) => ({
      value: String(area.id),
      label: area.activo ? area.nombre : `${area.nombre} (inactiva)`,
    })),
  ]);

  readonly tipoFilterOptions = computed(() => [
    { value: '', label: 'Todos los tipos' },
    ...this.tipos().map((tipo) => ({
      value: String(tipo.id),
      label: tipo.activo ? tipo.nombre : `${tipo.nombre} (inactivo)`,
    })),
  ]);

  readonly areaFormOptions = computed(() => this.withCurrentArea(this.editingArea()));
  readonly tipoFormOptions = computed(() => this.withCurrentTipo(this.editingServicio()?.tipo_servicio ?? null));

  ngOnInit(): void {
    this.loadAreas();
    this.loadTipos();
    this.loadServicios();

    this.subscribeSearch(this.areaSearch, () => this.loadAreas());
    this.subscribeSearch(this.tipoSearch, () => this.loadTipos());
    this.subscribeSearch(this.servicioSearch, () => {
      this.servicioPage.set(1);
      this.loadServicios();
    });
  }

  setSection(section: CatalogoServiciosSection): void {
    this.activeSection.set(section);
    this.error.set(null);

    if (section === 'areas') {
      this.loadAreas();
    } else if (section === 'tipos') {
      this.loadTipos();
    } else {
      this.loadServicios();
    }
  }

  loadAreas(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service
      .listAreas({
        search: this.areaSearch.value.trim() || undefined,
        estado: this.areaEstado() || undefined,
        sort: this.areaSort(),
        direction: this.areaDirection(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.areas.set(response.data);
          this.abilities.set(response.can);
          this.loading.set(false);
        },
        error: (error: CatalogoServiciosApiError) => this.handleError(error),
      });
  }

  loadTipos(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service
      .listTiposServicio({
        search: this.tipoSearch.value.trim() || undefined,
        estado: this.tipoEstado() || undefined,
        sort: this.tipoSort(),
        direction: this.tipoDirection(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.tipos.set(response.data);
          this.abilities.set(response.can);
          this.loading.set(false);
        },
        error: (error: CatalogoServiciosApiError) => this.handleError(error),
      });
  }

  loadServicios(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service
      .listServicios({
        search: this.servicioSearch.value.trim() || undefined,
        area_id: this.numberOrUndefined(this.servicioAreaFilter()),
        tipo_servicio_id: this.numberOrUndefined(this.servicioTipoFilter()),
        estado: this.servicioEstado() || undefined,
        page: this.servicioPage(),
        per_page: 10,
        sort: this.servicioSort(),
        direction: this.servicioDirection(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.servicios.set(response.data);
          this.abilities.set(response.can);
          this.servicioPage.set(response.meta.current_page);
          this.servicioLastPage.set(response.meta.last_page);
          this.servicioTotal.set(response.meta.total);
          this.servicioFrom.set(response.meta.from);
          this.servicioTo.set(response.meta.to);
          this.loading.set(false);

          if (response.data.length === 0 && response.meta.current_page > 1) {
            this.servicioPage.set(response.meta.current_page - 1);
            this.loadServicios();
          }
        },
        error: (error: CatalogoServiciosApiError) => this.handleError(error),
      });
  }

  onAreaEstado(value: string): void {
    this.areaEstado.set(value as EstadoCatalogo | '');
    this.loadAreas();
  }

  onTipoEstado(value: string): void {
    this.tipoEstado.set(value as EstadoCatalogo | '');
    this.loadTipos();
  }

  onServicioArea(value: string): void {
    this.servicioAreaFilter.set(value);
    this.servicioPage.set(1);
    this.loadServicios();
  }

  onServicioTipo(value: string): void {
    this.servicioTipoFilter.set(value);
    this.servicioPage.set(1);
    this.loadServicios();
  }

  onServicioEstado(value: string): void {
    this.servicioEstado.set(value as EstadoCatalogo | '');
    this.servicioPage.set(1);
    this.loadServicios();
  }

  sortAreas(field: 'nombre' | 'activo' | 'updated_at'): void {
    this.toggleSort(this.areaSort, this.areaDirection, field);
    this.loadAreas();
  }

  sortTipos(field: 'nombre' | 'activo' | 'updated_at'): void {
    this.toggleSort(this.tipoSort, this.tipoDirection, field);
    this.loadTipos();
  }

  sortServicios(
    field: 'nombre' | 'area' | 'tipo' | 'duracion_minutos' | 'activo' | 'updated_at',
  ): void {
    this.toggleSort(this.servicioSort, this.servicioDirection, field);
    this.servicioPage.set(1);
    this.loadServicios();
  }

  ariaSort(current: string, field: string): 'ascending' | 'descending' | 'none' {
    return current === field ? 'ascending' : 'none';
  }

  sortIndicator(current: string, field: string, direction: string): string {
    return current === field ? (direction === 'asc' ? '↑' : '↓') : '';
  }

  goToServiciosPage(page: number): void {
    this.servicioPage.set(page);
    this.loadServicios();
  }

  openAreaCreate(): void {
    if (!this.canCreateArea()) {
      return;
    }

    this.areaFormMode.set('create');
    this.editingArea.set(null);
    this.areaForm.reset({
      nombre: '',
      descripcion: '',
      color: DEFAULT_AREA_COLOR,
      activo: true,
    });
    this.areaForm.enable();
    this.clearAreaErrors();
    this.areaFormOpen.set(true);
  }

  openAreaEdit(area: Area): void {
    if (!this.canEditArea()) {
      return;
    }

    this.service
      .getArea(area.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const latest = response.data;
          this.abilities.set(response.can);
          this.areaFormMode.set('edit');
          this.editingArea.set(latest);
          this.areaForm.reset({
            nombre: latest.nombre,
            descripcion: latest.descripcion ?? '',
            color: latest.color,
            activo: latest.activo,
          });
          this.areaForm.controls.activo.disable();
          this.clearAreaErrors();
          this.areaFormOpen.set(true);
        },
        error: (error: CatalogoServiciosApiError) => this.handleError(error),
      });
  }

  closeAreaForm(): void {
    if (!this.saving()) {
      this.areaFormOpen.set(false);
    }
  }

  saveArea(): void {
    if (this.saving()) {
      return;
    }

    this.areaForm.markAllAsTouched();

    if (this.areaForm.invalid) {
      return;
    }

    const raw = this.areaForm.getRawValue();
    const payload: AreaPayload = {
      nombre: raw.nombre.trim(),
      descripcion: this.nullIfEmpty(raw.descripcion),
      color: raw.color.trim().toUpperCase(),
    };

    this.saving.set(true);
    this.clearAreaErrors();

    if (this.areaFormMode() === 'create') {
      payload.activo = raw.activo;

      this.service
        .createArea(payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: () => this.handleAreaSuccess('Área creada'),
          error: (error: CatalogoServiciosApiError) => this.applyAreaError(error),
        });
      return;
    }

    const area = this.editingArea();

    if (!area) {
      this.saving.set(false);
      return;
    }

    payload.version = area.version;

    this.service
      .updateArea(area.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => this.handleAreaSuccess('Área actualizada'),
        error: (error: CatalogoServiciosApiError) => this.applyAreaError(error),
      });
  }

  openServicioCreate(): void {
    if (!this.canCreateServicio()) {
      return;
    }

    this.servicioFormMode.set('create');
    this.editingServicio.set(null);
    this.servicioForm.reset({
      area_id: '',
      tipo_servicio_id: '',
      nombre: '',
      descripcion: '',
      duracion_minutos: 50,
      activo: true,
    });
    this.servicioForm.enable();
    this.clearServicioErrors();
    this.servicioFormOpen.set(true);
  }

  openServicioEdit(servicio: Servicio): void {
    if (!this.canEditServicio()) {
      return;
    }

    this.service
      .getServicio(servicio.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const latest = response.data;
          this.abilities.set(response.can);
          this.servicioFormMode.set('edit');
          this.editingServicio.set(latest);
          this.servicioForm.reset({
            area_id: String(latest.area?.id ?? ''),
            tipo_servicio_id: String(latest.tipo_servicio?.id ?? ''),
            nombre: latest.nombre,
            descripcion: latest.descripcion ?? '',
            duracion_minutos: latest.duracion_minutos,
            activo: latest.activo,
          });
          this.servicioForm.controls.activo.disable();
          this.clearServicioErrors();
          this.servicioFormOpen.set(true);
        },
        error: (error: CatalogoServiciosApiError) => this.handleError(error),
      });
  }

  closeServicioForm(): void {
    if (!this.saving()) {
      this.servicioFormOpen.set(false);
    }
  }

  saveServicio(): void {
    if (this.saving()) {
      return;
    }

    this.servicioForm.markAllAsTouched();

    if (this.servicioForm.invalid) {
      return;
    }

    const raw = this.servicioForm.getRawValue();
    const payload: ServicioPayload = {
      area_id: Number(raw.area_id),
      tipo_servicio_id: Number(raw.tipo_servicio_id),
      nombre: raw.nombre.trim(),
      descripcion: this.nullIfEmpty(raw.descripcion),
      duracion_minutos: Number(raw.duracion_minutos),
    };

    this.saving.set(true);
    this.clearServicioErrors();

    if (this.servicioFormMode() === 'create') {
      payload.activo = raw.activo;

      this.service
        .createServicio(payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: () => this.handleServicioSuccess('Servicio creado'),
          error: (error: CatalogoServiciosApiError) => this.applyServicioError(error),
        });
      return;
    }

    const servicio = this.editingServicio();

    if (!servicio) {
      this.saving.set(false);
      return;
    }

    payload.version = servicio.version;

    this.service
      .updateServicio(servicio.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => this.handleServicioSuccess('Servicio actualizado'),
        error: (error: CatalogoServiciosApiError) => this.applyServicioError(error),
      });
  }

  openTipoCreate(): void {
    if (!this.canCreateTipo()) {
      return;
    }

    this.tipoFormMode.set('create');
    this.editingTipo.set(null);
    this.tipoForm.reset({ nombre: '', activo: true });
    this.tipoForm.enable();
    this.clearTipoErrors();
    this.tipoFormOpen.set(true);
  }

  openTipoEdit(tipo: TipoServicio): void {
    if (!this.canEditTipo()) {
      return;
    }

    this.service
      .getTipoServicio(tipo.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.tipoFormMode.set('edit');
          this.editingTipo.set(response.data);
          this.tipoForm.reset({ nombre: response.data.nombre, activo: response.data.activo });
          this.tipoForm.controls.activo.disable();
          this.clearTipoErrors();
          this.tipoFormOpen.set(true);
        },
        error: (error: CatalogoServiciosApiError) => this.handleError(error),
      });
  }

  closeTipoForm(): void {
    if (!this.saving()) {
      this.tipoFormOpen.set(false);
    }
  }

  saveTipo(): void {
    if (this.saving()) {
      return;
    }

    this.tipoForm.markAllAsTouched();

    if (this.tipoForm.invalid) {
      return;
    }

    const raw = this.tipoForm.getRawValue();
    const payload: TipoServicioPayload = { nombre: raw.nombre.trim() };
    this.saving.set(true);
    this.clearTipoErrors();

    if (this.tipoFormMode() === 'create') {
      payload.activo = raw.activo;

      this.service
        .createTipoServicio(payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: () => this.handleTipoSuccess('Tipo de servicio creado'),
          error: (error: CatalogoServiciosApiError) => this.applyTipoError(error),
        });
      return;
    }

    const tipo = this.editingTipo();

    if (!tipo) {
      this.saving.set(false);
      return;
    }

    payload.version = tipo.version;

    this.service
      .updateTipoServicio(tipo.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => this.handleTipoSuccess('Tipo de servicio actualizado'),
        error: (error: CatalogoServiciosApiError) => this.applyTipoError(error),
      });
  }

  openServicioDetail(servicio: Servicio): void {
    this.service
      .getServicio(servicio.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.selectedServicio.set(response.data);
          this.abilities.set(response.can);
          this.detailOpen.set(true);
        },
        error: (error: CatalogoServiciosApiError) => this.handleError(error),
      });
  }

  closeServicioDetail(): void {
    this.detailOpen.set(false);
  }

  openStatus(type: StatusEntityType, entity: Area | Servicio | TipoServicio, mode: StatusMode): void {
    if (type === 'area' && !this.canChangeAreaStatus()) {
      return;
    }
    if (type === 'servicio' && !this.canChangeServicioStatus()) {
      return;
    }
    if (type === 'tipo-servicio' && !this.canChangeTipoStatus()) {
      return;
    }

    this.detailOpen.set(false);
    this.statusTarget.set({ type, entity } as StatusTarget);
    this.statusMode.set(mode);
    this.statusForm.reset({ motivo: '' });
    this.statusFieldErrors.set({});
    this.statusError.set(null);
    this.statusActiveServicesCount.set(null);
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

    const target = this.statusTarget();

    if (!target) {
      return;
    }

    this.statusForm.markAllAsTouched();

    if (this.statusForm.invalid) {
      return;
    }

    const payload: StatusPayload = {
      motivo: this.statusForm.getRawValue().motivo.trim(),
      version: target.entity.version,
    };

    this.saving.set(true);
    this.statusFieldErrors.set({});
    this.statusError.set(null);
    this.statusActiveServicesCount.set(null);
    this.statusConcurrency.set(false);

    this.statusRequest(target, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.saving.set(false);
          this.statusOpen.set(false);
          this.toast.success(
            this.statusMode() === 'deactivate' ? 'Registro desactivado' : 'Registro activado',
            'El cambio quedó registrado en la bitácora.',
          );
          this.reloadForType(target.type);
        },
        error: (error: CatalogoServiciosApiError) => {
          this.saving.set(false);
          this.statusFieldErrors.set(error.errors ?? {});
          this.statusError.set(error.message);
          this.statusActiveServicesCount.set(error.active_services_count ?? null);
          this.statusConcurrency.set(error.code === 'VERSION_CONFLICT');
        },
      });
  }

  reloadStatusTarget(): void {
    const target = this.statusTarget();

    if (!target) {
      return;
    }

    this.statusReloadRequest(target)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.statusTarget.set({ type: target.type, entity: response.data } as StatusTarget);
          this.statusConcurrency.set(false);
          this.statusError.set('Se actualizó la versión del registro. Revisá el motivo y confirmá nuevamente.');
        },
        error: (error: CatalogoServiciosApiError) => this.statusError.set(error.message),
      });
  }

  verServiciosDelArea(): void {
    const target = this.statusTarget();

    if (!target || target.type !== 'area') {
      return;
    }

    this.statusOpen.set(false);
    this.activeSection.set('servicios');
    this.servicioAreaFilter.set(String(target.entity.id));
    this.servicioPage.set(1);
    this.loadServicios();
  }

  fieldError(form: FormKey, field: string): string | null {
    const errors =
      form === 'area'
        ? this.areaFieldErrors()
        : form === 'servicio'
          ? this.servicioFieldErrors()
          : this.tipoFieldErrors();

    const backend = errors[field]?.[0];

    if (backend) {
      return backend;
    }

    const control =
      form === 'area'
        ? this.areaForm.get(field)
        : form === 'servicio'
          ? this.servicioForm.get(field)
          : this.tipoForm.get(field);

    if (!control || !control.touched || control.valid) {
      return null;
    }

    if (control.hasError('required')) {
      return 'Este campo es obligatorio.';
    }
    if (control.hasError('maxlength')) {
      return 'El valor ingresado es demasiado largo.';
    }
    if (control.hasError('min')) {
      return 'El valor debe ser mayor que cero.';
    }
    if (control.hasError('max')) {
      return 'El valor supera el máximo permitido.';
    }
    if (control.hasError('pattern')) {
      return 'Usá formato hexadecimal #RGB o #RRGGBB.';
    }

    return 'Revisá este campo.';
  }

  statusFieldError(): string | null {
    const backend = this.statusFieldErrors()['motivo']?.[0];

    if (backend) {
      return backend;
    }

    const control = this.statusForm.controls.motivo;

    if (!control.touched) {
      return null;
    }

    if (control.value.trim() === '') {
      return 'El motivo es obligatorio.';
    }

    return control.hasError('maxlength') ? 'El motivo es demasiado largo.' : null;
  }

  setColorPreset(value: string): void {
    this.areaForm.controls.color.setValue(value);
    this.areaForm.controls.color.markAsTouched();
  }

  onColorPicker(event: Event): void {
    const value = (event.target as HTMLInputElement).value;
    this.areaForm.controls.color.setValue(value.toUpperCase());
    this.areaForm.controls.color.markAsTouched();
  }

  normalizeColor(): void {
    const control = this.areaForm.controls.color;
    control.setValue(control.value.trim().toUpperCase());
    control.markAsTouched();
  }

  entityLabel(type: StatusEntityType): string {
    return type === 'area' ? 'área' : type === 'servicio' ? 'servicio' : 'tipo de servicio';
  }

  targetName(): string {
    const target = this.statusTarget();

    if (!target) {
      return '';
    }

    return target.entity.nombre;
  }

  formatDate(value: string | null): string {
    if (!value) {
      return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
      return '—';
    }

    return new Intl.DateTimeFormat('es-BO', {
      dateStyle: 'medium',
      timeStyle: 'short',
      timeZone: 'America/La_Paz',
    }).format(date);
  }

  private handleAreaSuccess(title: string): void {
    this.saving.set(false);
    this.areaFormOpen.set(false);
    this.toast.success(title, 'Los datos se guardaron correctamente.');
    this.loadAreas();
    this.loadServicios();
  }

  private handleServicioSuccess(title: string): void {
    this.saving.set(false);
    this.servicioFormOpen.set(false);
    this.detailOpen.set(false);
    this.toast.success(title, 'Los datos se guardaron correctamente.');
    this.loadServicios();
  }

  private handleTipoSuccess(title: string): void {
    this.saving.set(false);
    this.tipoFormOpen.set(false);
    this.toast.success(title, 'Los datos se guardaron correctamente.');
    this.loadTipos();
    this.loadServicios();
  }

  private applyAreaError(error: CatalogoServiciosApiError): void {
    this.saving.set(false);
    this.areaFieldErrors.set(error.errors ?? {});
    this.areaFormError.set(error.message);
    this.areaConcurrency.set(error.code === 'VERSION_CONFLICT');
  }

  private applyServicioError(error: CatalogoServiciosApiError): void {
    this.saving.set(false);
    this.servicioFieldErrors.set(error.errors ?? {});
    this.servicioFormError.set(error.message);
    this.servicioConcurrency.set(error.code === 'VERSION_CONFLICT');
  }

  private applyTipoError(error: CatalogoServiciosApiError): void {
    this.saving.set(false);
    this.tipoFieldErrors.set(error.errors ?? {});
    this.tipoFormError.set(error.message);
    this.tipoConcurrency.set(error.code === 'VERSION_CONFLICT');
  }

  private clearAreaErrors(): void {
    this.areaFieldErrors.set({});
    this.areaFormError.set(null);
    this.areaConcurrency.set(false);
  }

  private clearServicioErrors(): void {
    this.servicioFieldErrors.set({});
    this.servicioFormError.set(null);
    this.servicioConcurrency.set(false);
  }

  private clearTipoErrors(): void {
    this.tipoFieldErrors.set({});
    this.tipoFormError.set(null);
    this.tipoConcurrency.set(false);
  }

  private handleError(error: CatalogoServiciosApiError): void {
    this.loading.set(false);
    this.error.set(error);
  }

  private reloadForType(type: StatusEntityType): void {
    if (type === 'area') {
      this.loadAreas();
      this.loadServicios();
    } else if (type === 'servicio') {
      this.loadServicios();
    } else {
      this.loadTipos();
      this.loadServicios();
    }
  }

  private statusRequest(
    target: StatusTarget,
    payload: StatusPayload,
  ): Observable<DetailResponse<Area | Servicio | TipoServicio>> {
    if (target.type === 'area') {
      return this.statusMode() === 'activate'
        ? this.service.activateArea(target.entity.id, payload)
        : this.service.deactivateArea(target.entity.id, payload);
    }

    if (target.type === 'servicio') {
      return this.statusMode() === 'activate'
        ? this.service.activateServicio(target.entity.id, payload)
        : this.service.deactivateServicio(target.entity.id, payload);
    }

    return this.statusMode() === 'activate'
      ? this.service.activateTipoServicio(target.entity.id, payload)
      : this.service.deactivateTipoServicio(target.entity.id, payload);
  }

  private statusReloadRequest(
    target: StatusTarget,
  ): Observable<DetailResponse<Area | Servicio | TipoServicio>> {
    if (target.type === 'area') {
      return this.service.getArea(target.entity.id);
    }

    if (target.type === 'servicio') {
      return this.service.getServicio(target.entity.id);
    }

    return this.service.getTipoServicio(target.entity.id);
  }

  private withCurrentArea(current: Area | null): { value: string; label: string }[] {
    const options = new Map<string, string>();

    for (const area of this.areas().filter((item) => item.activo)) {
      options.set(String(area.id), area.nombre);
    }

    if (current) {
      options.set(
        String(current.id),
        current.activo ? current.nombre : `${current.nombre} (inactiva)`,
      );
    }

    return [...options.entries()].map(([value, label]) => ({ value, label }));
  }

  private withCurrentTipo(
    current: { id: number; nombre: string; activo: boolean } | null,
  ): { value: string; label: string }[] {
    const options = new Map<string, string>();

    for (const tipo of this.tipos().filter((item) => item.activo)) {
      options.set(String(tipo.id), tipo.nombre);
    }

    if (current) {
      options.set(
        String(current.id),
        current.activo ? current.nombre : `${current.nombre} (inactivo)`,
      );
    }

    return [...options.entries()].map(([value, label]) => ({ value, label }));
  }

  private subscribeSearch(control: FormControl<string>, callback: () => void): void {
    control.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe(callback);
  }

  private toggleSort<T extends string>(
    sort: { (): T; set(value: T): void },
    direction: { (): 'asc' | 'desc'; set(value: 'asc' | 'desc'): void },
    field: T,
  ): void {
    if (sort() === field) {
      direction.set(direction() === 'asc' ? 'desc' : 'asc');
    } else {
      sort.set(field);
      direction.set('asc');
    }
  }

  private nullIfEmpty(value: string): string | null {
    const normalized = value.trim();

    return normalized === '' ? null : normalized;
  }

  private numberOrUndefined(value: string): number | undefined {
    return value === '' ? undefined : Number(value);
  }
}
