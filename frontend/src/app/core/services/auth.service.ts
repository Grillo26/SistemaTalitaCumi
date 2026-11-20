import { Injectable, computed, signal } from '@angular/core';

const SESSION_KEY = 'talitacumi.session';

export interface SessionUser {
  name: string;
  email: string;
  role: string;
}

/**
 * Autenticación simulada para la maqueta del sistema.
 * Cualquier correo y contraseña son aceptados; la sesión se guarda localmente.
 */
@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly userSignal = signal<SessionUser | null>(this.readSession());

  readonly user = this.userSignal.asReadonly();
  readonly isAuthenticated = computed(() => this.userSignal() !== null);

  async login(email: string, remember: boolean): Promise<SessionUser> {
    await new Promise((resolve) => setTimeout(resolve, 650));

    const user: SessionUser = {
      name: this.buildName(email),
      email: email.trim(),
      role: 'Recepción',
    };

    const primary = remember ? localStorage : sessionStorage;
    const secondary = remember ? sessionStorage : localStorage;
    primary.setItem(SESSION_KEY, JSON.stringify(user));
    secondary.removeItem(SESSION_KEY);

    this.userSignal.set(user);
    return user;
  }

  logout(): void {
    localStorage.removeItem(SESSION_KEY);
    sessionStorage.removeItem(SESSION_KEY);
    this.userSignal.set(null);
  }

  private readSession(): SessionUser | null {
    try {
      const raw =
        localStorage.getItem(SESSION_KEY) ?? sessionStorage.getItem(SESSION_KEY);

      if (!raw) return null;
      const parsed = JSON.parse(raw) as Partial<SessionUser>;
      if (!parsed.email) return null;

      return {
        name: parsed.name?.trim() || this.buildName(parsed.email),
        email: parsed.email,
        role: parsed.role?.trim() || 'Recepción',
      };
    } catch {
      return null;
    }
  }

  private buildName(email: string): string {
    const localPart = email.split('@')[0] ?? '';
    const words = localPart
      .split(/[._-]+/)
      .map((part) => part.trim())
      .filter(Boolean)
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1).toLowerCase());

    return words.join(' ') || 'Recepción';
  }
}
