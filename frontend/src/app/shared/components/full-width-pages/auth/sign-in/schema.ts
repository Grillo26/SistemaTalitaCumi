export interface SignInFormData {
  identificador: string;
  password: string;
  keepLoggedIn?: boolean;
}

export const signInValidationRules = {
  identificador: {
    required: 'Ingresá tu correo o nombre de usuario.',
    maxlength: 'El identificador no puede superar los 150 caracteres.',
  },
  password: {
    required: 'Ingresá tu contraseña.',
  },
};
