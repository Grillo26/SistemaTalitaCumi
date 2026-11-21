export interface UserRole {
  id: number;
  nombre: string;
  label: string;
}

export interface UserBranch {
  id: number;
  codigo: string;
  nombre: string;
}

export interface UserAbilities {
  view: boolean;
  update: boolean;
  change_status: boolean;
  reset_password: boolean;
  unlock: boolean;
  close_sessions: boolean;
  delete: boolean;
}

export interface User {
  id: number;
  nombre_completo: string;
  username: string | null;
  documento: string | null;
  email: string | null;
  activo: boolean;
  estado: 'activo' | 'inactivo';
  bloqueado: boolean;
  rol: UserRole | null;
  sucursales: UserBranch[];
  version: number;
  ultimo_acceso: string | null;
  intentos_fallidos: number;
  bloqueado_hasta: string | null;
  motivo_estado: string | null;
  estado_cambiado_en: string | null;
  debe_cambiar_password: boolean;
  sesiones_activas: number;
  created_at: string | null;
  updated_at: string | null;
  can: UserAbilities;
}

export interface UserPaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface UserListAbilities {
  create: boolean;
  export: boolean;
}

export interface UserListResponse {
  data: User[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: UserPaginationMeta;
  can: UserListAbilities;
}

export interface UserResponse {
  data: User;
}

export interface UserCreateResponse extends UserResponse {
  password_temporal: string;
  message: string;
}

export interface UserResetResponse extends UserResponse {
  password_temporal: string;
  sessions_invalidated: number;
  message: string;
}

export interface UserCloseSessionsResponse extends UserResponse {
  sesiones_cerradas: number;
}

export interface UserOptionsResponse {
  data: {
    roles: UserRole[];
    sucursales: UserBranch[];
  };
}

export interface UserHistoryEntry {
  id: number;
  accion: string;
  entidad: string | null;
  entidad_id: number | null;
  usuario_id: number | null;
  usuario: string | null;
  datos_anteriores: Record<string, unknown> | null;
  datos_nuevos: Record<string, unknown> | null;
  motivo: string | null;
  ip: string | null;
  modulo: string | null;
  resultado: string | null;
  critico: boolean;
  created_at: string | null;
}

export interface UserHistoryResponse {
  data: UserHistoryEntry[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: UserPaginationMeta;
}

export type UserStatusFilter = '' | 'activo' | 'inactivo' | 'bloqueado';

export type UserSortField =
  | 'nombre'
  | 'email'
  | 'activo'
  | 'ultimo_acceso'
  | 'created_at';

export type UserSortDirection = 'asc' | 'desc';

export interface UserListParams {
  search?: string;
  rol_id?: number | null;
  sucursal_id?: number | null;
  estado?: UserStatusFilter;
  page?: number;
  per_page?: number;
  sort?: UserSortField;
  direction?: UserSortDirection;
}

export interface UserCreatePayload {
  name: string;
  documento: string;
  email: string | null;
  username: string;
  rol_id: number;
  sucursales: number[];
}

export interface UserUpdatePayload {
  name?: string;
  documento?: string;
  email?: string | null;
  username?: string;
  rol_id?: number;
  sucursales?: number[];
  version: number;
}

export interface UserStatusPayload {
  motivo?: string | null;
  version: number;
}

export interface UserUnlockPayload {
  motivo: string;
  version: number;
}

export interface UserCloseSessionsPayload {
  motivo: string;
}

export interface ApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
}

export const EMPTY_USER_ABILITIES: UserAbilities = {
  view: false,
  update: false,
  change_status: false,
  reset_password: false,
  unlock: false,
  close_sessions: false,
  delete: false,
};

export const EMPTY_USER_LIST_ABILITIES: UserListAbilities = {
  create: false,
  export: false,
};

export const USER_TEXTS = {
  loadError: 'No se pudo cargar la lista de usuarios.',
  saveError: 'No se pudo guardar el usuario.',
  statusError: 'No se pudo cambiar el estado del usuario.',
  resetError: 'No se pudo resetear la contraseña.',
  unlockError: 'No se pudo desbloquear la cuenta.',
  closeSessionsError: 'No se pudieron cerrar las sesiones.',
  historyError: 'No se pudo cargar el historial.',
  networkError: 'No se pudo conectar con el servidor. Reintentá la operación.',
  createSuccess: 'Usuario creado correctamente.',
  updateSuccess: 'Usuario actualizado correctamente.',
  deleteNotAllowed: 'Los usuarios no se eliminan. Usá la acción Desactivar.',
  tempPasswordWarning:
    'Esta contraseña se muestra una sola vez. Copiala y entregala al usuario por un canal seguro.',
  concurrency: 'El usuario fue modificado por otro usuario. Recargá los datos antes de guardar.',
} as const;
