import { CommonModule } from '@angular/common';
import {
  Component,
  OnInit,
  computed,
  inject,
  signal,
} from '@angular/core';
import { FormControl, ReactiveFormsModule } from '@angular/forms';
import { ToastService } from '../../../core/services/toast.service';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { RoleMatrixDateInputComponent } from './role-matrix-date-input.component';
import {
  ApiError,
  PermissionAction,
  PermissionModule,
  ROLE_MATRIX_TEXTS,
  RoleMatrix,
  RoleMatrixAbilitiesResponse,
  RoleMatrixHistory,
} from './roles-permisos.models';
import { RolesPermisosService } from './roles-permisos.service';

function setsEqual(a: Set<number>, b: Set<number>): boolean {
  if (a.size !== b.size) return false;

  for (const value of a) {
    if (!b.has(value)) return false;
  }

  return true;
}

@Component({
  selector: 'app-roles-permisos-page',
  imports: [
    CommonModule,
    ReactiveFormsModule,
    PageBreadcrumbComponent,
    ComponentCardComponent,
    ButtonComponent,
    BadgeComponent,
    AlertComponent,
    RoleMatrixDateInputComponent,
  ],
  templateUrl: './roles-permisos.page.html',
})
export class RolesPermisosPageComponent implements OnInit {
  private readonly service = inject(RolesPermisosService);
  private readonly toast = inject(ToastService);

  readonly texts = ROLE_MATRIX_TEXTS;

  readonly roles = signal<RoleMatrix[]>([]);
  readonly modules = signal<PermissionModule[]>([]);
  readonly totalPermissions = signal(0);
  readonly abilities = signal<RoleMatrixAbilitiesResponse>({
    edit: false,
    export: false,
  });

  readonly selectedRole = signal<RoleMatrix | null>(null);
  readonly selectedPermissions = signal<Set<number>>(new Set());
  readonly originalPermissions = signal<Set<number>>(new Set());

  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly error = signal<ApiError | null>(null);
  readonly saveError = signal<string | null>(null);
  readonly concurrency = signal(false);
  readonly fieldErrors = signal<Record<string, string[]>>({});

  readonly historyDate = new FormControl(this.today(), { nonNullable: true });
  readonly history = signal<RoleMatrixHistory | null>(null);
  readonly historyLoading = signal(false);
  readonly historyError = signal<string | null>(null);

  readonly canEdit = computed(
    () => this.abilities().edit && this.selectedRole()?.can.edit === true,
  );

  readonly isDirty = computed(() =>
    !setsEqual(this.selectedPermissions(), this.originalPermissions()),
  );

  readonly selectedCount = computed(() => this.selectedPermissions().size);

  ngOnInit(): void {
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service.list().subscribe({
      next: (response) => {
        this.roles.set(response.data.roles);
        this.modules.set(response.data.modulos);
        this.totalPermissions.set(response.data.total_permisos);
        this.abilities.set(response.can);

        const currentId = this.selectedRole()?.id;
        const next =
          response.data.roles.find((role) => role.id === currentId) ??
          response.data.roles[0] ??
          null;

        if (next !== null) {
          this.selectRole(next);
        } else {
          this.selectedRole.set(null);
          this.selectedPermissions.set(new Set());
          this.originalPermissions.set(new Set());
        }

        this.loading.set(false);
      },
      error: (apiError: ApiError) => {
        this.error.set(apiError);
        this.loading.set(false);
      },
    });
  }

  selectRole(role: RoleMatrix): void {
    this.selectedRole.set(role);
    this.selectedPermissions.set(new Set(role.permisos));
    this.originalPermissions.set(new Set(role.permisos));
    this.saveError.set(null);
    this.concurrency.set(false);
    this.fieldErrors.set({});
    this.history.set(null);
    this.historyError.set(null);
  }

  isChecked(action: PermissionAction): boolean {
    return this.selectedPermissions().has(action.id);
  }

  isLocked(action: PermissionAction): boolean {
    return this.selectedRole()?.es_superadmin === true && action.critico;
  }

  toggle(action: PermissionAction): void {
    if (!this.canEdit() || this.isLocked(action)) {
      return;
    }

    const next = new Set(this.selectedPermissions());

    if (next.has(action.id)) {
      next.delete(action.id);
    } else {
      next.add(action.id);
    }

    this.selectedPermissions.set(next);
    this.saveError.set(null);
    this.concurrency.set(false);
  }

  discard(): void {
    this.selectedPermissions.set(new Set(this.originalPermissions()));
    this.saveError.set(null);
    this.concurrency.set(false);
    this.fieldErrors.set({});
  }

  reload(): void {
    this.concurrency.set(false);
    this.saveError.set(null);
    this.load();
  }

  save(): void {
    const role = this.selectedRole();

    if (role === null) {
      return;
    }

    if (!this.isDirty()) {
      this.toast.info('Sin cambios', ROLE_MATRIX_TEXTS.unchanged);
      return;
    }

    this.saving.set(true);
    this.saveError.set(null);
    this.concurrency.set(false);
    this.fieldErrors.set({});

    this.service
      .update(role.id, {
        permisos: [...this.selectedPermissions()],
        version: role.version,
      })
      .subscribe({
        next: (response) => {
          this.saving.set(false);
          this.roles.update((roles) =>
            roles.map((item) =>
              item.id === response.data.role.id ? response.data.role : item,
            ),
          );
          this.abilities.set(response.can);
          this.selectRole(response.data.role);
          this.history.set(null);
          this.toast.success('Matriz actualizada', ROLE_MATRIX_TEXTS.saveSuccess);
        },
        error: (apiError: ApiError) => {
          this.saving.set(false);
          this.applySaveError(apiError);
        },
      });
  }

  loadHistory(): void {
    const role = this.selectedRole();
    const fecha = this.historyDate.value;

    if (role === null || fecha === '') {
      return;
    }

    this.historyLoading.set(true);
    this.historyError.set(null);
    this.history.set(null);

    this.service.history(role.id, fecha).subscribe({
      next: (response) => {
        this.history.set(response.data);
        this.historyLoading.set(false);
      },
      error: (apiError: ApiError) => {
        this.historyError.set(apiError.message);
        this.historyLoading.set(false);
      },
    });
  }

  formatDate(value: string | null): string {
    if (value === null) return '—';

    return new Intl.DateTimeFormat('es-BO', {
      dateStyle: 'short',
      timeStyle: 'short',
    }).format(new Date(value));
  }

  private applySaveError(apiError: ApiError): void {
    if (apiError.errors) {
      this.fieldErrors.set(apiError.errors);
    }

    if (apiError.status === 409 && apiError.code === 'MATRIX_VERSION_CONFLICT') {
      this.concurrency.set(true);
      this.saveError.set(apiError.message);
      return;
    }

    if (apiError.status === 409 && apiError.code === 'CRITICAL_PERMISSION_PROTECTED') {
      this.saveError.set(apiError.message);
      return;
    }

    this.saveError.set(apiError.message);
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
