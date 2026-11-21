export interface AuditEntry {
  id: number;
  usuario_id: number | null;
  usuario: string | null;
  usuario_email: string | null;
  sucursal_id: number | null;
  sucursal: string | null;
  sucursal_nombre: string | null;
  modulo: string;
  modulo_label: string;
  entidad: string | null;
  entidad_label: string;
  entidad_id: number | null;
  accion: string;
  accion_label: string;
  datos_anteriores: Record<string, unknown> | null;
  datos_nuevos: Record<string, unknown> | null;
  motivo: string | null;
  ip: string | null;
  resultado: string | null;
  resultado_label: string;
  critico: boolean;
  created_at: string | null;
}

export interface AuditFilterUser {
  id: number;
  nombre: string;
  email: string;
}

export interface AuditFilterOption {
  clave: string;
  label: string;
}

export interface AuditFilterAction extends AuditFilterOption {
  modulo: string | null;
  entidad: string | null;
}

export interface AuditFilterOptions {
  usuarios: AuditFilterUser[];
  modulos: AuditFilterOption[];
  entidades: AuditFilterOption[];
  acciones: AuditFilterAction[];
  resultados: AuditFilterOption[];
  retencion_anios: number;
}

export interface AuditPaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface AuditListResponse {
  data: AuditEntry[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: AuditPaginationMeta;
  can: { export: boolean };
}

export interface AuditEntryResponse {
  data: AuditEntry;
  can: { export: boolean };
}

export interface AuditOptionsResponse {
  data: AuditFilterOptions;
  can: { export: boolean };
}

export interface AuditReconstruction {
  entidad: string;
  entidad_id: number;
  fecha: string;
  encontrado: boolean;
  estado: Record<string, unknown> | null;
  anterior: Record<string, unknown> | null;
  accion: string | null;
  registrado_en: string | null;
  usuario: string | null;
  sucursal: string | null;
}

export interface AuditReconstructionResponse {
  data: AuditReconstruction;
}

export type CriticalFilter = '' | '1' | '0';

export interface AuditListParams {
  fecha_desde?: string;
  fecha_hasta?: string;
  usuario_id?: number | null;
  modulo?: string;
  entidad?: string;
  entidad_id?: number | null;
  accion?: string;
  resultado?: string;
  critico?: boolean;
  sucursal_id?: number | null;
  page?: number;
  per_page?: number;
}

export interface ApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
}

export const BITACORA_TEXTS = {
  loadError: 'No se pudo cargar la bitácora.',
  detailError: 'No se pudo cargar el detalle del evento.',
  optionsError: 'No se pudieron cargar las opciones de filtros.',
  reconstructError: 'No se pudo reconstruir el estado.',
  reconstructNotFound: 'No hay eventos para esa entidad en la fecha indicada.',
  retention: 'Retención configurada sin borrado manual.',
  exportPending: 'La exportación de bitácora está pendiente de infraestructura; no se muestra botón.',
  networkError: 'No se pudo conectar con el servidor. Reintentá la operación.',
} as const;
