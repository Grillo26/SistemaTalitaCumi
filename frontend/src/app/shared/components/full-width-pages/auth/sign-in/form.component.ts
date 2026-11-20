import { CommonModule } from '@angular/common';
import { Component, EventEmitter, Input, Output, inject } from '@angular/core';
import {
  FormControl,
  FormGroup,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { Router, RouterModule } from '@angular/router';
import { SignInFormData } from './schema';
import { ButtonComponent } from '../../../ui/button/button.component';
import { InputDirective } from '../../../ui/input/input.directive';
import { AuthService } from '../../../../../core/services/auth.service';

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

  @Input() className: string = '';
  @Output() formSubmit = new EventEmitter<SignInFormData>();

  showPassword = false;
  isSubmitting = false;
  errorMessage = '';

  signInForm = new FormGroup({
    email: new FormControl('', {
      nonNullable: true,
      validators: [Validators.required, Validators.email],
    }),
    password: new FormControl('', {
      nonNullable: true,
      validators: [Validators.required],
    }),
    keepLoggedIn: new FormControl(false, {
      nonNullable: true,
    }),
  });

  togglePasswordVisibility() {
    this.showPassword = !this.showPassword;
  }

  isFieldInvalid(fieldName: 'email' | 'password'): boolean {
    const control = this.signInForm.get(fieldName);
    return !!(control && control.invalid && (control.dirty || control.touched));
  }

  getFieldError(fieldName: 'email' | 'password'): string {
    const control = this.signInForm.get(fieldName);
    if (!control || !control.errors) return '';

    if (fieldName === 'email') {
      if (control.errors['required']) return 'El correo es obligatorio';
      if (control.errors['email']) return 'Ingresa un correo válido';
    }

    if (fieldName === 'password') {
      if (control.errors['required']) return 'La contraseña es obligatoria';
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
      await this.auth.login(data.email, data.keepLoggedIn ?? false);
      await this.router.navigateByUrl('/');
    } catch {
      this.errorMessage = 'No se pudo iniciar sesión. Intenta nuevamente.';
      this.isSubmitting = false;
    }
  }
}
