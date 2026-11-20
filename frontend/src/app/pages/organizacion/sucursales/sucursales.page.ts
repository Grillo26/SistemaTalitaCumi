import { Component, DestroyRef, HostListener, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, FormControl, ReactiveFormsModule, Validators } from '@angular/forms';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import { ToastService } from '../../../core/services/toast.service';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
import { PaginationComponent } from '../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { SwitchComponent } from '../../../shared/components/ui/switch/switch.component';
import { TABLE_IMPORTS } from '../../../shared/components/ui/table';
import { TextareaDirective } from '../../../shared/components/ui/textarea/textarea.directive';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import {
  ApiError,
  Branch,
  BranchAbilities,
  BranchCreatePayload,
  BranchSortDirection,
  BranchSortField,
  BranchStatusFilter,
  BranchStatusPayload,
  BranchUpdatePayload,
  EMPTY_BRANCH_ABILITIES,
} from './sucursales.models';
import { SucursalesService } from './sucursales.service';

type FormMode = 'create' | 'edit';
type StatusMode = 'activate' | 'deactivate';

@Component({
  selector: 'app-sucursales-page',
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
  templateUrl: './sucursales.page.html',
})
export class SucursalesPageComponent implements OnInit {
  private readonly service = inject(SucursalesService);
  private readonly toast = inject(ToastService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  readonly branches = signal<Branch[]>([]);
  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly error = signal<ApiError | null>(null);
  readonly abilities = signal<BranchAbilities>(EMPTY_BRANCH_ABILITIES);

  readonly page = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly from = signal<number | null>(null);
  readonly to = signal<number | null>(null);
  readonly perPage = signal(10);

  readonly search = signal('');
  readonly estado = signal<BranchStatusFilter>('');
  readonly sort = signal<BranchSortField>('nombre');
  readonly direction = signal<BranchSortDirection>('asc');

  readonly detailOpen = signal(false);
  readonly selectedBranch = signal<Branch | null>(null);

  readonly formOpen = signal(false);
  readonly formMode = signal<FormMode>('create');
  readonly editingBranch = signal<Branch | null>(null);
  readonly fieldErrors = signal<Record<string, string[]>>({});
  readonly formErrorMessage = signal<string | null>(null);
  readonly concurrencyConflict = signal(false);

  readonly statusOpen = signal(false);
  readonly statusMode = signal<StatusMode>('deactivate');
  readonly statusFieldErrors = signal<Record<string, string[]>>({});
  readonly statusErrorMessage = signal<string | null>(null);
  readonly statusConflict = signal(false);

  readonly canCreate = computed(() => this.abilities().create);
  readonly canEditStructural = computed(() => this.abilities().update);
  readonly canEditContact = computed(
    () => this.abilities().update || this.abilities().update_contact,
  );
  readonly canChangeStatus = computed(() => this.abilities().change_status);

  readonly estadoOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'activa', label: 'Activas' },
    { value: 'inactiva', label: 'Inactivas' },
  ];

  readonly searchControl = new FormControl('', { nonNullable: true });

  readonly branchForm = this.fb.nonNullable.group({
    codigo: ['', [Validators.required, Validators.maxLength(50)]],
    nombre: ['', [Validators.required, Validators.maxLength(150)]],
    direccion: ['', [Validators.maxLength(1000)]],
    telefono: [
      '',
      [Validators.maxLength(30), Validators.pattern(/^[0-9+()\-\s]{6,30}$/)],
    ],
    email: ['', [Validators.email, Validators.maxLength(150)]],
    activo: [true],
  });

  readonly statusForm = this.fb.nonNullable.group({
    motivo: ['', [Validators.required, Validators.maxLength(1000)]],
  });

  ngOnInit(): void {
    this.searchControl.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe((value) => {
        this.search.set(value.trim());
        this.page.set(1);
        this.loadBranches();
      });

    this.loadBranches();
  }

  @HostListener('document:keydown.escape')
  onEscape(): void {
    if (this.statusOpen()) {
      this.closeStatus();
      return;
    }

    if (this.formOpen()) {
      this.closeForm();
      return;
    }

    if (this.detailOpen()) {
      this.closeDetail();
    }
  }

  loadBranches(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service
      .list({
        search: this.search() || undefined,
        estado: this.estado() || undefined,
        page: this.page(),
        per_page: this.perPage(),
        sort: this.sort(),
        direction: this.direction(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.branches.set(response.data);
          this.abilities.set(response.can);
          this.page.set(response.meta.current_page);
          this.lastPage.set(response.meta.last_page);
          this.total.set(response.meta.total);
          this.from.set(response.meta.from);
          this.to.set(response.meta.to);
          this.loading.set(false);

          if (response.data.length === 0 && response.meta.current_page > 1) {
            this.page.set(response.meta.current_page - 1);
            this.loadBranches();
          }
        },
        error: (error: ApiError) => {
          this.error.set(error);
          this.loading.set(false);
        },
      });
  }

  onEstadoChange(value: string): void {
    const next = value === 'activa' || value === 'inactiva' ? value : '';
    this.estado.set(next);
    this.page.set(1);
    this.loadBranches();
  }

  sortBy(field: BranchSortField): void {
    if (this.sort() === field) {
      this.direction.set(this.direction() === 'asc' ? 'desc' : 'asc');
    } else {
      this.sort.set(field);
      this.direction.set('asc');
    }

    this.page.set(1);
    this.loadBranches();
  }

  ariaSort(field: BranchSortField): 'ascending' | 'descending' | 'none' {
    if (this.sort() !== field) {
      return 'none';
    }

    return this.direction() === 'asc' ? 'ascending' : 'descending';
  }

  sortIndicator(field: BranchSortField): string {
    if (this.sort() !== field) {
      return '';
    }

    return this.direction() === 'asc' ? '↑' : '↓';
  }

  goToPage(page: number): void {
    this.page.set(page);
    this.loadBranches();
  }

  openDetail(branch: Branch): void {
    this.error.set(null);

    this.service
      .get(branch.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.selectedBranch.set(response.data);
          this.abilities.set(response.can);
          this.detailOpen.set(true);
        },
        error: (error: ApiError) => this.error.set(error),
      });
  }

  closeDetail(): void {
    this.detailOpen.set(false);
  }

  openCreate(): void {
    if (!this.canCreate()) {
      return;
    }

    this.formMode.set('create');
    this.editingBranch.set(null);
    this.branchForm.reset({
      codigo: '',
      nombre: '',
      direccion: '',
      telefono: '',
      email: '',
      activo: true,
    });
    this.branchForm.controls.codigo.enable();
    this.branchForm.controls.nombre.enable();
    this.branchForm.controls.activo.enable();
    this.clearFormErrors();
    this.formOpen.set(true);
  }

  openEdit(branch: Branch): void {
    if (!this.canEditStructural() && !this.canEditContact()) {
      return;
    }

    this.error.set(null);

    this.service
      .get(branch.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const latest = response.data;
          this.abilities.set(response.can);
          this.formMode.set('edit');
          this.editingBranch.set(latest);
          this.branchForm.reset({
            codigo: latest.codigo,
            nombre: latest.nombre,
            direccion: latest.direccion ?? '',
            telefono: latest.telefono ?? '',
            email: latest.email ?? '',
            activo: latest.activo,
          });

          if (response.can.update) {
            this.branchForm.controls.codigo.enable();
            this.branchForm.controls.nombre.enable();
          } else {
            this.branchForm.controls.codigo.disable();
            this.branchForm.controls.nombre.disable();
          }

          this.branchForm.controls.activo.disable();
          this.clearFormErrors();
          this.formOpen.set(true);
        },
        error: (error: ApiError) => this.error.set(error),
      });
  }

  closeForm(): void {
    if (this.saving()) {
      return;
    }

    this.formOpen.set(false);
  }

  saveBranch(): void {
    if (this.saving()) {
      return;
    }

    this.branchForm.markAllAsTouched();

    if (this.branchForm.invalid) {
      return;
    }

    const raw = this.branchForm.getRawValue();

    if (this.formMode() === 'create') {
      const payload: BranchCreatePayload = {
        codigo: raw.codigo.trim(),
        nombre: raw.nombre.trim(),
        direccion: this.nullIfEmpty(raw.direccion),
        telefono: this.nullIfEmpty(raw.telefono),
        email: this.nullIfEmpty(raw.email),
        activo: raw.activo,
      };

      this.saving.set(true);
      this.clearFormErrors();

      this.service
        .create(payload)
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: () => this.handleFormSuccess('Sucursal creada'),
          error: (error: ApiError) => this.handleFormError(error),
        });

      return;
    }

    const branch = this.editingBranch();

    if (!branch) {
      return;
    }

    const payload: BranchUpdatePayload = { version: branch.version };

    if (this.abilities().update) {
      payload.codigo = raw.codigo.trim();
      payload.nombre = raw.nombre.trim();
    }

    if (this.canEditContact()) {
      payload.direccion = this.nullIfEmpty(raw.direccion);
      payload.telefono = this.nullIfEmpty(raw.telefono);
      payload.email = this.nullIfEmpty(raw.email);
    }

    this.saving.set(true);
    this.clearFormErrors();

    this.service
      .update(branch.id, payload)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => this.handleFormSuccess('Sucursal actualizada'),
        error: (error: ApiError) => this.handleFormError(error),
      });
  }

  openStatusDialog(branch: Branch, mode: StatusMode): void {
    if (!this.canChangeStatus()) {
      return;
    }

    this.error.set(null);

    this.service
      .get(branch.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.abilities.set(response.can);
          this.selectedBranch.set(response.data);
          this.statusMode.set(mode);
          this.statusForm.reset({ motivo: '' });
          this.statusFieldErrors.set({});
          this.statusErrorMessage.set(null);
          this.statusConflict.set(false);
          this.statusOpen.set(true);
        },
        error: (error: ApiError) => this.error.set(error),
      });
  }

  closeStatus(): void {
    if (this.saving()) {
      return;
    }

    this.statusOpen.set(false);
  }

  confirmStatus(): void {
    if (this.saving()) {
      return;
    }

    this.statusForm.markAllAsTouched();

    if (this.statusForm.invalid) {
      return;
    }

    const branch = this.selectedBranch();

    if (!branch) {
      return;
    }

    const payload: BranchStatusPayload = {
      motivo: this.statusForm.getRawValue().motivo.trim(),
      version: branch.version,
    };

    this.saving.set(true);
    this.statusFieldErrors.set({});
    this.statusErrorMessage.set(null);
    this.statusConflict.set(false);

    const request$ =
      this.statusMode() === 'activate'
        ? this.service.activate(branch.id, payload)
        : this.service.deactivate(branch.id, payload);

    request$.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.statusOpen.set(false);
        this.toast.success(
          this.statusMode() === 'activate' ? 'Sucursal activada' : 'Sucursal desactivada',
          this.statusMode() === 'activate'
            ? 'La sucursal vuelve a estar disponible para nuevas operaciones.'
            : 'La sucursal no recibirá nuevas operaciones. El historial se conserva.',
        );

        if (this.detailOpen()) {
          this.selectedBranch.set(response.data);
        }

        this.loadBranches();
      },
      error: (error: ApiError) => {
        this.saving.set(false);

        if (error.status === 422 && error.errors) {
          this.statusFieldErrors.set(error.errors);
          this.statusErrorMessage.set('Revisá el motivo indicado.');
          return;
        }

        if (error.status === 409) {
          this.statusConflict.set(true);
          this.statusErrorMessage.set(error.message);
          return;
        }

        this.statusErrorMessage.set(error.message);
      },
    });
  }

  reloadConflictTarget(): void {
    const branchId = this.editingBranch()?.id ?? this.selectedBranch()?.id;

    if (!branchId) {
      return;
    }

    const wasFormOpen = this.formOpen();
    const wasStatusOpen = this.statusOpen();
    this.error.set(null);

    this.service
      .get(branchId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const latest = response.data;
          this.abilities.set(response.can);
          this.selectedBranch.set(latest);

          if (wasFormOpen) {
            this.editingBranch.set(latest);
            this.branchForm.reset({
              codigo: latest.codigo,
              nombre: latest.nombre,
              direccion: latest.direccion ?? '',
              telefono: latest.telefono ?? '',
              email: latest.email ?? '',
              activo: latest.activo,
            });

            if (response.can.update) {
              this.branchForm.controls.codigo.enable();
              this.branchForm.controls.nombre.enable();
            } else {
              this.branchForm.controls.codigo.disable();
              this.branchForm.controls.nombre.disable();
            }

            this.branchForm.controls.activo.disable();
            this.clearFormErrors();
            this.concurrencyConflict.set(false);
            this.toast.info('Datos recargados', 'Se cargó la última versión de la sucursal.');
          }

          if (wasStatusOpen) {
            this.statusConflict.set(false);
            this.statusErrorMessage.set(
              'Se actualizó la versión de la sucursal. Revisá el motivo y confirmá nuevamente.',
            );
          }
        },
        error: (error: ApiError) => {
          if (wasFormOpen) {
            this.formErrorMessage.set(error.message);
          }

          if (wasStatusOpen) {
            this.statusErrorMessage.set(error.message);
          }
        },
      });
  }

  fieldError(field: string): string | null {
    const backendError = this.fieldErrors()[field]?.[0];

    if (backendError) {
      return backendError;
    }

    const control = this.branchForm.get(field);

    if (!control || !control.touched || control.valid) {
      return null;
    }

    if (control.hasError('required')) {
      return 'Este campo es obligatorio.';
    }

    if (control.hasError('email')) {
      return 'Ingresá un correo válido.';
    }

    if (control.hasError('pattern')) {
      return 'Ingresá un teléfono válido (6 a 30 caracteres).';
    }

    if (control.hasError('maxlength')) {
      return 'El valor ingresado es demasiado largo.';
    }

    return 'Revisá este campo.';
  }

  statusFieldError(field: string): string | null {
    const backendError = this.statusFieldErrors()[field]?.[0];

    if (backendError) {
      return backendError;
    }

    const control = this.statusForm.get(field);

    if (!control || !control.touched || control.valid) {
      return null;
    }

    if (control.hasError('required')) {
      return 'El motivo es obligatorio.';
    }

    if (control.hasError('maxlength')) {
      return 'El motivo no puede superar los 1000 caracteres.';
    }

    return 'Revisá este campo.';
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

  private handleFormSuccess(title: string): void {
    this.saving.set(false);
    this.formOpen.set(false);
    this.detailOpen.set(false);
    this.toast.success(title, 'Los datos se guardaron correctamente.');
    this.loadBranches();
  }

  private handleFormError(error: ApiError): void {
    this.saving.set(false);

    if (error.status === 422 && error.errors) {
      this.fieldErrors.set(error.errors);
      this.formErrorMessage.set('Revisá los campos marcados.');
      return;
    }

    if (error.status === 409) {
      this.concurrencyConflict.set(true);
      this.formErrorMessage.set(error.message);
      return;
    }

    this.formErrorMessage.set(error.message);
  }

  private clearFormErrors(): void {
    this.fieldErrors.set({});
    this.formErrorMessage.set(null);
    this.concurrencyConflict.set(false);
  }

  private nullIfEmpty(value: string): string | null {
    const trimmed = value.trim();

    return trimmed === '' ? null : trimmed;
  }
}
