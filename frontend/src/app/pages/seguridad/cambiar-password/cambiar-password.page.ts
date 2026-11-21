import { HttpErrorResponse } from '@angular/common/http';
import { Component, computed, inject, signal } from '@angular/core';
import {
  FormBuilder,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { AUTH_TEXTS, ApiError } from '../../../core/models/auth.models';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';

/**
 * Cambio de contraseña propio y forzado (RF-A03, RF-A13).
 */
@Component({
  selector: 'app-cambiar-password-page',
  imports: [
    ReactiveFormsModule,
    PageBreadcrumbComponent,
    ComponentCardComponent,
    ButtonComponent,
    AlertComponent,
    InputDirective,
  ],
  templateUrl: './cambiar-password.page.html',
})
export class CambiarPasswordPageComponent {
  private readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);
  private readonly fb = inject(FormBuilder);

  readonly mustChange = this.auth.mustChangePassword;
  readonly policy = computed(() => this.auth.user()?.passwordPolicy ?? null);
  readonly saving = signal(false);
  readonly errorMessage = signal<string | null>(null);
  readonly fieldErrors = signal<Record<string, string[]>>({});

  readonly form = this.fb.nonNullable.group({
    currentPassword: ['', [Validators.required]],
    newPassword: ['', [Validators.required]],
    confirmation: ['', [Validators.required]],
  });

  get policyHint(): string {
    const policy = this.policy();

    if (policy === null) {
      return 'La contraseña debe cumplir la política vigente.';
    }

    const parts = [`Mínimo ${policy.min_length} caracteres`];

    if (policy.require_uppercase) parts.push('una mayúscula');
    if (policy.require_lowercase) parts.push('una minúscula');
    if (policy.require_number) parts.push('un número');
    if (policy.require_symbol) parts.push('un símbolo');

    return `${parts.join(', ')}. No se repiten las últimas ${policy.history_count} contraseñas.`;
  }

  fieldError(field: string): string {
    return this.fieldErrors()[field]?.[0] ?? '';
  }

  async submit(): Promise<void> {
    this.errorMessage.set(null);
    this.fieldErrors.set({});

    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const value = this.form.getRawValue();

    if (value.newPassword !== value.confirmation) {
      this.fieldErrors.set({
        confirmation: ['La confirmación no coincide con la nueva contraseña.'],
      });
      return;
    }

    this.saving.set(true);

    try {
      await this.auth.changePassword(
        value.currentPassword,
        value.newPassword,
        value.confirmation,
      );

      this.toast.success('Contraseña actualizada', AUTH_TEXTS.passwordChanged);
      await this.router.navigateByUrl('/');
    } catch (error: unknown) {
      this.applyError(error);
    } finally {
      this.saving.set(false);
    }
  }

  private applyError(error: unknown): void {
    if (error instanceof HttpErrorResponse) {
      const body = error.error as Partial<ApiError> | null;

      if (body?.errors && typeof body.errors === 'object') {
        this.fieldErrors.set(body.errors);
      }

      if (error.status === 401) {
        this.errorMessage.set(AUTH_TEXTS.invalidCredentials);
        return;
      }

      if (error.status === 403) {
        this.errorMessage.set(
          body?.message ?? 'No tenés permisos para cambiar la contraseña.',
        );
        return;
      }

      if (error.status === 0) {
        this.errorMessage.set(AUTH_TEXTS.networkError);
        return;
      }

      if (typeof body?.message === 'string' && body.message.trim() !== '') {
        this.errorMessage.set(body.message);
        return;
      }
    }

    this.errorMessage.set('No se pudo cambiar la contraseña. Intentá nuevamente.');
  }
}
