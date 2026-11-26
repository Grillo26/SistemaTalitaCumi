export type EstadoCatalogo = 'activo' | 'inactivo';
export type SortDirection = 'asc' | 'desc';
export type FormMode = 'create' | 'edit';

export interface TipoDiagnostico {
  id: number;
  servicio: {
    id: number;
    nombre: string;
    codigo: string | null;
    activo: boolean;
    area: { id: number; nombre: string; color: string } | null;
  } | null;
  nombre: string;
  descripcion: string | null;
  dias_estimados: number;
  activo: boolean;
  estado: EstadoCatalogo;
  especialidades: {
    especialidad_id: number;
    nombre: string;
    cantidad_minima: number;
    activo: boolean;
  }[];
  especialidades_count: number;
  version: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface TiposDiagnosticoAbilities {
  tipos_diagnostico: {
    view: boolean;
    create: boolean;
    update: boolean;
    change_status: boolean;
    delete: boolean;
  };
  export: boolean;
}

export const EMPTY_TIPOS_DIAGNOSTICO_ABILITIES: TiposDiagnosticoAbilities = {
  tipos_diagnostico: {
    view: false,
    create: false,
    update: false,
    change_status: false,
    delete: false,
  },
  export: false,
};

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: PaginationMeta;
  can: TiposDiagnosticoAbilities;
}

export interface DetailResponse<T> {
  data: T;
  can: TiposDiagnosticoAbilities;
}

export interface TiposDiagnosticoApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
}

export interface TipoDiagnosticoListParams {
  search?: string;
  estado?: EstadoCatalogo | '';
  especialidad_id?: number;
  page?: number;
  per_page?: number;
  sort?: 'nombre' | 'activo' | 'dias_estimados' | 'updated_at';
  direction?: SortDirection;
}

export interface EspecialidadRequeridaPayload {
  especialidad_id: number;
  cantidad_minima: number;
}

export interface TipoDiagnosticoPayload {
  servicio_id: number;
  nombre: string;
  descripcion: string | null;
  dias_estimados: number;
  activo?: boolean;
  version?: number;
  especialidades: EspecialidadRequeridaPayload[];
}

export interface StatusTipoDiagnosticoPayload {
  motivo: string;
  version: number;
}

export const ESTADO_OPTIONS = [
  { value: '', label: 'Todos los estados' },
  { value: 'activo', label: 'Activos' },
  { value: 'inactivo', label: 'Inactivos' },
];

export const TIPOS_DIAGNOSTICO_TEXTS = {
  title: 'Tipos de diagnóstico',
  subtitle:
    'Configurá los tipos de evaluación, los días estimados y las especialidades requeridas.',
  empty: 'No se encontraron tipos de diagnóstico con los filtros actuales.',
  noEspecialidades: 'Agregá al menos una especialidad requerida.',
  duplicateEspecialidad: 'No repitas la misma especialidad.',
  especialidadesUnavailable:
    'No se pudo cargar el catálogo de especialidades. Verificá el módulo de profesionales.',
  serviciosUnavailable:
    'No se pudo cargar el catálogo de servicios diagnóstico. Verificá el módulo de catálogo.',
  noEspecialidadesSelected: 'Sin especialidades configuradas.',
};
