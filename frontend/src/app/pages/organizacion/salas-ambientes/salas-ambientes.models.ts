export type UsoAmbiente =
  | 'terapia_individual'
  | 'entrevista'
  | 'diagnostico'
  | 'mixto';

export interface SucursalSummary {
  id: number;
  nombre: string;
}

export interface TipoSala {
  id: number;
  nombre: string;
  activo: boolean;
  estado: 'activo' | 'inactivo';
  version: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface TurnoCatalogo {
  id: number;
  nombre: string;
  orden: number;
  activo: boolean;
  estado: 'activo' | 'inactivo';
  version: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface Area {
  id: number;
  nombre: string;
  activo: boolean;
}

export interface Sala {
  id: number;
  sucursal: SucursalSummary | null;
  area: { id: number; nombre: string } | null;
  tipo_sala: { id: number; nombre: string } | null;
  turno: { id: number; nombre: string } | null;
  nombre: string;
  observaciones: string | null;
  activo: boolean;
  estado: 'activa' | 'inactiva';
  version: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface Ambiente {
  id: number;
  sucursal: SucursalSummary | null;
  nombre: string;
  uso: UsoAmbiente;
  uso_label: string;
  observaciones: string | null;
  activo: boolean;
  estado: 'activa' | 'inactiva';
  version: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface EntityAbilities {
  create: boolean;
  update: boolean;
  change_status: boolean;
}

export interface SpaceAbilities {
  create: boolean;
  update_structure: boolean;
  update_data: boolean;
  change_status: boolean;
  delete: boolean;
}

export interface SalasAmbientesAbilities {
  global_scope: boolean;
  catalogos: {
    view: boolean;
    tipos_sala: EntityAbilities;
    turnos: EntityAbilities;
  };
  salas: SpaceAbilities;
  ambientes: SpaceAbilities;
  export: boolean;
}

export const EMPTY_SALAS_AMBIENTES_ABILITIES: SalasAmbientesAbilities = {
  global_scope: false,
  catalogos: {
    view: false,
    tipos_sala: { create: false, update: false, change_status: false },
    turnos: { create: false, update: false, change_status: false },
  },
  salas: {
    create: false,
    update_structure: false,
    update_data: false,
    change_status: false,
    delete: false,
  },
  ambientes: {
    create: false,
    update_structure: false,
    update_data: false,
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
  can: SalasAmbientesAbilities;
}

export interface SimpleListResponse<T> {
  data: T[];
  can: SalasAmbientesAbilities;
}

export interface DetailResponse<T> {
  data: T;
  can: SalasAmbientesAbilities;
}

export interface SalaConflict {
  id: number;
  nombre: string;
  tipo_sala: string;
  turno: string;
  sucursal: string;
}

export interface AmbienteConflict {
  id: number;
  nombre: string;
  sucursal: string;
}

export interface SalasAmbientesApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  conflict?: SalaConflict | AmbienteConflict;
}

export type StatusFilter = '' | 'activa' | 'inactiva';
export type CatalogStatusFilter = '' | 'activo' | 'inactivo';
export type SortDirection = 'asc' | 'desc';
export type SalasSortField = 'nombre' | 'activo' | 'updated_at';
export type AmbientesSortField = 'nombre' | 'uso' | 'activo' | 'updated_at';
export type SalasAmbientesSection = 'salas' | 'ambientes' | 'tipos-sala' | 'turnos';
export type FormMode = 'create' | 'edit';
export type StatusMode = 'activate' | 'deactivate';
export type StatusEntityType = 'sala' | 'ambiente' | 'tipo-sala' | 'turno';

export interface CatalogoListParams {
  search?: string;
  estado?: CatalogStatusFilter;
}

export interface SalaListParams {
  search?: string;
  sucursal_id?: number;
  tipo_sala_id?: number;
  turno_catalogo_id?: number;
  estado?: StatusFilter;
  page?: number;
  per_page?: number;
  sort?: SalasSortField;
  direction?: SortDirection;
}

export interface AmbienteListParams {
  search?: string;
  sucursal_id?: number;
  uso?: UsoAmbiente | '';
  estado?: StatusFilter;
  page?: number;
  per_page?: number;
  sort?: AmbientesSortField;
  direction?: SortDirection;
}

export interface TipoSalaPayload {
  nombre: string;
  activo?: boolean;
  version?: number;
}

export interface TurnoCatalogoPayload {
  nombre: string;
  orden: number;
  activo?: boolean;
  version?: number;
}

export interface SalaCreatePayload {
  sucursal_id: number;
  area_id: number;
  tipo_sala_id: number;
  turno_catalogo_id: number;
  nombre: string;
  observaciones: string | null;
  activo: boolean;
}

export interface SalaUpdatePayload {
  sucursal_id?: number;
  area_id?: number;
  tipo_sala_id?: number;
  turno_catalogo_id?: number;
  nombre?: string;
  observaciones?: string | null;
  version: number;
}

export interface AmbienteCreatePayload {
  sucursal_id: number;
  nombre: string;
  uso: UsoAmbiente;
  observaciones: string | null;
  activo: boolean;
}

export interface AmbienteUpdatePayload {
  sucursal_id?: number;
  nombre?: string;
  uso?: UsoAmbiente;
  observaciones?: string | null;
  version: number;
}

export interface StatusPayload {
  motivo?: string;
  version: number;
}

export const USO_OPTIONS: { value: UsoAmbiente; label: string }[] = [
  { value: 'terapia_individual', label: 'Terapia individual' },
  { value: 'entrevista', label: 'Entrevista' },
  { value: 'diagnostico', label: 'Diagnóstico' },
  { value: 'mixto', label: 'Mixto' },
];

export const ESTADO_ESPACIO_OPTIONS = [
  { value: '', label: 'Todos los estados' },
  { value: 'activa', label: 'Activas' },
  { value: 'inactiva', label: 'Inactivas' },
];

export const ESTADO_CATALOGO_OPTIONS = [
  { value: '', label: 'Todos los estados' },
  { value: 'activo', label: 'Activos' },
  { value: 'inactivo', label: 'Inactivos' },
];

export const SECTION_OPTIONS: { value: SalasAmbientesSection; label: string }[] = [
  { value: 'salas', label: 'Salas grupales' },
  { value: 'ambientes', label: 'Ambientes' },
  { value: 'tipos-sala', label: 'Tipos de sala' },
  { value: 'turnos', label: 'Turnos' },
];
