import {
  Component,
  DestroyRef,
  HostListener,
  OnInit,
  computed,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, FormControl, ReactiveFormsModule, Validators } from '@angular/forms';
import { debounceTime, distinctUntilChanged, forkJoin, Observable } from 'rxjs';
import { ToastService } from '../../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
import { PaginationComponent } from '../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { SwitchComponent } from '../../../shared/components/ui/switch/switch.component';
import { TABLE_IMPORTS } from '../../../shared/components/ui/table';
import { TextareaDirective } from '../../../shared/components/ui/textarea/textarea.directive';
import { Branch, BranchListResponse } from '../sucursales/sucursales.models';
import { SucursalesService } from '../sucursales/sucursales.service';
import {
  Ambiente,
  AmbienteCreatePayload,
  AmbienteUpdatePayload,
  Area,
  DetailResponse,
  EMPTY_SALAS_AMBIENTES_ABILITIES,
  ESTADO_CATALOGO_OPTIONS,
  ESTADO_ESPACIO_OPTIONS,
  FormMode,
  Sala,
  SalaCreatePayload,
  SalaUpdatePayload,
  SalasAmbientesAbilities,
  SalasAmbientesApiError,
  SalasAmbientesSection,
  SECTION_OPTIONS,
  StatusEntityType,
  StatusMode,
  StatusPayload,
  SucursalSummary,
  TipoSala,
  TipoSalaPayload,
  TurnoCatalogo,
  TurnoCatalogoPayload,
  USO_OPTIONS,
  UsoAmbiente,
} from './salas-ambientes.models';
import { SalasAmbientesService } from './salas-ambientes.service';

type StatusTarget =
  | { type: 'sala'; entity: Sala }
  | { type: 'ambiente'; entity: Ambiente }
  | { type: 'tipo-sala'; entity: TipoSala }
  | { type: 'turno'; entity: TurnoCatalogo };

@Component({
  selector: 'app-salas-ambientes-page',
  imports: [
    ReactiveFormsModule,
    PageBreadcrumbComponent,
    ComponentCardComponent,
    ButtonComponent,
    BadgeComponent,
    AlertComponent,
    PaginationComponent,
    InputDirective,
    SelectFieldComponent,
    TextareaDirective,
    SwitchComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './salas-ambientes.page.html',
})
export class SalasAmbientesPageComponent implements OnInit {
  private readonly service = inject(SalasAmbientesService);
  private readonly sucursalesService = inject(SucursalesService);
  private readonly toast = inject(ToastService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  readonly activeSection = signal<SalasAmbientesSection>('salas');
  readonly abilities = signal<SalasAmbientesAbilities>(EMPTY_SALAS_AMBIENTES_ABILITIES);
  readonly error = signal<SalasAmbientesApiError | null>(null);
  readonly saving = signal(false);

  readonly branches = signal<Branch[]>([]);
  readonly areas = signal<Area[]>([]);
  readonly tiposSala = signal<TipoSala[]>([]);
  readonly turnos = signal<TurnoCatalogo[]>([]);
  readonly tiposLoading = signal(false);
  readonly turnosLoading = signal(false);

  readonly salas = signal<Sala[]>([]);
  readonly salasLoading = signal(false);
  readonly salasPage = signal(1);
  readonly salasLastPage = signal(1);
  readonly salasTotal = signal(0);
  readonly salasFrom = signal<number | null>(null);
  readonly salasTo = signal<number | null>(null);
  readonly salasSort = signal<'nombre' | 'activo' | 'updated_at'>('nombre');
  readonly salasDirection = signal<'asc' | 'desc'>('asc');
  readonly salaSucursalFilter = signal('');
  readonly salaTipoFilter = signal('');
  readonly salaTurnoFilter = signal('');
  readonly salaEstadoFilter = signal<'activa' | 'inactiva' | ''>('');

  readonly ambientes = signal<Ambiente[]>([]);
  readonly ambientesLoading = signal(false);
  readonly ambientesPage = signal(1);
  readonly ambientesLastPage = signal(1);
  readonly ambientesTotal = signal(0);
  readonly ambientesFrom = signal<number | null>(null);
  readonly ambientesTo = signal<number | null>(null);
  readonly ambientesSort = signal<'nombre' | 'uso' | 'activo' | 'updated_at'>('nombre');
  readonly ambientesDirection = signal<'asc' | 'desc'>('asc');
  readonly ambienteSucursalFilter = signal('');
  readonly ambienteUsoFilter = signal('');
  readonly ambienteEstadoFilter = signal<'activa' | 'inactiva' | ''>('');

  readonly tipoEstadoFilter = signal<'activo' | 'inactivo' | ''>('');
  readonly turnoEstadoFilter = signal<'activo' | 'inactivo' | ''>('');

  readonly salaSearchControl = new FormControl('', { nonNullable: true });
  readonly ambienteSearchControl = new FormControl('', { nonNullable: true });
  readonly tipoSearchControl = new FormControl('', { nonNullable: true });
  readonly turnoSearchControl = new FormControl('', { nonNullable: true });

  readonly salaForm = this.fb.nonNullable.group({
    sucursal_id: ['', [Validators.required]],
    area_id: ['', [Validators.required]],
    tipo_sala_id: ['', [Validators.required]],
    turno_catalogo_id: ['', [Validators.required]],
    nombre: ['', [Validators.required, Validators.maxLength(150)]],
    observaciones: ['', [Validators.maxLength(2000)]],
    activo: [true],
  });

  readonly ambienteForm = this.fb.nonNullable.group({
    sucursal_id: ['', [Validators.required]],
    nombre: ['', [Validators.required, Validators.maxLength(150)]],
    uso: ['', [Validators.required]],
    observaciones: ['', [Validators.maxLength(2000)]],
    activo: [true],
  });

  readonly tipoForm = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.maxLength(100)]],
    activo: [true],
  });

  readonly turnoForm = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.maxLength(50)]],
    orden: [0, [Validators.required, Validators.min(0)]],
    activo: [true],
  });

  readonly statusForm = this.fb.nonNullable.group({
    motivo: ['', [Validators.maxLength(1000)]],
  });

  readonly salaFormOpen = signal(false);
  readonly salaFormMode = signal<FormMode>('create');
  readonly editingSala = signal<Sala | null>(null);
  readonly salaFieldErrors = signal<Record<string, string[]>>({});
  readonly salaFormError = signal<string | null>(null);
  readonly salaConflict = signal<string | null>(null);
  readonly salaConcurrency = signal(false);

  readonly ambienteFormOpen = signal(false);
  readonly ambienteFormMode = signal<FormMode>('create');
  readonly editingAmbiente = signal<Ambiente | null>(null);
  readonly ambienteFieldErrors = signal<Record<string, string[]>>({});
  readonly ambienteFormError = signal<string | null>(null);
  readonly ambienteConflict = signal<string | null>(null);
  readonly ambienteConcurrency = signal(false);

  readonly tipoFormOpen = signal(false);
  readonly tipoFormMode = signal<FormMode>('create');
  readonly editingTipo = signal<TipoSala | null>(null);
  readonly tipoFieldErrors = signal<Record<string, string[]>>({});
  readonly tipoFormError = signal<string | null>(null);
  readonly tipoConcurrency = signal(false);

  readonly turnoFormOpen = signal(false);
  readonly turnoFormMode = signal<FormMode>('create');
  readonly editingTurno = signal<TurnoCatalogo | null>(null);
  readonly turnoFieldErrors = signal<Record<string, string[]>>({});
  readonly turnoFormError = signal<string | null>(null);
  readonly turnoConcurrency = signal(false);

  readonly detailOpen = signal(false);
  readonly detailType = signal<'sala' | 'ambiente'>('sala');
  readonly selectedSala = signal<Sala | null>(null);
  readonly selectedAmbiente = signal<Ambiente | null>(null);

  readonly statusOpen = signal(false);
  readonly statusTarget = signal<StatusTarget | null>(null);
  readonly statusMode = signal<StatusMode>('deactivate');
  readonly statusFieldErrors = signal<Record<string, string[]>>({});
  readonly statusError = signal<string | null>(null);
  readonly statusConflict = signal(false);

  readonly sectionOptions = SECTION_OPTIONS;
  readonly estadoEspacioOptions = ESTADO_ESPACIO_OPTIONS;
  readonly estadoCatalogoOptions = ESTADO_CATALOGO_OPTIONS;
  readonly usoOptions = USO_OPTIONS;

  readonly isGlobalScope = computed(() => this.abilities().global_scope);
  readonly canCreateSala = computed(() => this.abilities().salas.create);
  readonly canEditSalaStructure = computed(() => this.abilities().salas.update_structure);
  readonly canEditSalaData = computed(() => this.abilities().salas.update_data);
  readonly canChangeSalaStatus = computed(() => this.abilities().salas.change_status);
  readonly canCreateAmbiente = computed(() => this.abilities().ambientes.create);
  readonly canEditAmbienteStructure = computed(
    () => this.abilities().ambientes.update_structure,
  );
  readonly canEditAmbienteData = computed(() => this.abilities().ambientes.update_data);
  readonly canChangeAmbienteStatus = computed(
    () => this.abilities().ambientes.change_status,
  );
  readonly canAdminTipos = computed(() => this.abilities().catalogos.tipos_sala.create);
  readonly canAdminTurnos = computed(() => this.abilities().catalogos.turnos.create);

  readonly branchFilterOptions = computed(() => [
    { value: '', label: 'Todas las sucursales' },
    ...this.branches().map((branch) => ({ value: String(branch.id), label: branch.nombre })),
  ]);

  readonly branchFormOptions = computed(() =>
    this.branches().map((branch) => ({ value: String(branch.id), label: branch.nombre })),
  );

  readonly salaBranchOptions = computed(() =>
    this.withCurrentBranch(this.editingSala()?.sucursal ?? null),
  );

  readonly ambienteBranchOptions = computed(() =>
    this.withCurrentBranch(this.editingAmbiente()?.sucursal ?? null),
  );

  readonly areaOptions = computed(() =>
    this.areas().map((area) => ({
      value: String(area.id),
      label: area.activo ? area.nombre : `${area.nombre} (inactiva)`,
    })),
  );

  readonly tipoOptions = computed(() =>
    this.tiposSala().map((tipo) => ({
      value: String(tipo.id),
      label: tipo.activo ? tipo.nombre : `${tipo.nombre} (inactivo)`,
    })),
  );

  readonly tipoFilterOptions = computed(() => [
    { value: '', label: 'Todos los tipos' },
    ...this.tiposSala().map((tipo) => ({ value: String(tipo.id), label: tipo.nombre })),
  ]);

  readonly turnoOptions = computed(() =>
    this.turnos().map((turno) => ({
      value: String(turno.id),
      label: turno.activo ? turno.nombre : `${turno.nombre} (inactivo)`,
    })),
  );

  readonly turnoFilterOptions = computed(() => [
    { value: '', label: 'Todos los turnos' },
    ...this.turnos().map((turno) => ({ value: String(turno.id), label: turno.nombre })),
  ]);

  readonly usoFilterOptions = computed(() => [
    { value: '', label: 'Todos los usos' },
    ...USO_OPTIONS.map((uso) => ({ value: uso.value, label: uso.label })),
  ]);

  ngOnInit(): void {
    this.loadCatalogs();
    this.loadSalas();

    this.subscribeSearch(this.salaSearchControl, () => {
      this.salasPage.set(1);
      this.loadSalas();
    });
    this.subscribeSearch(this.ambienteSearchControl, () => {
      this.ambientesPage.set(1);
      this.loadAmbientes();
    });
    this.subscribeSearch(this.tipoSearchControl, () => this.loadTiposSala());
    this.subscribeSearch(this.turnoSearchControl, () => this.loadTurnosCatalogo());
  }

  @HostListener('document:keydown.escape')
  onEscape(): void {
    if (this.statusOpen()) {
      this.closeStatus();
      return;
    }
    if (this.salaFormOpen()) {
      this.closeSalaForm();
      return;
    }
    if (this.ambienteFormOpen()) {
      this.closeAmbienteForm();
      return;
    }
    if (this.tipoFormOpen()) {
      this.closeTipoForm();
      return;
    }
    if (this.turnoFormOpen()) {
      this.closeTurnoForm();
      return;
    }
    if (this.detailOpen()) {
      this.closeDetail();
    }
  }

  setSection(section: SalasAmbientesSection): void {
    this.activeSection.set(section);
    this.error.set(null);

    switch (section) {
      case 'salas':
        this.loadSalas();
        break;
      case 'ambientes':
        this.loadAmbientes();
        break;
      case 'tipos-sala':
        this.loadTiposSala();
        break;
      case 'turnos':
        this.loadTurnosCatalogo();
        break;
    }
  }

  loadCatalogs(): void {
    this.tiposLoading.set(true);
    this.turnosLoading.set(true);

    forkJoin({
      tipos: this.service.listTiposSala({}),
      turnos: this.service.listTurnosCatalogo({}),
      areas: this.service.listAreas(),
    })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.tiposSala.set(response.tipos.data);
          this.turnos.set(response.turnos.data);
          this.areas.set(response.areas.data);
          this.abilities.set(response.tipos.can);
          this.tiposLoading.set(false);
          this.turnosLoading.set(false);
          this.loadBranchesIfNeeded();
        },
        error: (error: SalasAmbientesApiError) => {
          this.tiposLoading.set(false);
          this.turnosLoading.set(false);
          this.error.set(error);
        },
      });
  }

  loadTiposSala(): void {
    this.tiposLoading.set(true);

    this.service
      .listTiposSala({
        search: this.tipoSearchControl.value.trim() || undefined,
        estado: this.tipoEstadoFilter() || undefined,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.tiposSala.set(response.data);
          this.abilities.set(response.can);
          this.tiposLoading.set(false);
        },
        error: (error: SalasAmbientesApiError) => {
          this.tiposLoading.set(false);
          this.error.set(error);
        },
      });
  }

  loadTurnosCatalogo(): void {
    this.turnosLoading.set(true);

    this.service
      .listTurnosCatalogo({
        search: this.turnoSearchControl.value.trim() || undefined,
        estado: this.turnoEstadoFilter() || undefined,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.turnos.set(response.data);
          this.abilities.set(response.can);
          this.turnosLoading.set(false);
        },
        error: (error: SalasAmbientesApiError) => {
          this.turnosLoading.set(false);
          this.error.set(error);
        },
      });
  }

  loadSalas(): void {
    this.salasLoading.set(true);
    this.error.set(null);

    this.service
      .listSalas({
        search: this.salaSearchControl.value.trim() || undefined,
        sucursal_id: this.numberOrUndefined(this.salaSucursalFilter()),
        tipo_sala_id: this.numberOrUndefined(this.salaTipoFilter()),
        turno_catalogo_id: this.numberOrUndefined(this.salaTurnoFilter()),
        estado: this.salaEstadoFilter() || undefined,
        page: this.salasPage(),
        per_page: 10,
        sort: this.salasSort(),
        direction: this.salasDirection(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.salas.set(response.data);
          this.abilities.set(response.can);
          this.salasPage.set(response.meta.current_page);
          this.salasLastPage.set(response.meta.last_page);
          this.salasTotal.set(response.meta.total);
          this.salasFrom.set(response.meta.from);
          this.salasTo.set(response.meta.to);
          this.salasLoading.set(false);
          this.loadBranchesIfNeeded();

          if (response.data.length === 0 && response.meta.current_page > 1) {
            this.salasPage.set(response.meta.current_page - 1);
            this.loadSalas();
          }
        },
        error: (error: SalasAmbientesApiError) => {
          this.salasLoading.set(false);
          this.error.set(error);
        },
      });
  }

  loadAmbientes(): void {
    this.ambientesLoading.set(true);
    this.error.set(null);

    this.service
      .listAmbientes({
        search: this.ambienteSearchControl.value.trim() || undefined,
        sucursal_id: this.numberOrUndefined(this.ambienteSucursalFilter()),
        uso: (this.ambienteUsoFilter() as UsoAmbiente | '') || undefined,
        estado: this.ambienteEstadoFilter() || undefined,
        page: this.ambientesPage(),
        per_page: 10,
        sort: this.ambientesSort(),
        direction: this.ambientesDirection(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.ambientes.set(response.data);
          this.abilities.set(response.can);
          this.ambientesPage.set(response.meta.current_page);
          this.ambientesLastPage.set(response.meta.last_page);
          this.ambientesTotal.set(response.meta.total);
          this.ambientesFrom.set(response.meta.from);
          this.ambientesTo.set(response.meta.to);
          this.ambientesLoading.set(false);
          this.loadBranchesIfNeeded();

          if (response.data.length === 0 && response.meta.current_page > 1) {
            this.ambientesPage.set(response.meta.current_page - 1);
            this.loadAmbientes();
          }
        },
        error: (error: SalasAmbientesApiError) => {
          this.ambientesLoading.set(false);
          this.error.set(error);
        },
      });
  }

  loadBranchesIfNeeded(): void {
    if (!this.isGlobalScope() || this.branches().length > 0) {
      return;
    }

    this.sucursalesService
      .list({ per_page: 50, sort: 'nombre', direction: 'asc' })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response: BranchListResponse) => this.branches.set(response.data),
        error: (error: SalasAmbientesApiError) => this.error.set(error),
      });
  }

  onSalaSucursalFilter(value: string): void {
    this.salaSucursalFilter.set(value);
    this.salasPage.set(1);
    this.loadSalas();
  }

  onSalaTipoFilter(value: string): void {
    this.salaTipoFilter.set(value);
    this.salasPage.set(1);
    this.loadSalas();
  }

  onSalaTurnoFilter(value: string): void {
    this.salaTurnoFilter.set(value);
    this.salasPage.set(1);
    this.loadSalas();
  }

  onSalaEstadoFilter(value: string): void {
    this.salaEstadoFilter.set(value as 'activa' | 'inactiva' | '');
    this.salasPage.set(1);
    this.loadSalas();
  }

  onAmbienteSucursalFilter(value: string): void {
    this.ambienteSucursalFilter.set(value);
    this.ambientesPage.set(1);
    this.loadAmbientes();
  }

  onAmbienteUsoFilter(value: string): void {
    this.ambienteUsoFilter.set(value);
    this.ambientesPage.set(1);
    this.loadAmbientes();
  }

  onAmbienteEstadoFilter(value: string): void {
    this.ambienteEstadoFilter.set(value as 'activa' | 'inactiva' | '');
    this.ambientesPage.set(1);
    this.loadAmbientes();
  }

  onTipoEstadoFilter(value: string): void {
    this.tipoEstadoFilter.set(value as 'activo' | 'inactivo' | '');
    this.loadTiposSala();
  }

  onTurnoEstadoFilter(value: string): void {
    this.turnoEstadoFilter.set(value as 'activo' | 'inactivo' | '');
    this.loadTurnosCatalogo();
  }

  sortSalas(field: 'nombre' | 'activo' | 'updated_at'): void {
    if (this.salasSort() === field) {
      this.salasDirection.set(this.salasDirection() === 'asc' ? 'desc' : 'asc');
    } else {
      this.salasSort.set(field);
      this.salasDirection.set('asc');
    }
    this.salasPage.set(1);
    this.loadSalas();
  }

  sortAmbientes(field: 'nombre' | 'uso' | 'activo' | 'updated_at'): void {
    if (this.ambientesSort() === field) {
      this.ambientesDirection.set(this.ambientesDirection() === 'asc' ? 'desc' : 'asc');
    } else {
      this.ambientesSort.set(field);
      this.ambientesDirection.set('asc');
    }
    this.ambientesPage.set(1);
    this.loadAmbientes();
  }

  ariaSortSalas(field: string): 'ascending' | 'descending' | 'none' {
    return this.salasSort() === field
      ? this.salasDirection() === 'asc'
        ? 'ascending'
        : 'descending'
      : 'none';
  }

  ariaSortAmbientes(field: string): 'ascending' | 'descending' | 'none' {
    return this.ambientesSort() === field
      ? this.ambientesDirection() === 'asc'
        ? 'ascending'
        : 'descending'
      : 'none';
  }

  sortIndicatorSalas(field: string): string {
    return this.salasSort() === field ? (this.salasDirection() === 'asc' ? '↑' : '↓') : '';
  }

  sortIndicatorAmbientes(field: string): string {
    return this.ambientesSort() === field
      ? this.ambientesDirection() === 'asc'
        ? '↑'
        : '↓'
      : '';
  }

  goToSalasPage(page: number): void {
    this.salasPage.set(page);
    this.loadSalas();
  }

  goToAmbientesPage(page: number): void {
    this.ambientesPage.set(page);
    this.loadAmbientes();
  }

  openSalaDetail(sala: Sala): void {
    this.service
      .getSala(sala.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.selectedSala.set(response.data);
          this.abilities.set(response.can);
          this.detailType.set('sala');
          this.detailOpen.set(true);
        },
        error: (error: SalasAmbientesApiError) => this.error.set(error),
      });
  }

  openAmbienteDetail(ambiente: Ambiente): void {
    this.service
      .getAmbiente(ambiente.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.selectedAmbiente.set(response.data);
          this.abilities.set(response.can);
          this.detailType.set('ambiente');
          this.detailOpen.set(true);
        },
        error: (error: SalasAmbientesApiError) => this.error.set(error),
      });
  }

  closeDetail(): void {
    this.detailOpen.set(false);
  }

  openSalaCreate(): void {
    if (!this.canCreateSala()) {
      return;
    }

    this.salaFormMode.set('create');
    this.editingSala.set(null);
    this.salaForm.reset({
      sucursal_id: '',
      area_id: '',
      tipo_sala_id: '',
      turno_catalogo_id: '',
      nombre: '',
      observaciones: '',
      activo: true,
    });
    this.salaForm.enable();
    this.clearSalaFormErrors();
    this.salaFormOpen.set(true);
  }

  openSalaEdit(sala: Sala): void {
    if (!this.canEditSalaStructure() && !this.canEditSalaData()) {
      return;
    }

    this.service
      .getSala(sala.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const latest = response.data;
          this.abilities.set(response.can);
          this.salaFormMode.set('edit');
          this.editingSala.set(latest);
          this.salaForm.reset({
            sucursal_id: String(latest.sucursal?.id ?? ''),
            area_id: String(latest.area?.id ?? ''),
            tipo_sala_id: String(latest.tipo_sala?.id ?? ''),
            turno_catalogo_id: String(latest.turno?.id ?? ''),
            nombre: latest.nombre,
            observaciones: latest.observaciones ?? '',
            activo: latest.activo,
          });

          if (this.canEditSalaStructure()) {
            this.salaForm.controls.sucursal_id.enable();
            this.salaForm.controls.area_id.enable();
            this.salaForm.controls.tipo_sala_id.enable();
            this.salaForm.controls.turno_catalogo_id.enable();
          } else {
            this.salaForm.controls.sucursal_id.disable();
            this.salaForm.controls.area_id.disable();
            this.salaForm.controls.tipo_sala_id.disable();
            this.salaForm.controls.turno_catalogo_id.disable();
          }

          if (this.canEditSalaData()) {
            this.salaForm.controls.nombre.enable();
            this.salaForm.controls.observaciones.enable();
          } else {
            this.salaForm.controls.nombre.disable();
            this.salaForm.controls.observaciones.disable();
          }

          this.salaForm.controls.activo.disable();
          this.clearSalaFormErrors();
          this.salaFormOpen.set(true);
        },
        error: (error: SalasAmbientesApiError) => this.error.set(error),
      });
  }

  closeSalaForm(): void {
    if (!this.saving()) {
      this.salaFormOpen.set(false);
    }
  }

  saveSala(): void {
    if (this.saving()) {
      return;
    }

    this.salaForm.markAllAsTouched();

    if (this.salaForm.invalid) {
      return;
    }

    const raw = this.salaForm.getRawValue();
    this.saving.set(true);
    this.clearSalaFormErrors();

    if (this.salaFormMode() === 'create') {
      const payload: SalaCreatePayload = {
        sucursal_id: Number(raw.sucursal_id),
        area_id: Number(raw.area_id),
        tipo_sala_id: Number(raw.tipo_sala_id),
        turno_catalogo_id: Number(raw.turno_catalogo_id),
        nombre: raw.nombre.trim(),
        observaciones: this.nullIfEmpty(raw.observaciones),
        activo: raw.activo,
      };

      this.service
        .createSala(payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: () => this.handleSalaSuccess('Sala creada'),
          error: (error: SalasAmbientesApiError) => this.applySalaFormError(error),
        });
      return;
    }

    const sala = this.editingSala();

    if (!sala) {
      this.saving.set(false);
      return;
    }

    const payload: SalaUpdatePayload = { version: sala.version };

    if (this.canEditSalaStructure()) {
      payload.sucursal_id = Number(raw.sucursal_id);
      payload.area_id = Number(raw.area_id);
      payload.tipo_sala_id = Number(raw.tipo_sala_id);
      payload.turno_catalogo_id = Number(raw.turno_catalogo_id);
    }

    if (this.canEditSalaData()) {
      payload.nombre = raw.nombre.trim();
      payload.observaciones = this.nullIfEmpty(raw.observaciones);
    }

    this.service
      .updateSala(sala.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => this.handleSalaSuccess('Sala actualizada'),
        error: (error: SalasAmbientesApiError) => this.applySalaFormError(error),
      });
  }

  openAmbienteCreate(): void {
    if (!this.canCreateAmbiente()) {
      return;
    }

    this.ambienteFormMode.set('create');
    this.editingAmbiente.set(null);
    this.ambienteForm.reset({
      sucursal_id: '',
      nombre: '',
      uso: '',
      observaciones: '',
      activo: true,
    });
    this.ambienteForm.enable();
    this.clearAmbienteFormErrors();
    this.ambienteFormOpen.set(true);
  }

  openAmbienteEdit(ambiente: Ambiente): void {
    if (!this.canEditAmbienteStructure() && !this.canEditAmbienteData()) {
      return;
    }

    this.service
      .getAmbiente(ambiente.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const latest = response.data;
          this.abilities.set(response.can);
          this.ambienteFormMode.set('edit');
          this.editingAmbiente.set(latest);
          this.ambienteForm.reset({
            sucursal_id: String(latest.sucursal?.id ?? ''),
            nombre: latest.nombre,
            uso: latest.uso,
            observaciones: latest.observaciones ?? '',
            activo: latest.activo,
          });

          if (this.canEditAmbienteStructure()) {
            this.ambienteForm.controls.sucursal_id.enable();
            this.ambienteForm.controls.uso.enable();
          } else {
            this.ambienteForm.controls.sucursal_id.disable();
            this.ambienteForm.controls.uso.disable();
          }

          if (this.canEditAmbienteData()) {
            this.ambienteForm.controls.nombre.enable();
            this.ambienteForm.controls.observaciones.enable();
          } else {
            this.ambienteForm.controls.nombre.disable();
            this.ambienteForm.controls.observaciones.disable();
          }

          this.ambienteForm.controls.activo.disable();
          this.clearAmbienteFormErrors();
          this.ambienteFormOpen.set(true);
        },
        error: (error: SalasAmbientesApiError) => this.error.set(error),
      });
  }

  closeAmbienteForm(): void {
    if (!this.saving()) {
      this.ambienteFormOpen.set(false);
    }
  }

  saveAmbiente(): void {
    if (this.saving()) {
      return;
    }

    this.ambienteForm.markAllAsTouched();

    if (this.ambienteForm.invalid) {
      return;
    }

    const raw = this.ambienteForm.getRawValue();
    this.saving.set(true);
    this.clearAmbienteFormErrors();

    if (this.ambienteFormMode() === 'create') {
      const payload: AmbienteCreatePayload = {
        sucursal_id: Number(raw.sucursal_id),
        nombre: raw.nombre.trim(),
        uso: raw.uso as UsoAmbiente,
        observaciones: this.nullIfEmpty(raw.observaciones),
        activo: raw.activo,
      };

      this.service
        .createAmbiente(payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: () => this.handleAmbienteSuccess('Ambiente creado'),
          error: (error: SalasAmbientesApiError) => this.applyAmbienteFormError(error),
        });
      return;
    }

    const ambiente = this.editingAmbiente();

    if (!ambiente) {
      this.saving.set(false);
      return;
    }

    const payload: AmbienteUpdatePayload = { version: ambiente.version };

    if (this.canEditAmbienteStructure()) {
      payload.sucursal_id = Number(raw.sucursal_id);
      payload.uso = raw.uso as UsoAmbiente;
    }

    if (this.canEditAmbienteData()) {
      payload.nombre = raw.nombre.trim();
      payload.observaciones = this.nullIfEmpty(raw.observaciones);
    }

    this.service
      .updateAmbiente(ambiente.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => this.handleAmbienteSuccess('Ambiente actualizado'),
        error: (error: SalasAmbientesApiError) => this.applyAmbienteFormError(error),
      });
  }

  openTipoCreate(): void {
    if (!this.canAdminTipos()) {
      return;
    }

    this.tipoFormMode.set('create');
    this.editingTipo.set(null);
    this.tipoForm.reset({ nombre: '', activo: true });
    this.tipoForm.enable();
    this.clearTipoFormErrors();
    this.tipoFormOpen.set(true);
  }

  openTipoEdit(tipo: TipoSala): void {
    this.service
      .getTipoSala(tipo.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.tipoFormMode.set('edit');
          this.editingTipo.set(response.data);
          this.tipoForm.reset({ nombre: response.data.nombre, activo: response.data.activo });
          this.tipoForm.controls.activo.disable();
          this.clearTipoFormErrors();
          this.tipoFormOpen.set(true);
        },
        error: (error: SalasAmbientesApiError) => this.error.set(error),
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
    this.saving.set(true);
    this.clearTipoFormErrors();

    if (this.tipoFormMode() === 'create') {
      const payload: TipoSalaPayload = { nombre: raw.nombre.trim(), activo: raw.activo };

      this.service
        .createTipoSala(payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: () => this.handleTipoSuccess('Tipo de sala creado'),
          error: (error: SalasAmbientesApiError) => this.applyTipoFormError(error),
        });
      return;
    }

    const tipo = this.editingTipo();

    if (!tipo) {
      this.saving.set(false);
      return;
    }

    this.service
      .updateTipoSala(tipo.id, { nombre: raw.nombre.trim(), version: tipo.version })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => this.handleTipoSuccess('Tipo de sala actualizado'),
        error: (error: SalasAmbientesApiError) => this.applyTipoFormError(error),
      });
  }

  openTurnoCreate(): void {
    if (!this.canAdminTurnos()) {
      return;
    }

    this.turnoFormMode.set('create');
    this.editingTurno.set(null);
    this.turnoForm.reset({ nombre: '', orden: 0, activo: true });
    this.turnoForm.enable();
    this.clearTurnoFormErrors();
    this.turnoFormOpen.set(true);
  }

  openTurnoEdit(turno: TurnoCatalogo): void {
    this.service
      .getTurnoCatalogo(turno.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.turnoFormMode.set('edit');
          this.editingTurno.set(response.data);
          this.turnoForm.reset({
            nombre: response.data.nombre,
            orden: response.data.orden,
            activo: response.data.activo,
          });
          this.turnoForm.controls.activo.disable();
          this.clearTurnoFormErrors();
          this.turnoFormOpen.set(true);
        },
        error: (error: SalasAmbientesApiError) => this.error.set(error),
      });
  }

  closeTurnoForm(): void {
    if (!this.saving()) {
      this.turnoFormOpen.set(false);
    }
  }

  saveTurno(): void {
    if (this.saving()) {
      return;
    }

    this.turnoForm.markAllAsTouched();

    if (this.turnoForm.invalid) {
      return;
    }

    const raw = this.turnoForm.getRawValue();
    this.saving.set(true);
    this.clearTurnoFormErrors();

    if (this.turnoFormMode() === 'create') {
      const payload: TurnoCatalogoPayload = {
        nombre: raw.nombre.trim(),
        orden: raw.orden,
        activo: raw.activo,
      };

      this.service
        .createTurnoCatalogo(payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: () => this.handleTurnoSuccess('Turno creado'),
          error: (error: SalasAmbientesApiError) => this.applyTurnoFormError(error),
        });
      return;
    }

    const turno = this.editingTurno();

    if (!turno) {
      this.saving.set(false);
      return;
    }

    this.service
      .updateTurnoCatalogo(turno.id, {
        nombre: raw.nombre.trim(),
        orden: raw.orden,
        version: turno.version,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => this.handleTurnoSuccess('Turno actualizado'),
        error: (error: SalasAmbientesApiError) => this.applyTurnoFormError(error),
      });
  }

  reloadSalaConflict(): void {
    const sala = this.editingSala();

    if (!sala) {
      return;
    }

    this.service
      .getSala(sala.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const latest = response.data;
          this.abilities.set(response.can);
          this.editingSala.set(latest);
          this.salaForm.reset({
            sucursal_id: String(latest.sucursal?.id ?? ''),
            area_id: String(latest.area?.id ?? ''),
            tipo_sala_id: String(latest.tipo_sala?.id ?? ''),
            turno_catalogo_id: String(latest.turno?.id ?? ''),
            nombre: latest.nombre,
            observaciones: latest.observaciones ?? '',
            activo: latest.activo,
          });

          if (this.canEditSalaStructure()) {
            this.salaForm.controls.sucursal_id.enable();
            this.salaForm.controls.area_id.enable();
            this.salaForm.controls.tipo_sala_id.enable();
            this.salaForm.controls.turno_catalogo_id.enable();
          } else {
            this.salaForm.controls.sucursal_id.disable();
            this.salaForm.controls.area_id.disable();
            this.salaForm.controls.tipo_sala_id.disable();
            this.salaForm.controls.turno_catalogo_id.disable();
          }

          if (this.canEditSalaData()) {
            this.salaForm.controls.nombre.enable();
            this.salaForm.controls.observaciones.enable();
          } else {
            this.salaForm.controls.nombre.disable();
            this.salaForm.controls.observaciones.disable();
          }

          this.salaForm.controls.activo.disable();
          this.clearSalaFormErrors();
          this.toast.info('Datos recargados', 'Se cargó la última versión de la sala.');
        },
        error: (error: SalasAmbientesApiError) => this.salaFormError.set(error.message),
      });
  }

  reloadAmbienteConflict(): void {
    const ambiente = this.editingAmbiente();

    if (!ambiente) {
      return;
    }

    this.service
      .getAmbiente(ambiente.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const latest = response.data;
          this.abilities.set(response.can);
          this.editingAmbiente.set(latest);
          this.ambienteForm.reset({
            sucursal_id: String(latest.sucursal?.id ?? ''),
            nombre: latest.nombre,
            uso: latest.uso,
            observaciones: latest.observaciones ?? '',
            activo: latest.activo,
          });

          if (this.canEditAmbienteStructure()) {
            this.ambienteForm.controls.sucursal_id.enable();
            this.ambienteForm.controls.uso.enable();
          } else {
            this.ambienteForm.controls.sucursal_id.disable();
            this.ambienteForm.controls.uso.disable();
          }

          if (this.canEditAmbienteData()) {
            this.ambienteForm.controls.nombre.enable();
            this.ambienteForm.controls.observaciones.enable();
          } else {
            this.ambienteForm.controls.nombre.disable();
            this.ambienteForm.controls.observaciones.disable();
          }

          this.ambienteForm.controls.activo.disable();
          this.clearAmbienteFormErrors();
          this.toast.info('Datos recargados', 'Se cargó la última versión del ambiente.');
        },
        error: (error: SalasAmbientesApiError) => this.ambienteFormError.set(error.message),
      });
  }

  reloadTipoConflict(): void {
    const tipo = this.editingTipo();

    if (!tipo) {
      return;
    }

    this.service
      .getTipoSala(tipo.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.editingTipo.set(response.data);
          this.tipoForm.reset({ nombre: response.data.nombre, activo: response.data.activo });
          this.tipoForm.controls.activo.disable();
          this.clearTipoFormErrors();
          this.toast.info('Datos recargados', 'Se cargó la última versión del tipo de sala.');
        },
        error: (error: SalasAmbientesApiError) => this.tipoFormError.set(error.message),
      });
  }

  reloadTurnoConflict(): void {
    const turno = this.editingTurno();

    if (!turno) {
      return;
    }

    this.service
      .getTurnoCatalogo(turno.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.editingTurno.set(response.data);
          this.turnoForm.reset({
            nombre: response.data.nombre,
            orden: response.data.orden,
            activo: response.data.activo,
          });
          this.turnoForm.controls.activo.disable();
          this.clearTurnoFormErrors();
          this.toast.info('Datos recargados', 'Se cargó la última versión del turno.');
        },
        error: (error: SalasAmbientesApiError) => this.turnoFormError.set(error.message),
      });
  }

  openStatus(
    type: StatusEntityType,
    entity: Sala | Ambiente | TipoSala | TurnoCatalogo,
    mode: StatusMode,
  ): void {
    if (type === 'sala' && !this.canChangeSalaStatus()) {
      return;
    }
    if (type === 'ambiente' && !this.canChangeAmbienteStatus()) {
      return;
    }
    if (type === 'tipo-sala' && !this.canAdminTipos()) {
      return;
    }
    if (type === 'turno' && !this.canAdminTurnos()) {
      return;
    }

    this.statusTarget.set({ type, entity } as StatusTarget);
    this.statusMode.set(mode);
    this.statusForm.reset({ motivo: '' });
    this.statusFieldErrors.set({});
    this.statusError.set(null);
    this.statusConflict.set(false);
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

    const motivo = this.statusForm.getRawValue().motivo.trim();

    if (this.statusMode() === 'deactivate') {
      this.statusForm.controls.motivo.markAsTouched();

      if (motivo === '') {
        return;
      }
    }

    const payload: StatusPayload = {
      version: target.entity.version,
      ...(this.statusMode() === 'deactivate' ? { motivo } : {}),
    };

    this.saving.set(true);
    this.statusFieldErrors.set({});
    this.statusError.set(null);
    this.statusConflict.set(false);

    const request$ = this.statusRequest(target, payload);

    request$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.statusOpen.set(false);
        this.toast.success(
          this.statusMode() === 'deactivate' ? 'Registro desactivado' : 'Registro activado',
          this.statusMode() === 'deactivate'
            ? 'El registro no admitirá nuevas operaciones. El historial se conserva.'
            : 'El registro vuelve a estar disponible para nuevas operaciones.',
        );

        if (target.type === 'sala' && this.detailOpen() && this.detailType() === 'sala') {
          this.selectedSala.set(response.data as Sala);
        }
        if (target.type === 'ambiente' && this.detailOpen() && this.detailType() === 'ambiente') {
          this.selectedAmbiente.set(response.data as Ambiente);
        }

        this.reloadForType(target.type);
      },
      error: (error: SalasAmbientesApiError) => {
        this.saving.set(false);

        if (error.status === 422 && error.errors) {
          this.statusFieldErrors.set(error.errors);
          this.statusError.set('Revisá el motivo indicado.');
          return;
        }

        if (error.status === 409) {
          this.statusConflict.set(true);
          this.statusError.set(error.message);
          return;
        }

        this.statusError.set(error.message);
      },
    });
  }

  reloadStatusTarget(): void {
    const target = this.statusTarget();

    if (!target) {
      return;
    }

    const request$ = this.statusReloadRequest(target);

    request$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: (response) => {
        const entity = response.data as Sala | Ambiente | TipoSala | TurnoCatalogo;
        this.statusTarget.set({ type: target.type, entity } as StatusTarget);
        this.statusConflict.set(false);
        this.statusError.set(
          'Se actualizó la versión del registro. Revisá el motivo y confirmá nuevamente.',
        );
      },
      error: (error: SalasAmbientesApiError) => this.statusError.set(error.message),
    });
  }

  fieldError(
    form: 'sala' | 'ambiente' | 'tipo' | 'turno',
    field: string,
  ): string | null {
    const errors =
      form === 'sala'
        ? this.salaFieldErrors()
        : form === 'ambiente'
          ? this.ambienteFieldErrors()
          : form === 'tipo'
            ? this.tipoFieldErrors()
            : this.turnoFieldErrors();

    const backend = errors[field]?.[0];

    if (backend) {
      return backend;
    }

    const control =
      form === 'sala'
        ? this.salaForm.get(field)
        : form === 'ambiente'
          ? this.ambienteForm.get(field)
          : form === 'tipo'
            ? this.tipoForm.get(field)
            : this.turnoForm.get(field);

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
      return 'El valor no puede ser negativo.';
    }

    return 'Revisá este campo.';
  }

  statusFieldError(field: string): string | null {
    const backend = this.statusFieldErrors()[field]?.[0];

    if (backend) {
      return backend;
    }

    const control = this.statusForm.get(field);

    if (!control || !control.touched) {
      return null;
    }

    if (
      field === 'motivo'
      && this.statusMode() === 'deactivate'
      && control.value.trim() === ''
    ) {
      return 'El motivo es obligatorio.';
    }

    if (control.valid) {
      return null;
    }

    return control.hasError('maxlength')
      ? 'El motivo es demasiado largo.'
      : 'Revisá este campo.';
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

  entityLabel(type: StatusEntityType): string {
    return type === 'sala'
      ? 'sala'
      : type === 'ambiente'
        ? 'ambiente'
        : type === 'tipo-sala'
          ? 'tipo de sala'
          : 'turno';
  }

  private statusRequest(
    target: StatusTarget,
    payload: StatusPayload,
  ): Observable<DetailResponse<Sala | Ambiente | TipoSala | TurnoCatalogo>> {
    if (target.type === 'sala') {
      return this.statusMode() === 'activate'
        ? this.service.activateSala(target.entity.id, payload)
        : this.service.deactivateSala(target.entity.id, payload);
    }
    if (target.type === 'ambiente') {
      return this.statusMode() === 'activate'
        ? this.service.activateAmbiente(target.entity.id, payload)
        : this.service.deactivateAmbiente(target.entity.id, payload);
    }
    if (target.type === 'tipo-sala') {
      return this.statusMode() === 'activate'
        ? this.service.activateTipoSala(target.entity.id, payload)
        : this.service.deactivateTipoSala(target.entity.id, payload);
    }

    return this.statusMode() === 'activate'
      ? this.service.activateTurnoCatalogo(target.entity.id, payload)
      : this.service.deactivateTurnoCatalogo(target.entity.id, payload);
  }

  private statusReloadRequest(
    target: StatusTarget,
  ): Observable<DetailResponse<Sala | Ambiente | TipoSala | TurnoCatalogo>> {
    if (target.type === 'sala') {
      return this.service.getSala(target.entity.id);
    }
    if (target.type === 'ambiente') {
      return this.service.getAmbiente(target.entity.id);
    }
    if (target.type === 'tipo-sala') {
      return this.service.getTipoSala(target.entity.id);
    }

    return this.service.getTurnoCatalogo(target.entity.id);
  }

  private reloadForType(type: StatusEntityType): void {
    if (type === 'sala') {
      this.loadSalas();
    } else if (type === 'ambiente') {
      this.loadAmbientes();
    } else if (type === 'tipo-sala') {
      this.loadTiposSala();
    } else {
      this.loadTurnosCatalogo();
    }
  }

  private handleSalaSuccess(title: string): void {
    this.saving.set(false);
    this.salaFormOpen.set(false);
    this.detailOpen.set(false);
    this.toast.success(title, 'Los datos se guardaron correctamente.');
    this.loadSalas();
  }

  private handleAmbienteSuccess(title: string): void {
    this.saving.set(false);
    this.ambienteFormOpen.set(false);
    this.detailOpen.set(false);
    this.toast.success(title, 'Los datos se guardaron correctamente.');
    this.loadAmbientes();
  }

  private handleTipoSuccess(title: string): void {
    this.saving.set(false);
    this.tipoFormOpen.set(false);
    this.toast.success(title, 'Los datos se guardaron correctamente.');
    this.loadTiposSala();
  }

  private handleTurnoSuccess(title: string): void {
    this.saving.set(false);
    this.turnoFormOpen.set(false);
    this.toast.success(title, 'Los datos se guardaron correctamente.');
    this.loadTurnosCatalogo();
  }

  private applySalaFormError(error: SalasAmbientesApiError): void {
    this.saving.set(false);
    this.salaFieldErrors.set(error.errors ?? {});
    this.salaFormError.set(error.message);

    if (error.conflict && 'tipo_sala' in error.conflict) {
      this.salaConflict.set(error.conflict.nombre);
    }

    if (error.status === 409) {
      this.salaConcurrency.set(true);
    }
  }

  private applyAmbienteFormError(error: SalasAmbientesApiError): void {
    this.saving.set(false);
    this.ambienteFieldErrors.set(error.errors ?? {});
    this.ambienteFormError.set(error.message);

    if (error.conflict && 'sucursal' in error.conflict) {
      this.ambienteConflict.set(error.conflict.nombre);
    }

    if (error.status === 409) {
      this.ambienteConcurrency.set(true);
    }
  }

  private applyTipoFormError(error: SalasAmbientesApiError): void {
    this.saving.set(false);
    this.tipoFieldErrors.set(error.errors ?? {});
    this.tipoFormError.set(error.message);

    if (error.status === 409) {
      this.tipoConcurrency.set(true);
    }
  }

  private applyTurnoFormError(error: SalasAmbientesApiError): void {
    this.saving.set(false);
    this.turnoFieldErrors.set(error.errors ?? {});
    this.turnoFormError.set(error.message);

    if (error.status === 409) {
      this.turnoConcurrency.set(true);
    }
  }

  private clearSalaFormErrors(): void {
    this.salaFieldErrors.set({});
    this.salaFormError.set(null);
    this.salaConflict.set(null);
    this.salaConcurrency.set(false);
  }

  private clearAmbienteFormErrors(): void {
    this.ambienteFieldErrors.set({});
    this.ambienteFormError.set(null);
    this.ambienteConflict.set(null);
    this.ambienteConcurrency.set(false);
  }

  private clearTipoFormErrors(): void {
    this.tipoFieldErrors.set({});
    this.tipoFormError.set(null);
    this.tipoConcurrency.set(false);
  }

  private clearTurnoFormErrors(): void {
    this.turnoFieldErrors.set({});
    this.turnoFormError.set(null);
    this.turnoConcurrency.set(false);
  }

  private subscribeSearch(control: FormControl<string>, callback: () => void): void {
    control.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => callback());
  }

  private numberOrUndefined(value: string): number | undefined {
    return value === '' ? undefined : Number(value);
  }

  private withCurrentBranch(
    current: SucursalSummary | null,
  ): { value: string; label: string }[] {
    const options = this.branchFormOptions();

    if (current && !options.some((option) => option.value === String(current.id))) {
      return [...options, { value: String(current.id), label: current.nombre }];
    }

    return options;
  }

  private nullIfEmpty(value: string): string | null {
    const trimmed = value.trim();

    return trimmed === '' ? null : trimmed;
  }
}
