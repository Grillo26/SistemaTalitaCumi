export interface PermissionAction {
  id: number;
  codigo: string;
  accion: string;
  label: string;
  critico: boolean;
}

export interface PermissionModule {
  clave: string;
  label: string;
  acciones: PermissionAction[];
}

export interface RoleMatrixAbilities {
  edit: boolean;
}

export interface RoleMatrix {
  id: number;
  nombre: string;
  label: string;
  version: number;
  permisos: number[];
  total_permisos: number;
  es_superadmin: boolean;
  can: RoleMatrixAbilities;
}

export interface RoleMatrixAbilitiesResponse {
  edit: boolean;
  export: boolean;
}

export interface RoleMatrixListResponse {
  data: {
    roles: RoleMatrix[];
    modulos: PermissionModule[];
    total_permisos: number;
  };
  can: RoleMatrixAbilitiesResponse;
}

export interface RoleMatrixResponse {
  data: {
    role: RoleMatrix;
    modulos: PermissionModule[];
  };
  can: RoleMatrixAbilitiesResponse;
}

export interface RoleMatrixUpdatePayload {
  permisos: number[];
  version: number;
}

export interface RoleMatrixHistory {
  role_id: number;
  fecha: string;
  encontrado: boolean;
  permisos: string[];
  version: number | null;
  version_actual: number;
  registrado_en: string | null;
}

export interface RoleMatrixHistoryResponse {
  data: RoleMatrixHistory;
}

export interface ApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
}

export const ROLE_MATRIX_TEXTS = {
  loadError: 'No se pudo cargar la matriz de permisos.',
  saveError: 'No se pudo guardar la matriz.',
  saveSuccess: 'Matriz actualizada correctamente.',
  unchanged: 'No hay cambios para guardar.',
  concurrency:
    'La matriz fue modificada por otro usuario. Recargá los datos antes de guardar.',
  criticalProtected:
    'Los permisos críticos del rol Superadmin están protegidos para no dejar el sistema sin administración.',
  readOnly: 'Solo lectura: tu rol puede consultar la matriz pero no editarla.',
  appliesNext: 'Los cambios de permisos aplican en la siguiente operación de cada usuario (RN-22).',
  historyEmpty: 'No hay registros de la matriz anteriores a esa fecha.',
  networkError: 'No se pudo conectar con el servidor. Reintentá la operación.',
} as const;
