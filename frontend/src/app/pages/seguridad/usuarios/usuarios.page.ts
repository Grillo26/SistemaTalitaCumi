import { CommonModule } from '@angular/common';
import {
  Component,
  DestroyRef,
  OnInit,
  computed,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import {
  FormBuilder,
  FormControl,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import { ToastService } from '../../../core/services/toast.service';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../../shared/components/ui/modal/modal.component';
import { PaginationComponent } from '../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { TABLE_IMPORTS } from '../../../shared/components/ui/table';
import { TextareaDirective } from '../../../shared/components/ui/textarea/textarea.directive';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import {
  ApiError,
  EMPTY_USER_LIST_ABILITIES,
  User,
  UserBranch,
  UserCreatePayload,
  UserHistoryEntry,
  UserListAbilities,
  UserRole,
  UserSortDirection,
  UserSortField,
  UserStatusFilter,
  UserUpdatePayload,
  USER_TEXTS,
} from './usuarios.models';
import { UsuariosService } from './usuarios.service';
import { usernameFromName } from './username.util';

type FormMode = 'create' | 'edit';
type StatusMode = 'activate' | 'deactivate';

@Component({
  selector: 'app-usuarios-page',
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
    TextareaDirective,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './usuarios.page.html',
})
export class UsuariosPageComponent implements OnInit {
  private readonly service = inject(UsuariosService);
  private readonly toast = inject(ToastService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  readonly users = signal<User[]>([]);
  readonly loading = signal(false);
  readonly error = signal<ApiError | null>(null);
  readonly meta = signal<{
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  } | null>(null);
  readonly abilities = signal<UserListAbilities>(EMPTY_USER_LIST_ABILITIES);

  readonly roles = signal<UserRole[]>([]);
  readonly branches = signal<UserBranch[]>([]);
  private usernameTouched = false;
  readonly searchControl = new FormControl('', { nonNullable: true });
  readonly roleFilter = signal<number | null>(null);
  readonly branchFilter = signal<number | null>(null);
  readonly stateFilter = signal<UserStatusFilter>('');
  readonly page = signal(1);
  readonly perPage = 10;
  readonly sort = signal<UserSortField>('nombre');
  readonly direction = signal<UserSortDirection>('asc');

  readonly roleOptions = computed(() => [
    { value: '', label: 'Todos los roles' },
    ...this.roles().map((role) => ({
      value: String(role.id),
      label: role.label,
    })),
  ]);

  readonly roleFormOptions = computed(() =>
    this.roles().map((role) => ({
      value: String(role.id),
      label: role.label,
    })),
  );

  readonly branchOptions = computed(() => [
    { value: '', label: 'Todas las sucursales' },
    ...this.branches().map((branch) => ({
      value: String(branch.id),
      label: `${branch.codigo} · ${branch.nombre}`,
    })),
  ]);

  readonly stateOptions = [
    { value: '', label: 'Todos los estados' },
    { value: 'activo', label: 'Activos' },
    { value: 'inactivo', label: 'Inactivos' },
    { value: 'bloqueado', label: 'Bloqueados' },
  ];

  readonly selected = signal<User | null>(null);
  readonly detailOpen = signal(false);

  readonly formOpen = signal(false);
  readonly formMode = signal<FormMode>('create');
  readonly editing = signal<User | null>(null);
  readonly saving = signal(false);
  readonly formError = signal<string | null>(null);
  readonly fieldErrors = signal<Record<string, string[]>>({});
  readonly formConcurrency = signal(false);
  readonly selectedBranches = signal<number[]>([]);
  readonly branchError = signal('');

  readonly form = this.fb.nonNullable.group({
    name: ['', [Validators.required, Validators.maxLength(200)]],
    documento: ['', [Validators.required, Validators.pattern(/^[A-Za-z0-9-]{4,20}$/)]],
    email: ['', [Validators.email, Validators.maxLength(150)]],
    username: ['', [Validators.required, Validators.pattern(/^[A-Za-z0-9._-]{3,100}$/)]],
    rol_id: [null as number | null, [Validators.required]],
  });

  readonly statusOpen = signal(false);
  readonly statusMode = signal<StatusMode>('activate');
  readonly statusTarget = signal<User | null>(null);
  readonly statusSaving = signal(false);
  readonly statusError = signal<string | null>(null);
  readonly statusFieldErrors = signal<Record<string, string[]>>({});
  readonly statusConcurrency = signal(false);
  readonly statusMotivo = new FormControl('', { nonNullable: true });

  readonly resetOpen = signal(false);
  readonly resetTarget = signal<User | null>(null);
  readonly resetting = signal(false);
  readonly resetError = signal<string | null>(null);

  readonly unlockOpen = signal(false);
  readonly unlockTarget = signal<User | null>(null);
  readonly unlocking = signal(false);
  readonly unlockError = signal<string | null>(null);
  readonly unlockFieldErrors = signal<Record<string, string[]>>({});
  readonly unlockConcurrency = signal(false);
  readonly unlockMotivo = new FormControl('', { nonNullable: true });

  readonly sessionsOpen = signal(false);
  readonly sessionsTarget = signal<User | null>(null);
  readonly closingSessions = signal(false);
  readonly sessionsError = signal<string | null>(null);
  readonly sessionsFieldErrors = signal<Record<string, string[]>>({});
  readonly sessionsMotivo = new FormControl('', { nonNullable: true });

  readonly historyOpen = signal(false);
  readonly historyTarget = signal<User | null>(null);
  readonly historyEntries = signal<UserHistoryEntry[]>([]);
  readonly historyLoading = signal(false);
  readonly historyError = signal<string | null>(null);
  readonly historyMeta = signal<{
    current_page: number;
    last_page: number;
  } | null>(null);

  readonly tempOpen = signal(false);
  readonly tempPassword = signal<string | null>(null);
  readonly tempUser = signal<User | null>(null);

  ngOnInit(): void {
    this.loadOptions();
    this.loadUsers();

    this.searchControl.valueChanges
      .pipe(
        debounceTime(350),
        distinctUntilChanged(),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe(() => {
        this.page.set(1);
        this.loadUsers();
      });
  }

  loadUsers(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service
      .list({
        search: this.searchControl.value.trim() || undefined,
        rol_id: this.roleFilter(),
        sucursal_id: this.branchFilter(),
        estado: this.stateFilter(),
        page: this.page(),
        per_page: this.perPage,
        sort: this.sort(),
        direction: this.direction(),
      })
      .subscribe({
        next: (response) => {
          this.users.set(response.data);
          this.meta.set(response.meta);
          this.abilities.set(response.can);
          this.loading.set(false);
        },
        error: (apiError: ApiError) => {
          this.error.set(apiError);
          this.loading.set(false);
        },
      });
  }

  loadOptions(): void {
    this.service.options().subscribe({
      next: (response) => {
        this.roles.set(response.data.roles);
        this.branches.set(response.data.sucursales);

        const activeIds = new Set(response.data.sucursales.map((branch) => branch.id));
        this.selectedBranches.update((ids) => ids.filter((id) => activeIds.has(id)));
      },
      error: (apiError: ApiError) => this.error.set(apiError),
    });
  }

  onRoleChange(value: string): void {
    this.roleFilter.set(value === '' ? null : Number(value));
    this.page.set(1);
    this.loadUsers();
  }

  onBranchChange(value: string): void {
    this.branchFilter.set(value === '' ? null : Number(value));
    this.page.set(1);
    this.loadUsers();
  }

  onStateChange(value: string): void {
    this.stateFilter.set(value as UserStatusFilter);
    this.page.set(1);
    this.loadUsers();
  }

  sortBy(field: UserSortField): void {
    if (this.sort() === field) {
      this.direction.set(this.direction() === 'asc' ? 'desc' : 'asc');
    } else {
      this.sort.set(field);
      this.direction.set('asc');
    }

    this.page.set(1);
    this.loadUsers();
  }

  sortIndicator(field: UserSortField): string {
    if (this.sort() !== field) return '↕';
    return this.direction() === 'asc' ? '↑' : '↓';
  }

  ariaSort(field: UserSortField): 'ascending' | 'descending' | 'none' {
    if (this.sort() !== field) return 'none';
    return this.direction() === 'asc' ? 'ascending' : 'descending';
  }

  goToPage(page: number): void {
    this.page.set(page);
    this.loadUsers();
  }

  openDetail(user: User): void {
    this.selected.set(user);
    this.detailOpen.set(true);

    this.service.get(user.id).subscribe({
      next: (response) => this.selected.set(response.data),
      error: (apiError: ApiError) => this.error.set(apiError),
    });
  }

  closeDetail(): void {
    this.detailOpen.set(false);
    this.selected.set(null);
  }

  openCreate(): void {
    this.formMode.set('create');
    this.editing.set(null);
    this.usernameTouched = false;
    this.form.reset({
      name: '',
      documento: '',
      email: '',
      username: '',
      rol_id: null,
    });
    this.selectedBranches.set([]);
    this.fieldErrors.set({});
    this.formError.set(null);
    this.formConcurrency.set(false);
    this.branchError.set('');
    this.loadOptions();
    this.formOpen.set(true);
  }

  openEdit(user: User): void {
    this.detailOpen.set(false);
    this.formMode.set('edit');
    this.editing.set(user);
    this.usernameTouched = true;
    this.form.reset({
      name: user.nombre_completo,
      documento: user.documento ?? '',
      email: user.email ?? '',
      username: user.username ?? '',
      rol_id: user.rol?.id ?? null,
    });
    this.selectedBranches.set(user.sucursales.map((branch) => branch.id));
    this.fieldErrors.set({});
    this.formError.set(null);
    this.formConcurrency.set(false);
    this.branchError.set('');
    this.loadOptions();
    this.formOpen.set(true);
  }

  onNameInput(name: string): void {
    if (this.formMode() !== 'create' || this.usernameTouched) {
      return;
    }

    this.form.controls.username.setValue(usernameFromName(name), { emitEvent: false });
  }

  onUsernameInput(): void {
    this.usernameTouched = true;
  }

  userSubtitle(user: User): string {
    return user.email ? `${user.nombre_completo} · ${user.email}` : user.nombre_completo;
  }

  closeForm(): void {
    this.formOpen.set(false);
    this.editing.set(null);
    this.saving.set(false);
  }

  onFormRoleChange(value: string): void {
    this.form.controls.rol_id.setValue(value === '' ? null : Number(value));
    this.form.controls.rol_id.markAsTouched();
  }

  isBranchSelected(id: number): boolean {
    return this.selectedBranches().includes(id);
  }

  toggleBranch(id: number): void {
    this.branchError.set('');
    this.selectedBranches.update((ids) =>
      ids.includes(id) ? ids.filter((value) => value !== id) : [...ids, id],
    );
  }

  fieldError(field: string): string {
    return this.fieldErrors()[field]?.[0] ?? '';
  }

  submitForm(): void {
    this.form.markAllAsTouched();
    this.formError.set(null);
    this.fieldErrors.set({});
    this.formConcurrency.set(false);
    this.branchError.set('');

    if (this.form.invalid) {
      return;
    }

    if (this.selectedBranches().length === 0) {
      this.branchError.set('Asigná al menos una sucursal.');
      return;
    }

    const value = this.form.getRawValue();

    const payload: UserCreatePayload = {
      name: value.name.trim(),
      documento: value.documento.trim(),
      email: value.email.trim() || null,
      username: value.username.trim(),
      rol_id: value.rol_id as number,
      sucursales: this.selectedBranches(),
    };

    this.saving.set(true);

    if (this.formMode() === 'create') {
      this.service.create(payload).subscribe({
        next: (response) => {
          this.saving.set(false);
          this.closeForm();
          this.toast.success('Usuario creado', USER_TEXTS.createSuccess);
          this.showTemporaryPassword(response.password_temporal, response.data);
          this.loadUsers();
        },
        error: (apiError: ApiError) => {
          this.saving.set(false);
          this.applyFormError(apiError);
        },
      });

      return;
    }

    const editing = this.editing();

    if (editing === null) {
      this.saving.set(false);
      return;
    }

    const updatePayload: UserUpdatePayload = {
      ...payload,
      version: editing.version,
    };

    this.service.update(editing.id, updatePayload).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.closeForm();
        this.toast.success('Usuario actualizado', USER_TEXTS.updateSuccess);
        this.refreshSelected(response.data);
        this.loadUsers();
      },
      error: (apiError: ApiError) => {
        this.saving.set(false);
        this.applyFormError(apiError);
      },
    });
  }

  reloadEditing(): void {
    const editing = this.editing();

    if (editing === null) return;

    this.service.get(editing.id).subscribe({
      next: (response) => {
        this.openEdit(response.data);
      },
      error: (apiError: ApiError) => this.formError.set(apiError.message),
    });
  }

  openStatus(user: User, mode: StatusMode): void {
    this.detailOpen.set(false);
    this.statusTarget.set(user);
    this.statusMode.set(mode);
    this.statusMotivo.reset('');
    this.statusError.set(null);
    this.statusFieldErrors.set({});
    this.statusConcurrency.set(false);
    this.statusOpen.set(true);
  }

  closeStatus(): void {
    this.statusOpen.set(false);
    this.statusTarget.set(null);
  }

  submitStatus(): void {
    const target = this.statusTarget();

    if (target === null) return;

    const motivo = this.statusMotivo.value.trim();

    this.statusFieldErrors.set({});
    this.statusError.set(null);
    this.statusConcurrency.set(false);

    if (this.statusMode() === 'deactivate' && motivo === '') {
      this.statusFieldErrors.set({
        motivo: ['El motivo es obligatorio para desactivar un usuario.'],
      });
      return;
    }

    this.statusSaving.set(true);

    const payload = {
      motivo: motivo === '' ? null : motivo,
      version: target.version,
    };

    const request =
      this.statusMode() === 'activate'
        ? this.service.activate(target.id, payload)
        : this.service.deactivate(target.id, payload);

    request.subscribe({
      next: (response) => {
        this.statusSaving.set(false);
        this.closeStatus();
        this.toast.success(
          this.statusMode() === 'activate' ? 'Usuario activado' : 'Usuario desactivado',
          this.statusMode() === 'activate'
            ? 'El usuario puede volver a iniciar sesión.'
            : 'Las sesiones activas fueron invalidadas.',
        );
        this.refreshSelected(response.data);
        this.loadUsers();
      },
      error: (apiError: ApiError) => {
        this.statusSaving.set(false);
        this.applyStatusError(apiError);
      },
    });
  }

  reloadStatusTarget(): void {
    const target = this.statusTarget();

    if (target === null) return;

    this.service.get(target.id).subscribe({
      next: (response) => {
        this.statusTarget.set(response.data);
        this.statusConcurrency.set(false);
        this.statusError.set(null);
      },
      error: (apiError: ApiError) => this.statusError.set(apiError.message),
    });
  }

  openReset(user: User): void {
    this.detailOpen.set(false);
    this.resetTarget.set(user);
    this.resetError.set(null);
    this.resetOpen.set(true);
  }

  closeReset(): void {
    this.resetOpen.set(false);
    this.resetTarget.set(null);
  }

  confirmReset(): void {
    const target = this.resetTarget();

    if (target === null) return;

    this.resetting.set(true);
    this.resetError.set(null);

    this.service.resetPassword(target.id).subscribe({
      next: (response) => {
        this.resetting.set(false);
        this.closeReset();
        this.toast.success(
          'Contraseña reseteada',
          `Se cerraron ${response.sessions_invalidated} sesión(es).`,
        );
        this.showTemporaryPassword(response.password_temporal, response.data);
        this.refreshSelected(response.data);
        this.loadUsers();
      },
      error: (apiError: ApiError) => {
        this.resetting.set(false);
        this.resetError.set(apiError.message);
      },
    });
  }

  openUnlock(user: User): void {
    this.detailOpen.set(false);
    this.unlockTarget.set(user);
    this.unlockMotivo.reset('');
    this.unlockError.set(null);
    this.unlockFieldErrors.set({});
    this.unlockConcurrency.set(false);
    this.unlockOpen.set(true);
  }

  closeUnlock(): void {
    this.unlockOpen.set(false);
    this.unlockTarget.set(null);
  }

  submitUnlock(): void {
    const target = this.unlockTarget();

    if (target === null) return;

    const motivo = this.unlockMotivo.value.trim();

    this.unlockFieldErrors.set({});
    this.unlockError.set(null);
    this.unlockConcurrency.set(false);

    if (motivo === '') {
      this.unlockFieldErrors.set({
        motivo: ['El motivo del desbloqueo es obligatorio.'],
      });
      return;
    }

    this.unlocking.set(true);

    this.service
      .unlock(target.id, { motivo, version: target.version })
      .subscribe({
        next: (response) => {
          this.unlocking.set(false);
          this.closeUnlock();
          this.toast.success('Cuenta desbloqueada', 'El usuario puede volver a intentar ingresar.');
          this.refreshSelected(response.data);
          this.loadUsers();
        },
        error: (apiError: ApiError) => {
          this.unlocking.set(false);
          this.applyUnlockError(apiError);
        },
      });
  }

  reloadUnlockTarget(): void {
    const target = this.unlockTarget();

    if (target === null) return;

    this.service.get(target.id).subscribe({
      next: (response) => {
        this.unlockTarget.set(response.data);
        this.unlockConcurrency.set(false);
        this.unlockError.set(null);
      },
      error: (apiError: ApiError) => this.unlockError.set(apiError.message),
    });
  }

  openSessions(user: User): void {
    this.detailOpen.set(false);
    this.sessionsTarget.set(user);
    this.sessionsMotivo.reset('');
    this.sessionsError.set(null);
    this.sessionsFieldErrors.set({});
    this.sessionsOpen.set(true);
  }

  closeSessions(): void {
    this.sessionsOpen.set(false);
    this.sessionsTarget.set(null);
  }

  submitSessions(): void {
    const target = this.sessionsTarget();

    if (target === null) return;

    const motivo = this.sessionsMotivo.value.trim();

    this.sessionsFieldErrors.set({});
    this.sessionsError.set(null);

    if (motivo === '') {
      this.sessionsFieldErrors.set({
        motivo: ['El motivo del cierre de sesiones es obligatorio.'],
      });
      return;
    }

    this.closingSessions.set(true);

    this.service.closeSessions(target.id, { motivo }).subscribe({
      next: (response) => {
        this.closingSessions.set(false);
        this.closeSessions();
        this.toast.success(
          'Sesiones cerradas',
          `Se cerraron ${response.sesiones_cerradas} sesión(es).`,
        );
        this.refreshSelected(response.data);
        this.loadUsers();
      },
      error: (apiError: ApiError) => {
        this.closingSessions.set(false);
        this.applySessionsError(apiError);
      },
    });
  }

  openHistory(user: User): void {
    this.detailOpen.set(false);
    this.historyTarget.set(user);
    this.historyEntries.set([]);
    this.historyMeta.set(null);
    this.historyError.set(null);
    this.historyOpen.set(true);
    this.loadHistory(1);
  }

  closeHistory(): void {
    this.historyOpen.set(false);
    this.historyTarget.set(null);
  }

  loadHistory(page: number): void {
    const target = this.historyTarget();

    if (target === null) return;

    this.historyLoading.set(true);
    this.historyError.set(null);

    this.service.history(target.id, page).subscribe({
      next: (response) => {
        this.historyEntries.set(response.data);
        this.historyMeta.set({
          current_page: response.meta.current_page,
          last_page: response.meta.last_page,
        });
        this.historyLoading.set(false);
      },
      error: (apiError: ApiError) => {
        this.historyError.set(apiError.message);
        this.historyLoading.set(false);
      },
    });
  }

  historyPageChange(page: number): void {
    this.loadHistory(page);
  }

  historyActionLabel(action: string): string {
    const labels: Record<string, string> = {
      CREAR: 'Creación',
      EDITAR: 'Edición',
      ACTIVAR: 'Activación',
      DESACTIVAR: 'Desactivación',
      RESETEAR_PASSWORD: 'Reseteo de contraseña',
      DESBLOQUEAR: 'Desbloqueo',
      CERRAR_SESIONES: 'Cierre de sesiones',
      CAMBIAR_PASSWORD: 'Cambio de contraseña',
    };

    return labels[action] ?? action;
  }

  historyResultLabel(result: string | null): string {
    return result === 'exito' ? 'Éxito' : result === 'fallo' ? 'Fallo' : result === 'denegado' ? 'Denegado' : '—';
  }

  showTemporaryPassword(password: string, user: User): void {
    this.tempPassword.set(password);
    this.tempUser.set(user);
    this.tempOpen.set(true);
  }

  closeTemporaryPassword(): void {
    this.tempOpen.set(false);
    this.tempPassword.set(null);
    this.tempUser.set(null);
  }

  async copyTemporaryPassword(): Promise<void> {
    const password = this.tempPassword();

    if (password === null) return;

    try {
      await navigator.clipboard.writeText(password);
      this.toast.success('Copiada', 'La contraseña temporal fue copiada al portapapeles.');
    } catch {
      this.toast.warning('No se pudo copiar', 'Seleccioná la contraseña manualmente.');
    }
  }

  branchSummary(user: User): string {
    if (user.sucursales.length === 0) return 'Sin sucursales';

    const names = user.sucursales.slice(0, 2).map((branch) => branch.codigo);
    const remaining = user.sucursales.length - names.length;

    return remaining > 0 ? `${names.join(', ')} +${remaining}` : names.join(', ');
  }

  formatDateTime(value: string | null): string {
    if (value === null) return '—';

    return new Intl.DateTimeFormat('es-BO', {
      dateStyle: 'short',
      timeStyle: 'short',
    }).format(new Date(value));
  }

  blockedMessage(user: User): string {
    return `Bloqueada hasta ${this.formatDateTime(user.bloqueado_hasta)}. Podés desbloquearla manualmente.`;
  }

  unlockMessage(user: User): string {
    const until = user.bloqueado_hasta
      ? ` Bloqueada hasta ${this.formatDateTime(user.bloqueado_hasta)}.`
      : '';

    return `La cuenta tiene ${user.intentos_fallidos} intento(s) fallido(s).${until}`;
  }

  stateLabel(user: User): string {
    if (user.bloqueado) return 'Bloqueado';
    return user.activo ? 'Activo' : 'Inactivo';
  }

  stateColor(user: User): 'success' | 'error' | 'gray' {
    if (user.bloqueado) return 'error';
    return user.activo ? 'success' : 'gray';
  }

  initials(name: string): string {
    return name
      .split(/\s+/)
      .filter(Boolean)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('');
  }

  private refreshSelected(user: User): void {
    if (this.selected()?.id === user.id) {
      this.selected.set(user);
    }
  }

  private applyFormError(apiError: ApiError): void {
    if (apiError.errors) {
      this.fieldErrors.set(apiError.errors);
    }

    if (apiError.status === 409) {
      this.formConcurrency.set(true);
      return;
    }

    this.formError.set(apiError.message);
  }

  private applyStatusError(apiError: ApiError): void {
    if (apiError.errors) {
      this.statusFieldErrors.set(apiError.errors);
    }

    if (apiError.status === 409) {
      this.statusConcurrency.set(true);
      return;
    }

    this.statusError.set(apiError.message);
  }

  private applyUnlockError(apiError: ApiError): void {
    if (apiError.errors) {
      this.unlockFieldErrors.set(apiError.errors);
    }

    if (apiError.status === 409) {
      this.unlockConcurrency.set(true);
      return;
    }

    this.unlockError.set(apiError.message);
  }

  private applySessionsError(apiError: ApiError): void {
    if (apiError.errors) {
      this.sessionsFieldErrors.set(apiError.errors);
    }

    this.sessionsError.set(apiError.message);
  }
}
