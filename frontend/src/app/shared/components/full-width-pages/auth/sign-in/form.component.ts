import { CommonModule } from '@angular/common';
import { HttpErrorResponse } from '@angular/common/http';
import { Component, EventEmitter, Input, Output, inject } from '@angular/core';
import {
  FormControl,
  FormGroup,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router, RouterModule } from '@angular/router';
import { SignInFormData } from './schema';
import { ButtonComponent } from '../../../ui/button/button.component';
import { InputDirective } from '../../../ui/input/input.directive';
import { AuthService } from '../../../../../core/services/auth.service';
import { AUTH_TEXTS, ApiError } from '../../../../../core/models/auth.models';

@Component({
  selector: 'app-sign-in-form-fields',
  imports: [InputDirective, ButtonComponent, CommonModule, ReactiveFormsModule, RouterModule],
  templateUrl: './form.component.html',
  host: {
    class: 'contents',
  },
  styles: [
    `
      :host {
        display: contents;
      }
    `,
  ],
})
export class SignInFormFieldsComponent {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  @Input() className: string = '';
  @Output() formSubmit = new EventEmitter<SignInFormData>();

  readonly sessionExpired =
    this.route.snapshot.queryParamMap.get('expirada') === '1';
  readonly sessionExpiredMessage = AUTH_TEXTS.sessionExpired;

  showPassword = false;
  isSubmitting = false;
  errorMessage = '';

  signInForm = new FormGroup({
    identificador: new FormControl(this.auth.rememberedIdentifier(), {
      nonNullable: true,
      validators: [Validators.required, Validators.maxLength(150)],
    }),
    password: new FormControl('', {
      nonNullable: true,
      validators: [Validators.required, Validators.maxLength(255)],
    }),
    keepLoggedIn: new FormControl(this.auth.rememberedIdentifier() !== '', {
      nonNullable: true,
    }),
  });

  togglePasswordVisibility() {
    this.showPassword = !this.showPassword;
  }

  isFieldInvalid(fieldName: 'identificador' | 'password'): boolean {
    const control = this.signInForm.get(fieldName);
    return !!(control && control.invalid && (control.dirty || control.touched));
  }

  getFieldError(fieldName: 'identificador' | 'password'): string {
    const control = this.signInForm.get(fieldName);
    if (!control || !control.errors) return '';

    if (control.errors['required']) {
      return fieldName === 'identificador'
        ? 'Ingresá tu correo o nombre de usuario.'
        : 'Ingresá tu contraseña.';
    }

    if (control.errors['maxlength']) {
      return 'El valor ingresado es demasiado largo.';
    }

    return '';
  }

  async onSubmit() {
    if (this.signInForm.invalid) {
      this.signInForm.markAllAsTouched();
      return;
    }

    this.isSubmitting = true;
    this.errorMessage = '';
    const data: SignInFormData = this.signInForm.getRawValue();
    this.formSubmit.emit(data);

    try {
      await this.auth.login(
        data.identificador,
        data.password,
        data.keepLoggedIn ?? false,
      );

      await this.router.navigateByUrl(
        this.auth.mustChangePassword() ? '/cambiar-password' : '/',
      );
    } catch (error: unknown) {
      this.errorMessage = this.mapError(error);
      this.isSubmitting = false;
    }
  }

  private mapError(error: unknown): string {
    if (error instanceof HttpErrorResponse) {
      if (error.status === 401) return AUTH_TEXTS.invalidCredentials;
      if (error.status === 429) return AUTH_TEXTS.tooManyAttempts;
      if (error.status === 0) return AUTH_TEXTS.networkError;

      const body = error.error as Partial<ApiError> | null;

      if (typeof body?.message === 'string' && body.message.trim() !== '') {
        return body.message;
      }
    }

    return AUTH_TEXTS.genericLoginError;
  }
}
