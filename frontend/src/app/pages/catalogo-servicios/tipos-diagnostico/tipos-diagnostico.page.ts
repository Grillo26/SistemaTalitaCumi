import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import {
  FormArray,
  FormBuilder,
  FormControl,
  FormGroup,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
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
import { Especialidad } from '../../profesionales/profesionales.models';
import { Servicio } from '../servicios/servicios.models';
import {
  EMPTY_TIPOS_DIAGNOSTICO_ABILITIES,
  ESTADO_OPTIONS,
  EstadoCatalogo,
  FormMode,
  TIPOS_DIAGNOSTICO_TEXTS,
  TipoDiagnostico,
  TipoDiagnosticoPayload,
  TiposDiagnosticoAbilities,
  TiposDiagnosticoApiError,
  StatusTipoDiagnosticoPayload,
} from './tipos-diagnostico.models';
import { TiposDiagnosticoService } from './tipos-diagnostico.service';

@Component({
  selector: 'app-tipos-diagnostico-page',
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
    ...TABLE_IMPORTS,
  ],
  templateUrl: './tipos-diagnostico.page.html',
})
export class TiposDiagnosticoPageComponent implements OnInit {
  private readonly service = inject(TiposDiagnosticoService);
  private readonly toast = inject(ToastService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = TIPOS_DIAGNOSTICO_TEXTS;
  readonly estadoOptions = ESTADO_OPTIONS;

  readonly abilities = signal<TiposDiagnosticoAbilities>(EMPTY_TIPOS_DIAGNOSTICO_ABILITIES);
  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly error = signal<TiposDiagnosticoApiError | null>(null);
  readonly catalogError = signal<string | null>(null);

  readonly tipos = signal<TipoDiagnostico[]>([]);
  readonly servicios = signal<Servicio[]>([]);
  readonly especialidades = signal<Especialidad[]>([]);

  readonly search = new FormControl('', { nonNullable: true });
  readonly estadoFilter = signal<EstadoCatalogo | ''>('');
  readonly especialidadFilter = signal('');
  readonly sort = signal<'nombre' | 'activo' | 'dias_estimados' | 'updated_at'>('nombre');
  readonly direction = signal<'asc' | 'desc'>('asc');
  readonly page = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly from = signal<number | null>(null);
  readonly to = signal<number | null>(null);

  readonly tipoForm = this.fb.nonNullable.group({
    servicio_id: ['', [Validators.required]],
    nombre: ['', [Validators.required, Validators.maxLength(150)]],
    descripcion: ['', [Validators.maxLength(2000)]],
    dias_estimados: [1, [Validators.required, Validators.min(1)]],
    activo: [true],
    especialidades: this.fb.array<FormGroup>([]),
  });

  readonly statusForm = this.fb.nonNullable.group({
    motivo: ['', [Validators.required, Validators.maxLength(1000)]],
  });

  readonly formOpen = signal(false);
  readonly formMode = signal<FormMode>('create');
  readonly editingTipo = signal<TipoDiagnostico | null>(null);
  readonly fieldErrors = signal<Record<string, string[]>>({});
  readonly formError = signal<string | null>(null);
  readonly concurrency = signal(false);
  readonly especialidadesError = signal<string | null>(null);

  readonly detailOpen = signal(false);
  readonly selectedTipo = signal<TipoDiagnostico | null>(null);

  readonly statusOpen = signal(false);
  readonly statusTipo = signal<TipoDiagnostico | null>(null);
  readonly statusMode = signal<'activate' | 'deactivate'>('deactivate');
  readonly statusFieldErrors = signal<Record<string, string[]>>({});
  readonly statusError = signal<string | null>(null);
  readonly statusConcurrency = signal(false);

  readonly canCreate = computed(() => this.abilities().tipos_diagnostico.create);
  readonly canEdit = computed(() => this.abilities().tipos_diagnostico.update);
  readonly canChangeStatus = computed(() => this.abilities().tipos_diagnostico.change_status);

  readonly especialidadFilterOptions = computed(() => [
    { value: '', label: 'Todas las especialidades' },
    ...this.especialidades()
      .filter((especialidad) => especialidad.activo)
      .map((especialidad) => ({
        value: String(especialidad.id),
        label: especialidad.nombre,
      })),
  ]);

  readonly servicioOptions = computed(() =>
    this.servicios().map((servicio) => ({
      value: String(servicio.id),
      label: this.servicioLabel(servicio),
    })),
  );

  get especialidadesArray(): FormArray<FormGroup> {
    return this.tipoForm.controls.especialidades;
  }

  ngOnInit(): void {
    this.loadCatalogos();
    this.loadTipos();

    this.search.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => {
        this.page.set(1);
        this.loadTipos();
      });
  }

  loadCatalogos(): void {
    this.service
      .listServiciosDiagnostico()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (servicios) => this.servicios.set(servicios),
        error: () => this.catalogError.set(this.texts.serviciosUnavailable),
      });

    this.service
      .listEspecialidades()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (especialidades) => this.especialidades.set(especialidades),
        error: () => this.catalogError.set(this.texts.especialidadesUnavailable),
      });
  }

  loadTipos(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service
      .listTipos({
        search: this.search.value.trim() || undefined,
        estado: this.estadoFilter() || undefined,
        especialidad_id: this.especialidadFilter()
          ? Number(this.especialidadFilter())
          : undefined,
        page: this.page(),
        per_page: 10,
        sort: this.sort(),
        direction: this.direction(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.tipos.set(response.data);
          this.abilities.set(response.can);
          this.page.set(response.meta.current_page);
          this.lastPage.set(response.meta.last_page);
          this.total.set(response.meta.total);
          this.from.set(response.meta.from);
          this.to.set(response.meta.to);
          this.loading.set(false);

          if (response.data.length === 0 && response.meta.current_page > 1) {
            this.page.set(response.meta.current_page - 1);
            this.loadTipos();
          }
        },
        error: (error: TiposDiagnosticoApiError) => {
          this.loading.set(false);
          this.error.set(error);
        },
      });
  }

  onEstadoFilter(value: string): void {
    this.estadoFilter.set(value as EstadoCatalogo | '');
    this.page.set(1);
    this.loadTipos();
  }

  onEspecialidadFilter(value: string): void {
    this.especialidadFilter.set(value);
    this.page.set(1);
    this.loadTipos();
  }

  sortBy(field: 'nombre' | 'activo' | 'dias_estimados' | 'updated_at'): void {
    if (this.sort() === field) {
      this.direction.set(this.direction() === 'asc' ? 'desc' : 'asc');
    } else {
      this.sort.set(field);
      this.direction.set('asc');
    }

    this.page.set(1);
    this.loadTipos();
  }

  goToPage(page: number): void {
    this.page.set(page);
    this.loadTipos();
  }

  openCreate(): void {
    if (!this.canCreate()) {
      return;
    }

    this.formMode.set('create');
    this.editingTipo.set(null);
    this.tipoForm.reset({
      servicio_id: '',
      nombre: '',
      descripcion: '',
      dias_estimados: 1,
      activo: true,
    });
    this.tipoForm.enable();
    this.especialidadesArray.clear();
    this.especialidadesArray.push(this.nuevaEspecialidad());
    this.clearErrors();
    this.formOpen.set(true);
  }

  openEdit(tipo: TipoDiagnostico): void {
    if (!this.canEdit()) {
      return;
    }

    this.service
      .getTipo(tipo.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const latest = response.data;
          this.abilities.set(response.can);
          this.formMode.set('edit');
          this.editingTipo.set(latest);
          this.tipoForm.reset({
            servicio_id: String(latest.servicio?.id ?? ''),
            nombre: latest.nombre,
            descripcion: latest.descripcion ?? '',
            dias_estimados: latest.dias_estimados,
            activo: latest.activo,
          });
          this.tipoForm.controls.activo.disable();
          this.especialidadesArray.clear();

          for (const especialidad of latest.especialidades) {
            this.especialidadesArray.push(
              this.nuevaEspecialidad(
                especialidad.especialidad_id,
                especialidad.cantidad_minima,
              ),
            );
          }

          this.detailOpen.set(false);
          this.clearErrors();
          this.formOpen.set(true);
        },
        error: (error: TiposDiagnosticoApiError) => this.error.set(error),
      });
  }

  closeForm(): void {
    if (!this.saving()) {
      this.formOpen.set(false);
    }
  }

  addEspecialidad(): void {
    this.especialidadesArray.push(this.nuevaEspecialidad());
    this.especialidadesError.set(null);
  }

  removeEspecialidad(index: number): void {
    this.especialidadesArray.removeAt(index);
    this.especialidadesError.set(null);
  }

  especialidadOptionsFor(index: number): { value: string; label: string }[] {
    const selected = new Set(
      this.especialidadesArray.controls
        .map((control) => String(control.get('especialidad_id')?.value ?? ''))
        .filter((value) => value !== ''),
    );

    return this.especialidades()
      .filter((especialidad) => especialidad.activo || selected.has(String(especialidad.id)))
      .map((especialidad) => ({
        value: String(especialidad.id),
        label: especialidad.activo
          ? especialidad.nombre
          : `${especialidad.nombre} (inactiva)`,
      }));
  }

  saveTipo(): void {
    if (this.saving()) {
      return;
    }

    this.tipoForm.markAllAsTouched();
    this.clearErrors();

    if (this.tipoForm.invalid || !this.validateEspecialidades()) {
      return;
    }

    const raw = this.tipoForm.getRawValue();
    const especialidades = this.especialidadesArray.controls.map((control) => ({
      especialidad_id: Number(control.get('especialidad_id')?.value),
      cantidad_minima: Number(control.get('cantidad_minima')?.value),
    }));

    const payload: TipoDiagnosticoPayload = {
      servicio_id: Number(raw.servicio_id),
      nombre: raw.nombre.trim(),
      descripcion: raw.descripcion.trim() === '' ? null : raw.descripcion.trim(),
      dias_estimados: Number(raw.dias_estimados),
      especialidades,
    };

    this.saving.set(true);

    if (this.formMode() === 'create') {
      payload.activo = raw.activo;

      this.service
        .createTipo(payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: () => this.handleSuccess('Tipo de diagnóstico creado'),
          error: (error: TiposDiagnosticoApiError) => this.applyFormError(error),
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
      .updateTipo(tipo.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => this.handleSuccess('Tipo de diagnóstico actualizado'),
        error: (error: TiposDiagnosticoApiError) => this.applyFormError(error),
      });
  }

  openDetail(tipo: TipoDiagnostico): void {
    this.service
      .getTipo(tipo.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.selectedTipo.set(response.data);
          this.abilities.set(response.can);
          this.detailOpen.set(true);
        },
        error: (error: TiposDiagnosticoApiError) => this.error.set(error),
      });
  }

  closeDetail(): void {
    this.detailOpen.set(false);
  }

  openStatus(tipo: TipoDiagnostico, mode: 'activate' | 'deactivate'): void {
    if (!this.canChangeStatus()) {
      return;
    }

    this.statusTipo.set(tipo);
    this.statusMode.set(mode);
    this.statusForm.reset({ motivo: '' });
    this.statusFieldErrors.set({});
    this.statusError.set(null);
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

    const tipo = this.statusTipo();

    if (!tipo) {
      return;
    }

    this.statusForm.markAllAsTouched();

    if (this.statusForm.invalid) {
      return;
    }

    const payload: StatusTipoDiagnosticoPayload = {
      motivo: this.statusForm.getRawValue().motivo.trim(),
      version: tipo.version,
    };

    this.saving.set(true);
    this.statusFieldErrors.set({});
    this.statusError.set(null);
    this.statusConcurrency.set(false);

    const request$ = this.statusMode() === 'activate'
      ? this.service.activateTipo(tipo.id, payload)
      : this.service.deactivateTipo(tipo.id, payload);

    request$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: () => {
        this.saving.set(false);
        this.statusOpen.set(false);
        this.toast.success(
          this.statusMode() === 'activate' ? 'Tipo activado' : 'Tipo desactivado',
          'El cambio quedó registrado en la bitácora.',
        );
        this.loadTipos();
      },
      error: (error: TiposDiagnosticoApiError) => {
        this.saving.set(false);
        this.statusFieldErrors.set(error.errors ?? {});
        this.statusError.set(error.message);
        this.statusConcurrency.set(error.code === 'VERSION_CONFLICT');
      },
    });
  }

  fieldError(field: string): string | null {
    const backend = this.fieldErrors()[field]?.[0];

    if (backend) {
      return backend;
    }

    const control = this.tipoForm.get(field);

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
      return 'El valor debe ser mayor o igual a 1.';
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

  especialidadRowError(index: number, field: string): string | null {
    const prefix = `especialidades.${index}.${field}`;
    const backend = this.fieldErrors()[prefix]?.[0];

    if (backend) {
      return backend;
    }

    const control = this.especialidadesArray.at(index)?.get(field);

    if (!control || !control.touched || control.valid) {
      return null;
    }

    return control.hasError('required') ? 'Este campo es obligatorio.' : 'Revisá este campo.';
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

  estadoColor(activo: boolean): 'success' | 'gray' {
    return activo ? 'success' : 'gray';
  }

  sortIndicator(field: string): string {
    return this.sort() === field ? (this.direction() === 'asc' ? '↑' : '↓') : '';
  }

  ariaSort(field: string): 'ascending' | 'descending' | 'none' {
    return this.sort() === field
      ? this.direction() === 'asc'
        ? 'ascending'
        : 'descending'
      : 'none';
  }

  private nuevaEspecialidad(
    especialidadId: number | string = '',
    cantidad = 1,
  ): FormGroup {
    return this.fb.nonNullable.group({
      especialidad_id: [String(especialidadId), [Validators.required]],
      cantidad_minima: [cantidad, [Validators.required, Validators.min(1)]],
    });
  }

  private validateEspecialidades(): boolean {
    const rows = this.especialidadesArray.controls;

    if (rows.length === 0) {
      this.especialidadesError.set(this.texts.noEspecialidades);
      return false;
    }

    const ids = rows
      .map((control) => String(control.get('especialidad_id')?.value ?? ''))
      .filter((value) => value !== '');

    if (ids.length !== rows.length) {
      this.especialidadesError.set('Seleccioná una especialidad en cada fila.');
      return false;
    }

    if (new Set(ids).size !== ids.length) {
      this.especialidadesError.set(this.texts.duplicateEspecialidad);
      return false;
    }

    const invalidQuantity = rows.some(
      (control) => Number(control.get('cantidad_minima')?.value) < 1,
    );

    if (invalidQuantity) {
      this.especialidadesError.set('La cantidad debe ser mayor o igual a 1.');
      return false;
    }

    this.especialidadesError.set(null);
    return true;
  }

  private handleSuccess(title: string): void {
    this.saving.set(false);
    this.formOpen.set(false);
    this.toast.success(title, 'Los datos se guardaron correctamente.');
    this.loadTipos();
  }

  private applyFormError(error: TiposDiagnosticoApiError): void {
    this.saving.set(false);
    this.fieldErrors.set(error.errors ?? {});
    this.formError.set(error.message);
    this.concurrency.set(error.code === 'VERSION_CONFLICT');

    const specialtyError = Object.entries(error.errors ?? {}).find(([key]) =>
      key.startsWith('especialidades'),
    );

    if (specialtyError) {
      this.especialidadesError.set(specialtyError[1][0] ?? null);
    }
  }

  private clearErrors(): void {
    this.fieldErrors.set({});
    this.formError.set(null);
    this.concurrency.set(false);
    this.especialidadesError.set(null);
  }

  private servicioLabel(servicio: Servicio): string {
    return servicio.area?.nombre
      ? `${servicio.nombre} — ${servicio.area.nombre}`
      : servicio.nombre;
  }
}
