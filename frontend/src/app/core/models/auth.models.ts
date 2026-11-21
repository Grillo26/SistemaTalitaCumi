export interface SessionBranch {
  id: number;
  codigo: string;
  nombre: string;
}

export interface PasswordPolicy {
  min_length: number;
  require_uppercase: boolean;
  require_lowercase: boolean;
  require_number: boolean;
  require_symbol: boolean;
  history_count: number;
  expiry_days: number;
}

export interface SessionUser {
  id: number;
  name: string;
  email: string | null;
  username: string | null;
  documento: string | null;
  role: string | null;
  permissions: string[];
  branches: SessionBranch[];
  globalScope: boolean;
  requiresBranchAssignment: boolean;
  mustChangePassword: boolean;
  lastAccess: string | null;
  passwordPolicy: PasswordPolicy | null;
}

export interface ApiUser {
  id: number;
  nombre_completo: string;
  email: string | null;
  username: string | null;
  documento: string | null;
  rol: string | null;
  permisos: string[];
  sucursales: SessionBranch[];
  alcance_global: boolean;
  requires_branch_assignment: boolean;
  debe_cambiar_password: boolean;
  ultimo_acceso: string | null;
  password_expires_at: string | null;
  password_policy: PasswordPolicy;
}

export interface LoginResponse {
  data: {
    token: string;
    token_type: string;
    expires_at: string;
    user: ApiUser;
  };
}

export interface MeResponse {
  data: ApiUser;
}

export interface ApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
}

export const AUTH_TEXTS = {
  genericLoginError: 'No se pudo iniciar sesión. Intentá nuevamente.',
  invalidCredentials: 'Usuario o contraseña incorrectos.',
  tooManyAttempts: 'Demasiados intentos. Esperá unos minutos e intentá de nuevo.',
  networkError: 'No se pudo conectar con el servidor. Reintentá la operación.',
  passwordChanged: 'Contraseña actualizada correctamente.',
  sessionExpired: 'Tu sesión expiró. Iniciá sesión nuevamente.',
} as const;
