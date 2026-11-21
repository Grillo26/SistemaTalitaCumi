import {
  Component,
  DestroyRef,
  OnInit,
  computed,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, FormControl, ReactiveFormsModule, Validators } from '@angular/forms';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import { ToastService } from '../../core/services/toast.service';
import { Branch } from '../organizacion/sucursales/sucursales.models';
import { SucursalesService } from '../organizacion/sucursales/sucursales.service';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { InputDirective } from '../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../shared/components/ui/modal/modal.component';
import { PaginationComponent } from '../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../shared/components/ui/select/select-field.component';
import { SwitchComponent } from '../../shared/components/ui/switch/switch.component';
import { TABLE_IMPORTS } from '../../shared/components/ui/table';
import { TextareaDirective } from '../../shared/components/ui/textarea/textarea.directive';
import {
  EMPTY_ESPECIALIDAD_ABILITIES,
  EMPTY_PROFESIONAL_ABILITIES,
  ESTADO_OPTIONS,
  FormMode,
  Profesional,
  ProfesionalCreatePayload,
  ProfesionalListParams,
  ProfesionalOptions,
  ProfesionalPageAbilities,
  ProfesionalSucursalFila,
  MatrizListParams,
  MatrizOptions,
  MatrizRow,
  ProfesionalUpdatePayload,
  ProfesionalesApiError,
  ProfesionalesSection,
  PROFESIONALES_TEXTS,
  SECTION_OPTIONS,
  StatusMode,
  StatusPayload,
  Especialidad,
  EspecialidadPageAbilities,
} from './profesionales.models';
import { ProfesionalesService } from './profesionales.service';

type StatusTarget =
  | { type: 'profesional'; entity: Profesional }
  | { type: 'especialidad'; entity: Especialidad };

@Component({
  selector: 'app-profesionales-page',
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
  templateUrl: './profesionales.page.html',
})
export class ProfesionalesPageComponent implements OnInit {
  private readonly service = inject(ProfesionalesService);
  private readonly sucursalesService = inject(SucursalesService);
  private readonly toast = inject(ToastService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  readonly activeSection = signal<ProfesionalesSection>('directorio');
  readonly pageAbilities = signal<ProfesionalPageAbilities>(EMPTY_PROFESIONAL_ABILITIES);
  readonly specialtyAbilities = signal<EspecialidadPageAbilities>(EMPTY_ESPECIALIDAD_ABILITIES);
  readonly error = signal<ProfesionalesApiError | null>(null);
  readonly saving = signal(false);

  readonly options = signal<ProfesionalOptions>({
    especialidades: [],
    sucursales: [],
    puede_secundarias: false,
  });
  readonly sucursales = signal<Branch[]>([]);

  readonly profesionales = signal<Profesional[]>([]);
  readonly profesionalesLoading = signal(false);
  readonly profesionalPage = signal(1);
  readonly profesionalLastPage = signal(1);
  readonly profesionalTotal = signal(0);
  readonly profesionalFrom = signal<number | null>(null);
  readonly profesionalTo = signal<number | null>(null);
  readonly profesionalSort = signal<'nombre_completo' | 'ci' | 'activo' | 'updated_at'>('nombre_completo');
  readonly profesionalDirection = signal<'asc' | 'desc'>('asc');
  readonly profesionalEspecialidadFilter = signal<number | ''>('');
  readonly profesionalSucursalFilter = signal<number | ''>('');
  readonly profesionalEstadoFilter = signal<'' | 'activo' | 'inactivo'>('');

  readonly especialidades = signal<Especialidad[]>([]);
  readonly especialidadesLoading = signal(false);
  readonly especialidadPage = signal(1);
  readonly especialidadLastPage = signal(1);
  readonly especialidadTotal = signal(0);
  readonly especialidadFrom = signal<number | null>(null);
  readonly especialidadTo = signal<number | null>(null);
  readonly especialidadSort = signal<'nombre' | 'activo' | 'updated_at'>('nombre');
  readonly especialidadDirection = signal<'asc' | 'desc'>('asc');
  readonly especialidadEstadoFilter = signal<'' | 'activo' | 'inactivo'>('');

  readonly matriz = signal<MatrizRow[]>([]);
  readonly matrizLoading = signal(false);
  readonly matrizPage = signal(1);
  readonly matrizLastPage = signal(1);
  readonly matrizTotal = signal(0);
  readonly matrizFrom = signal<number | null>(null);
  readonly matrizTo = signal<number | null>(null);
  readonly matrizSort = signal<'profesional_nombre' | 'profesional_ci' | 'especialidad_nombre' | 'sucursal_nombre'>('profesional_nombre');
  readonly matrizDirection = signal<'asc' | 'desc'>('asc');
  readonly matrizProfesionalFilter = signal<number | ''>('');
  readonly matrizEspecialidadFilter = signal<number | ''>('');
  readonly matrizSucursalFilter = signal<number | ''>('');
  readonly matrizEstadoFilter = signal<'' | 'activo' | 'inactivo'>('');
  readonly matrizSoloAsignables = signal(false);
  readonly matrizViewProfessionals = signal(false);
  readonly matrizOptions = signal<MatrizOptions>({
    profesionales: [],
    especialidades: [],
    sucursales: [],
  });
  readonly profesionalSearch = new FormControl('', { nonNullable: true });
  readonly especialidadSearch = new FormControl('', { nonNullable: true });
  readonly matrizSearch = new FormControl('', { nonNullable: true });

  readonly profesionalForm = this.fb.nonNullable.group({
    nombre_completo: ['', [Validators.required, Validators.maxLength(200)]],
    ci: ['', [Validators.required, Validators.maxLength(30)]],
    telefono: ['', [Validators.maxLength(30)]],
    telefono_2: ['', [Validators.maxLength(30)]],
    email: ['', [Validators.email, Validators.maxLength(150)]],
    matricula: ['', [Validators.maxLength(50)]],
    activo: [true],
    especialidad_principal_id: ['', [Validators.required]],
  });

  readonly especialidadForm = this.fb.nonNullable.group({
    nombre: ['', [Validators.required, Validators.maxLength(100)]],
    descripcion: ['', [Validators.maxLength(1000)]],
    activo: [true],
  });

  readonly statusForm = this.fb.nonNullable.group({
    motivo: ['', [Validators.required, Validators.maxLength(1000)]],
  });

  readonly branchStatusForm = this.fb.nonNullable.group({
    motivo: ['', [Validators.required, Validators.maxLength(1000)]],
  });

  readonly selectedSecondaries = signal<number[]>([]);
  readonly principalId = signal<number | null>(null);

  readonly profesionalModalOpen = signal(false);
  readonly profesionalModalMode = signal<FormMode>('create');
  readonly editingProfesional = signal<Profesional | null>(null);

  readonly detailOpen = signal(false);
  readonly detailProfesional = signal<Profesional | null>(null);

  readonly statusOpen = signal(false);
  readonly statusTarget = signal<StatusTarget | null>(null);
  readonly statusMode = signal<StatusMode>('deactivate');

  readonly branchesOpen = signal(false);
  readonly branchesTarget = signal<Profesional | null>(null);
  readonly branches = signal<ProfesionalSucursalFila[]>([]);
  readonly branchAbilities = signal<{ administrar: boolean }>({ administrar: false });
  readonly branchesLoading = signal(false);

  readonly branchStatusOpen = signal(false);
  readonly branchStatusTarget = signal<ProfesionalSucursalFila | null>(null);
  readonly branchStatusMode = signal<StatusMode>('deactivate');

  readonly especialidadModalOpen = signal(false);
  readonly especialidadModalMode = signal<FormMode>('create');
  readonly editingEspecialidad = signal<Especialidad | null>(null);

  readonly especialidadStatusOpen = signal(false);
  readonly especialidadStatusTarget = signal<Especialidad | null>(null);
  readonly especialidadStatusMode = signal<StatusMode>('deactivate');

  readonly sectionOptions = computed(() =>
    SECTION_OPTIONS.filter(
      (section) => section.value !== 'matriz' || this.pageAbilities().view_matrix,
    ),
  );
  readonly estadoOptions = ESTADO_OPTIONS;
  readonly texts = PROFESIONALES_TEXTS;

  readonly canCreate = computed(() => this.pageAbilities().create);
  readonly canManageBranches = computed(() => this.pageAbilities().manage_branches);
  readonly canAdminSpecialties = computed(() => this.specialtyAbilities().administrar);
  readonly puedeSecundarias = computed(
    () => this.pageAbilities().secundarias && this.options().puede_secundarias,
  );

  readonly formCanEditData = computed(
    () => this.profesionalModalMode() === 'create' || (this.editingProfesional()?.can.update_data ?? false),
  );
  readonly formCanEditContact = computed(
    () => this.profesionalModalMode() === 'create' || (this.editingProfesional()?.can.update_contact ?? false),
  );

  readonly especialidadFilterOptions = computed(() =>
    this.options().especialidades.map((especialidad) => ({
      value: String(especialidad.id),
      label: especialidad.nombre,
    })),
  );

  readonly sucursalFilterOptions = computed(() =>
    this.sucursales().map((sucursal) => ({
      value: String(sucursal.id),
      label: sucursal.nombre,
    })),
  );

  readonly principalOptions = computed(() =>
    this.options().especialidades.map((especialidad) => ({
      value: String(especialidad.id),
      label: especialidad.nombre,
    })),
  );

  readonly secondaryOptions = computed(() => {
    const principal = this.principalId();
    return this.options().especialidades.filter((especialidad) => especialidad.id !== principal);
  });

  readonly matrizProfesionalOptions = computed(() =>
    this.matrizOptions().profesionales.map((profesional) => ({
      value: String(profesional.id),
      label: profesional.activo
        ? profesional.nombre
        : `${profesional.nombre} (inactivo)`,
    })),
  );

  readonly matrizEspecialidadOptions = computed(() =>
    this.matrizOptions().especialidades.map((especialidad) => ({
      value: String(especialidad.id),
      label: especialidad.activo
        ? especialidad.nombre
        : `${especialidad.nombre} (inactiva)`,
    })),
  );

  readonly matrizSucursalOptions = computed(() =>
    this.matrizOptions().sucursales.map((sucursal) => ({
      value: String(sucursal.id),
      label: sucursal.activo
        ? sucursal.nombre
        : `${sucursal.nombre} (inactiva)`,
    })),
  );

  ngOnInit(): void {
    this.loadOpciones();
    this.loadSucursales();
    this.loadProfesionales(1);
    this.loadEspecialidades(1);

    this.profesionalSearch.valueChanges
      .pipe(debounceTime(350), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.loadProfesionales(1));

    this.especialidadSearch.valueChanges
      .pipe(debounceTime(350), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.loadEspecialidades(1));

    this.matrizSearch.valueChanges
      .pipe(debounceTime(350), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.loadMatriz(1));
  }

  setSection(section: ProfesionalesSection): void {
    this.activeSection.set(section);

    if (section === 'matriz') {
      this.loadMatrizOptions();
      this.loadMatriz(this.matrizPage());
    }
  }

  loadSucursales(): void {
    this.sucursalesService
      .list({ per_page: 50, estado: 'activa', sort: 'nombre', direction: 'asc' })
      .subscribe({
        next: (response) => this.sucursales.set(response.data),
        error: (error: { message?: string }) =>
          this.error.set({
            status: 0,
            message: error.message ?? 'No se pudieron cargar las sucursales.',
          }),
      });
  }

  loadOpciones(): void {
    this.service.getOpciones().subscribe({
      next: (response) => {
        this.options.set(response.data);
        this.pageAbilities.set(response.can);
      },
      error: (error: ProfesionalesApiError) => this.error.set(error),
    });
  }

  loadProfesionales(page = this.profesionalPage()): void {
    this.profesionalesLoading.set(true);
    this.error.set(null);

    const params: ProfesionalListParams = {
      search: this.profesionalSearch.value.trim() || undefined,
      especialidad_id: this.profesionalEspecialidadFilter() || undefined,
      sucursal_id: this.profesionalSucursalFilter() || undefined,
      estado: this.profesionalEstadoFilter() || undefined,
      page,
      per_page: 10,
      sort: this.profesionalSort(),
      direction: this.profesionalDirection(),
    };

    this.service.listProfesionales(params).subscribe({
      next: (response) => {
        this.profesionales.set(response.data);
        this.profesionalPage.set(response.meta.current_page);
        this.profesionalLastPage.set(response.meta.last_page);
        this.profesionalTotal.set(response.meta.total);
        this.profesionalFrom.set(response.meta.from);
        this.profesionalTo.set(response.meta.to);
        this.pageAbilities.set(response.can);
        this.profesionalesLoading.set(false);
      },
      error: (error: ProfesionalesApiError) => {
        this.error.set(error);
        this.profesionalesLoading.set(false);
      },
    });
  }

  loadEspecialidades(page = this.especialidadPage()): void {
    this.especialidadesLoading.set(true);
    this.error.set(null);

    this.service
      .listEspecialidades({
        search: this.especialidadSearch.value.trim() || undefined,
        estado: this.especialidadEstadoFilter() || undefined,
        page,
        per_page: 10,
        sort: this.especialidadSort(),
        direction: this.especialidadDirection(),
      })
      .subscribe({
        next: (response) => {
          this.especialidades.set(response.data);
          this.especialidadPage.set(response.meta.current_page);
          this.especialidadLastPage.set(response.meta.last_page);
          this.especialidadTotal.set(response.meta.total);
          this.especialidadFrom.set(response.meta.from);
          this.especialidadTo.set(response.meta.to);
          this.specialtyAbilities.set(response.can);
          this.especialidadesLoading.set(false);
        },
        error: (error: ProfesionalesApiError) => {
          this.error.set(error);
          this.especialidadesLoading.set(false);
        },
      });
  }

  loadMatrizOptions(): void {
    this.service.getMatrizOpciones().subscribe({
      next: (response) => {
        this.matrizOptions.set(response.data);
      },
      error: (error: ProfesionalesApiError) => this.error.set(error),
    });
  }

  loadMatriz(page = this.matrizPage()): void {
    this.matrizLoading.set(true);
    this.error.set(null);

    const params: MatrizListParams = {
      search: this.matrizSearch.value.trim() || undefined,
      profesional_id: this.matrizProfesionalFilter() || undefined,
      especialidad_id: this.matrizEspecialidadFilter() || undefined,
      sucursal_id: this.matrizSucursalFilter() || undefined,
      estado: this.matrizEstadoFilter() || undefined,
      solo_asignables: this.matrizSoloAsignables() || undefined,
      page,
      per_page: 10,
      sort: this.matrizSort(),
      direction: this.matrizDirection(),
    };

    this.service.listMatriz(params).subscribe({
      next: (response) => {
        this.matriz.set(response.data);
        this.matrizPage.set(response.meta.current_page);
        this.matrizLastPage.set(response.meta.last_page);
        this.matrizTotal.set(response.meta.total);
        this.matrizFrom.set(response.meta.from);
        this.matrizTo.set(response.meta.to);
        this.matrizViewProfessionals.set(response.can.view_professionals);
        this.matrizLoading.set(false);
      },
      error: (error: ProfesionalesApiError) => {
        this.error.set(error);
        this.matrizLoading.set(false);
      },
    });
  }

  onMatrizProfesionalFilter(value: string): void {
    this.matrizProfesionalFilter.set(value === '' ? '' : Number(value));
    this.loadMatriz(1);
  }

  onMatrizEspecialidadFilter(value: string): void {
    this.matrizEspecialidadFilter.set(value === '' ? '' : Number(value));
    this.loadMatriz(1);
  }

  onMatrizSucursalFilter(value: string): void {
    this.matrizSucursalFilter.set(value === '' ? '' : Number(value));
    this.loadMatriz(1);
  }

  onMatrizEstadoFilter(value: string): void {
    this.matrizEstadoFilter.set((value as '' | 'activo' | 'inactivo') || '');
    this.loadMatriz(1);
  }

  onMatrizSoloAsignables(value: boolean): void {
    this.matrizSoloAsignables.set(value);
    this.loadMatriz(1);
  }

  sortMatriz(field: 'profesional_nombre' | 'profesional_ci' | 'especialidad_nombre' | 'sucursal_nombre'): void {
    if (this.matrizSort() === field) {
      this.matrizDirection.set(this.matrizDirection() === 'asc' ? 'desc' : 'asc');
    } else {
      this.matrizSort.set(field);
      this.matrizDirection.set('asc');
    }

    this.loadMatriz(1);
  }

  openBranchesById(profesionalId: number): void {
    this.service.getProfesional(profesionalId).subscribe({
      next: (response) => this.openBranches(response.data),
      error: (error: ProfesionalesApiError) => this.error.set(error),
    });
  }

  openDetailById(profesionalId: number): void {
    this.service.getProfesional(profesionalId).subscribe({
      next: (response) => {
        this.detailProfesional.set(response.data);
        this.detailOpen.set(true);
      },
      error: (error: ProfesionalesApiError) => this.error.set(error),
    });
  }

  onProfesionalEspecialidadFilter(value: string): void {
    this.profesionalEspecialidadFilter.set(value === '' ? '' : Number(value));
    this.loadProfesionales(1);
  }

  onProfesionalSucursalFilter(value: string): void {
    this.profesionalSucursalFilter.set(value === '' ? '' : Number(value));
    this.loadProfesionales(1);
  }

  onProfesionalEstadoFilter(value: string): void {
    this.profesionalEstadoFilter.set((value as '' | 'activo' | 'inactivo') || '');
    this.loadProfesionales(1);
  }

  onEspecialidadEstadoFilter(value: string): void {
    this.especialidadEstadoFilter.set((value as '' | 'activo' | 'inactivo') || '');
    this.loadEspecialidades(1);
  }

  sortProfesionales(field: 'nombre_completo' | 'ci' | 'activo' | 'updated_at'): void {
    if (this.profesionalSort() === field) {
      this.profesionalDirection.set(this.profesionalDirection() === 'asc' ? 'desc' : 'asc');
    } else {
      this.profesionalSort.set(field);
      this.profesionalDirection.set('asc');
    }

    this.loadProfesionales(1);
  }

  sortEspecialidades(field: 'nombre' | 'activo' | 'updated_at'): void {
    if (this.especialidadSort() === field) {
      this.especialidadDirection.set(this.especialidadDirection() === 'asc' ? 'desc' : 'asc');
    } else {
      this.especialidadSort.set(field);
      this.especialidadDirection.set('asc');
    }

    this.loadEspecialidades(1);
  }

  openCreateProfesional(): void {
    this.profesionalModalMode.set('create');
    this.editingProfesional.set(null);
    this.selectedSecondaries.set([]);
    this.principalId.set(null);
    this.profesionalForm.reset({
      nombre_completo: '',
      ci: '',
      telefono: '',
      telefono_2: '',
      email: '',
      matricula: '',
      activo: true,
      especialidad_principal_id: '',
    });
    this.profesionalModalOpen.set(true);
  }

  openEditProfesional(profesional: Profesional): void {
    this.service.getProfesional(profesional.id).subscribe({
      next: (response) => {
        this.editingProfesional.set(response.data);
        this.profesionalModalMode.set('edit');
        this.fillProfesionalForm(response.data);
        this.profesionalModalOpen.set(true);
      },
      error: (error: ProfesionalesApiError) => this.error.set(error),
    });
  }

  openDetailProfesional(profesional: Profesional): void {
    this.service.getProfesional(profesional.id).subscribe({
      next: (response) => {
        this.detailProfesional.set(response.data);
        this.detailOpen.set(true);
      },
      error: (error: ProfesionalesApiError) => this.error.set(error),
    });
  }

  private fillProfesionalForm(profesional: Profesional): void {
    this.profesionalForm.reset({
      nombre_completo: profesional.nombre_completo,
      ci: profesional.ci,
      telefono: profesional.telefono ?? '',
      telefono_2: profesional.telefono_2 ?? '',
      email: profesional.email ?? '',
      matricula: profesional.matricula ?? '',
      activo: profesional.activo,
      especialidad_principal_id: profesional.especialidad_principal_id
        ? String(profesional.especialidad_principal_id)
        : '',
    });

    this.principalId.set(profesional.especialidad_principal_id);
    this.selectedSecondaries.set(
      profesional.especialidades
        .filter((especialidad) => especialidad.tipo === 'secundaria')
        .map((especialidad) => especialidad.id),
    );
  }

  onPrincipalChange(value: string): void {
    const id = value === '' ? null : Number(value);
    this.profesionalForm.controls.especialidad_principal_id.setValue(value);
    this.principalId.set(id);
    this.selectedSecondaries.set(
      this.selectedSecondaries().filter((secondaryId) => secondaryId !== id),
    );
  }

  toggleSecondary(id: number): void {
    if (id === this.principalId()) {
      return;
    }

    const current = this.selectedSecondaries();
    this.selectedSecondaries.set(
      current.includes(id) ? current.filter((item) => item !== id) : [...current, id],
    );
  }

  isSecondarySelected(id: number): boolean {
    return this.selectedSecondaries().includes(id);
  }

  saveProfesional(): void {
    if (this.profesionalForm.invalid) {
      this.profesionalForm.markAllAsTouched();
      return;
    }

    const principal = Number(this.profesionalForm.controls.especialidad_principal_id.value);

    if (!principal) {
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    if (this.profesionalModalMode() === 'create') {
      const raw = this.profesionalForm.getRawValue();
      const payload: ProfesionalCreatePayload = {
        nombre_completo: raw.nombre_completo.trim(),
        ci: raw.ci.trim(),
        telefono: this.nullable(raw.telefono),
        telefono_2: this.nullable(raw.telefono_2),
        email: this.nullable(raw.email),
        matricula: this.nullable(raw.matricula),
        activo: raw.activo,
        especialidad_principal_id: principal,
        especialidades_secundarias: this.selectedSecondaries(),
      };

      this.service.createProfesional(payload).subscribe({
        next: () => this.afterProfesionalSaved(),
        error: (error: ProfesionalesApiError) => {
          this.error.set(error);
          this.applyProfesionalErrors(error);
          this.saving.set(false);
        },
      });

      return;
    }

    const current = this.editingProfesional();

    if (current === null) {
      this.saving.set(false);
      return;
    }

    const raw = this.profesionalForm.getRawValue();
    const payload: ProfesionalUpdatePayload = { version: current.version };

    if (this.formCanEditData()) {
      if (raw.nombre_completo.trim() !== current.nombre_completo) {
        payload.nombre_completo = raw.nombre_completo.trim();
      }

      if (raw.ci.trim() !== current.ci) {
        payload.ci = raw.ci.trim();
      }

      if (this.nullable(raw.matricula) !== current.matricula) {
        payload.matricula = this.nullable(raw.matricula);
      }

      if (principal !== current.especialidad_principal_id) {
        payload.especialidad_principal_id = principal;
      }

      const currentSecondaries = current.especialidades
        .filter((especialidad) => especialidad.tipo === 'secundaria')
        .map((especialidad) => especialidad.id)
        .sort((a, b) => a - b);
      const selectedSecondaries = [...this.selectedSecondaries()].sort((a, b) => a - b);

      if (currentSecondaries.join(',') !== selectedSecondaries.join(',')) {
        payload.especialidades_secundarias = selectedSecondaries;
      }
    }

    if (this.formCanEditContact()) {
      payload.telefono = this.nullable(raw.telefono);
      payload.telefono_2 = this.nullable(raw.telefono_2);
      payload.email = this.nullable(raw.email);
    }

    this.service.updateProfesional(current.id, payload).subscribe({
      next: () => this.afterProfesionalSaved(),
      error: (error: ProfesionalesApiError) => {
        this.error.set(error);
        this.applyProfesionalErrors(error);
        this.saving.set(false);
      },
    });
  }

  private afterProfesionalSaved(): void {
    this.saving.set(false);
    this.profesionalModalOpen.set(false);
    this.toast.success('Profesionales', PROFESIONALES_TEXTS.saved);
    this.loadProfesionales(this.profesionalPage());
  }

  private applyProfesionalErrors(error: ProfesionalesApiError): void {
    if (!error.errors) {
      return;
    }

    for (const [field, messages] of Object.entries(error.errors)) {
      const control = this.profesionalForm.get(field);

      if (control && messages.length > 0) {
        control.setErrors({ server: messages[0] });
      }
    }
  }

  fieldError(field: string): string | null {
    const control = this.profesionalForm.get(field);
    const server = control?.errors?.['server'];

    return typeof server === 'string' ? server : null;
  }

  openStatus(target: StatusTarget, mode: StatusMode): void {
    this.statusTarget.set(target);
    this.statusMode.set(mode);
    this.statusForm.reset({ motivo: '' });
    this.statusOpen.set(true);
  }

  confirmStatus(): void {
    const target = this.statusTarget();

    if (target === null || this.statusForm.invalid) {
      this.statusForm.markAllAsTouched();
      return;
    }

    const payload: StatusPayload = {
      motivo: this.statusForm.controls.motivo.value.trim(),
      version: target.entity.version,
    };

    this.saving.set(true);

    if (target.type === 'profesional') {
      const request =
        this.statusMode() === 'activate'
          ? this.service.activateProfesional(target.entity.id, payload)
          : this.service.deactivateProfesional(target.entity.id, payload);

      request.subscribe({
        next: () => {
          this.saving.set(false);
          this.statusOpen.set(false);
          this.toast.success('Profesionales', PROFESIONALES_TEXTS.statusSaved);
          this.loadProfesionales(this.profesionalPage());
        },
        error: (error: ProfesionalesApiError) => {
          this.saving.set(false);
          this.error.set(error);
        },
      });

      return;
    }

    const request =
      this.statusMode() === 'activate'
        ? this.service.activateEspecialidad(target.entity.id, payload)
        : this.service.deactivateEspecialidad(target.entity.id, payload);

    request.subscribe({
      next: () => {
        this.saving.set(false);
        this.especialidadStatusOpen.set(false);
        this.toast.success('Especialidades', PROFESIONALES_TEXTS.statusSaved);
        this.loadEspecialidades(this.especialidadPage());
      },
      error: (error: ProfesionalesApiError) => {
        this.saving.set(false);
        this.error.set(error);
      },
    });
  }

  openBranches(profesional: Profesional): void {
    this.branchesTarget.set(profesional);
    this.branchesLoading.set(true);
    this.branchesOpen.set(true);

    this.service.listSucursales(profesional.id).subscribe({
      next: (response) => {
        this.branches.set(response.data);
        this.branchAbilities.set(response.can);
        this.branchesLoading.set(false);
      },
      error: (error: ProfesionalesApiError) => {
        this.error.set(error);
        this.branchesLoading.set(false);
        this.branchesOpen.set(false);
      },
    });
  }

  assignBranch(branch: ProfesionalSucursalFila): void {
    const target = this.branchesTarget();

    if (target === null) {
      return;
    }

    this.saving.set(true);

    this.service.assignSucursal(target.id, branch.id).subscribe({
      next: () => this.reloadBranches(),
      error: (error: ProfesionalesApiError) => {
        this.saving.set(false);
        this.error.set(error);
      },
    });
  }

  openBranchStatus(branch: ProfesionalSucursalFila, mode: StatusMode): void {
    this.branchStatusTarget.set(branch);
    this.branchStatusMode.set(mode);
    this.branchStatusForm.reset({ motivo: '' });
    this.branchStatusOpen.set(true);
  }

  confirmBranchStatus(): void {
    const target = this.branchesTarget();
    const branch = this.branchStatusTarget();

    if (target === null || branch === null || branch.version === null || this.branchStatusForm.invalid) {
      this.branchStatusForm.markAllAsTouched();
      return;
    }

    const payload: StatusPayload = {
      motivo: this.branchStatusForm.controls.motivo.value.trim(),
      version: branch.version,
    };

    this.saving.set(true);

    const request =
      this.branchStatusMode() === 'activate'
        ? this.service.activateSucursal(target.id, branch.id, payload)
        : this.service.deactivateSucursal(target.id, branch.id, payload);

    request.subscribe({
      next: () => {
        this.branchStatusOpen.set(false);
        this.toast.success('Sucursales', PROFESIONALES_TEXTS.branchSaved);
        this.reloadBranches();
      },
      error: (error: ProfesionalesApiError) => {
        this.saving.set(false);
        this.error.set(error);
      },
    });
  }

  private reloadBranches(): void {
    const target = this.branchesTarget();

    if (target === null) {
      this.saving.set(false);
      return;
    }

    this.service.listSucursales(target.id).subscribe({
      next: (response) => {
        this.branches.set(response.data);
        this.branchAbilities.set(response.can);
        this.saving.set(false);
        this.loadProfesionales(this.profesionalPage());
      },
      error: (error: ProfesionalesApiError) => {
        this.error.set(error);
        this.saving.set(false);
      },
    });
  }

  openCreateEspecialidad(): void {
    this.especialidadModalMode.set('create');
    this.editingEspecialidad.set(null);
    this.especialidadForm.reset({ nombre: '', descripcion: '', activo: true });
    this.especialidadModalOpen.set(true);
  }

  openEditEspecialidad(especialidad: Especialidad): void {
    this.service.getEspecialidad(especialidad.id).subscribe({
      next: (response) => {
        this.editingEspecialidad.set(response.data);
        this.especialidadModalMode.set('edit');
        this.especialidadForm.reset({
          nombre: response.data.nombre,
          descripcion: response.data.descripcion ?? '',
          activo: response.data.activo,
        });
        this.especialidadModalOpen.set(true);
      },
      error: (error: ProfesionalesApiError) => this.error.set(error),
    });
  }

  saveEspecialidad(): void {
    if (this.especialidadForm.invalid) {
      this.especialidadForm.markAllAsTouched();
      return;
    }

    const raw = this.especialidadForm.getRawValue();
    this.saving.set(true);
    this.error.set(null);

    const request =
      this.especialidadModalMode() === 'create'
        ? this.service.createEspecialidad({
            nombre: raw.nombre.trim(),
            descripcion: this.nullable(raw.descripcion),
            activo: raw.activo,
          })
        : this.service.updateEspecialidad(this.editingEspecialidad()?.id ?? 0, {
            nombre: raw.nombre.trim(),
            descripcion: this.nullable(raw.descripcion),
            version: this.editingEspecialidad()?.version ?? 1,
          });

    request.subscribe({
      next: () => {
        this.saving.set(false);
        this.especialidadModalOpen.set(false);
        this.toast.success('Especialidades', PROFESIONALES_TEXTS.specialtySaved);
        this.loadEspecialidades(this.especialidadPage());
        this.loadOpciones();
      },
      error: (error: ProfesionalesApiError) => {
        this.saving.set(false);
        this.error.set(error);

        if (error.errors) {
          for (const [field, messages] of Object.entries(error.errors)) {
            const control = this.especialidadForm.get(field);

            if (control && messages.length > 0) {
              control.setErrors({ server: messages[0] });
            }
          }
        }
      },
    });
  }

  especialidadFieldError(field: string): string | null {
    const control = this.especialidadForm.get(field);
    const server = control?.errors?.['server'];

    return typeof server === 'string' ? server : null;
  }

  openEspecialidadStatus(especialidad: Especialidad, mode: StatusMode): void {
    this.especialidadStatusTarget.set(especialidad);
    this.especialidadStatusMode.set(mode);
    this.statusForm.reset({ motivo: '' });
    this.especialidadStatusOpen.set(true);
  }

  confirmEspecialidadStatus(): void {
    const especialidad = this.especialidadStatusTarget();

    if (especialidad === null || this.statusForm.invalid) {
      this.statusForm.markAllAsTouched();
      return;
    }

    const payload: StatusPayload = {
      motivo: this.statusForm.controls.motivo.value.trim(),
      version: especialidad.version,
    };

    this.saving.set(true);

    const request =
      this.especialidadStatusMode() === 'activate'
        ? this.service.activateEspecialidad(especialidad.id, payload)
        : this.service.deactivateEspecialidad(especialidad.id, payload);

    request.subscribe({
      next: () => {
        this.saving.set(false);
        this.especialidadStatusOpen.set(false);
        this.toast.success('Especialidades', PROFESIONALES_TEXTS.statusSaved);
        this.loadEspecialidades(this.especialidadPage());
      },
      error: (error: ProfesionalesApiError) => {
        this.saving.set(false);
        this.error.set(error);
      },
    });
  }

  motivosMatrizTexto(row: MatrizRow): string {
    return row.motivos_no_asignable.map((motivo) => motivo.mensaje).join(' ');
  }

  private nullable(value: string): string | null {
    const trimmed = value.trim();

    return trimmed === '' ? null : trimmed;
  }

  formatDate(value: string | null): string {
    if (!value) {
      return '—';
    }

    return new Intl.DateTimeFormat('es-BO', {
      dateStyle: 'short',
      timeStyle: 'short',
      timeZone: 'America/La_Paz',
    }).format(new Date(value));
  }

  motivosTexto(profesional: Profesional): string {
    return profesional.motivos_no_asignable.map((motivo) => motivo.mensaje).join(' ');
  }

  sortIndicator(field: string, sort: string, direction: 'asc' | 'desc'): string {
    return sort === field ? (direction === 'asc' ? '▲' : '▼') : '';
  }

  ariaSort(field: string, sort: string, direction: 'asc' | 'desc'): 'ascending' | 'descending' | 'none' {
    if (sort !== field) {
      return 'none';
    }

    return direction === 'asc' ? 'ascending' : 'descending';
  }

  closeError(): void {
    this.error.set(null);
  }
}
