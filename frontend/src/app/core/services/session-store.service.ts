import { Injectable, signal } from '@angular/core';
import { SessionUser } from '../models/auth.models';

const TOKEN_KEY = 'talitacumi.token';
const REMEMBER_KEY = 'talitacumi.remember_identifier';

/**
 * Estado de sesión en memoria + token en sessionStorage.
 *
 * El token nunca se guarda en localStorage: "Recordarme" solo conserva el
 * identificador para prellenar el login. Esto reduce el riesgo ante XSS
 * persistente y cumple RF-A14 dentro de las restricciones de la Etapa 1.
 */
@Injectable({ providedIn: 'root' })
export class SessionStore {
  private readonly tokenSignal = signal<string | null>(this.readToken());
  private readonly userSignal = signal<SessionUser | null>(null);

  readonly token = this.tokenSignal.asReadonly();
  readonly user = this.userSignal.asReadonly();

  setSession(token: string, user: SessionUser): void {
    sessionStorage.setItem(TOKEN_KEY, token);
    this.tokenSignal.set(token);
    this.userSignal.set(user);
  }

  setUser(user: SessionUser): void {
    this.userSignal.set(user);
  }

  clear(): void {
    sessionStorage.removeItem(TOKEN_KEY);
    this.tokenSignal.set(null);
    this.userSignal.set(null);
  }

  rememberIdentifier(identifier: string): void {
    const value = identifier.trim();

    if (value === '') {
      localStorage.removeItem(REMEMBER_KEY);
      return;
    }

    localStorage.setItem(REMEMBER_KEY, value);
  }

  rememberedIdentifier(): string {
    try {
      return localStorage.getItem(REMEMBER_KEY) ?? '';
    } catch {
      return '';
    }
  }

  private readToken(): string | null {
    try {
      return sessionStorage.getItem(TOKEN_KEY);
    } catch {
      return null;
    }
  }
}
