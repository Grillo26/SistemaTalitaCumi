import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { environment } from '../../../environments/environment';
import {
  ApiUser,
  LoginResponse,
  MeResponse,
  SessionUser,
} from '../models/auth.models';
import { SessionStore } from './session-store.service';

/**
 * Autenticación real contra /api/auth (módulo 02, Fase 1).
 *
 * Mantiene la API pública histórica (`user`, `isAuthenticated`, `logout`)
 * y agrega `initialize`, `mustChangePassword`, `hasPermission` y
 * `changePassword`.
 */
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly store = inject(SessionStore);

  readonly user = this.store.user;
  readonly isAuthenticated = computed(() => this.store.user() !== null);
  readonly mustChangePassword = computed(
    () => this.store.user()?.mustChangePassword === true,
  );
  readonly permissions = computed(
    () => this.store.user()?.permissions ?? [],
  );

  /**
   * Rehidrata la sesión al arrancar la aplicación si hay token.
   * Un 401 limpia la sesión; un fallo de red también, para no dejar la UI
   * en un estado inconsistente.
   */
  async initialize(): Promise<void> {
    if (this.store.token() === null) {
      return;
    }

    try {
      const response = await firstValueFrom(
        this.http.get<MeResponse>(`${environment.apiUrl}/auth/me`),
      );
      this.store.setUser(this.mapUser(response.data));
    } catch {
      this.store.clear();
    }
  }

  async login(
    identifier: string,
    password: string,
    remember: boolean,
  ): Promise<SessionUser> {
    const response = await firstValueFrom(
      this.http.post<LoginResponse>(`${environment.apiUrl}/auth/login`, {
        identificador: identifier.trim(),
        password,
        recordar: remember,
      }),
    );

    const user = this.mapUser(response.data.user);
    this.store.setSession(response.data.token, user);
    this.store.rememberIdentifier(remember ? identifier : '');

    return user;
  }

  async logout(): Promise<void> {
    try {
      await firstValueFrom(
        this.http.post<void>(`${environment.apiUrl}/auth/logout`, {}),
      );
    } catch {
      // El cierre local debe funcionar aunque el token ya no sea válido.
    }

    this.store.clear();
  }

  async changePassword(
    currentPassword: string,
    newPassword: string,
    confirmation: string,
  ): Promise<void> {
    await firstValueFrom(
      this.http.post<{ message: string }>(
        `${environment.apiUrl}/auth/cambiar-password`,
        {
          current_password: currentPassword,
          password: newPassword,
          password_confirmation: confirmation,
        },
      ),
    );

    const user = this.store.user();

    if (user !== null) {
      this.store.setUser({ ...user, mustChangePassword: false });
    }
  }

  hasPermission(permission: string): boolean {
    return this.permissions().includes(permission);
  }

  rememberedIdentifier(): string {
    return this.store.rememberedIdentifier();
  }

  private mapUser(data: ApiUser): SessionUser {
    return {
      id: data.id,
      name: data.nombre_completo,
      email: data.email,
      username: data.username,
      documento: data.documento,
      role: data.rol,
      permissions: data.permisos,
      branches: data.sucursales,
      globalScope: data.alcance_global,
      requiresBranchAssignment: data.requires_branch_assignment,
      mustChangePassword: data.debe_cambiar_password,
      lastAccess: data.ultimo_acceso,
      passwordPolicy: data.password_policy,
    };
  }
}
