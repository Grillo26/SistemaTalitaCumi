export type EstadoCatalogo = 'activo' | 'inactivo';
export type SortDirection = 'asc' | 'desc';
export type CatalogoServiciosSection = 'servicios' | 'areas' | 'tipos';
export type FormMode = 'create' | 'edit';
export type StatusMode = 'activate' | 'deactivate';
export type StatusEntityType = 'area' | 'servicio' | 'tipo-servicio';

export interface Area {
  id: number;
  nombre: string;
  descripcion: string | null;
  color: string;
  activo: boolean;
  estado: EstadoCatalogo;
  servicios_activos_count: number | null;
  servicios_total_count: number | null;
  version: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface TipoServicio {
  id: number;
  nombre: string;
  codigo?: string | null;
  activo: boolean;
  estado: EstadoCatalogo;
  servicios_total_count: number | null;
  version: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface AreaSummary {
  id: number;
  nombre: string;
  color: string;
  activo: boolean;
}

export interface TipoServicioSummary {
  id: number;
  nombre: string;
  codigo?: string | null;
  activo: boolean;
}

export interface Servicio {
  id: number;
  area: AreaSummary | null;
  tipo_servicio: TipoServicioSummary | null;
  nombre: string;
  descripcion: string | null;
  duracion_minutos: number;
  activo: boolean;
  estado: EstadoCatalogo;
  version: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface EntityAbilities {
  view: boolean;
  create: boolean;
  update: boolean;
  change_status: boolean;
  delete: boolean;
}

export interface CatalogoServiciosAbilities {
  areas: EntityAbilities;
  servicios: EntityAbilities;
  tipos_servicio: EntityAbilities;
  export: boolean;
}

export const EMPTY_CATALOGO_SERVICIOS_ABILITIES: CatalogoServiciosAbilities = {
  areas: { view: false, create: false, update: false, change_status: false, delete: false },
  servicios: { view: false, create: false, update: false, change_status: false, delete: false },
  tipos_servicio: {
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
  can: CatalogoServiciosAbilities;
}

export interface SimpleListResponse<T> {
  data: T[];
  can: CatalogoServiciosAbilities;
}

export interface DetailResponse<T> {
  data: T;
  can: CatalogoServiciosAbilities;
}

export interface CatalogoServiciosApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  active_services_count?: number;
}

export interface AreaListParams {
  search?: string;
  estado?: EstadoCatalogo | '';
  sort?: 'nombre' | 'activo' | 'updated_at';
  direction?: SortDirection;
}

export interface ServicioListParams {
  search?: string;
  area_id?: number;
  tipo_servicio_id?: number;
  estado?: EstadoCatalogo | '';
  page?: number;
  per_page?: number;
  sort?: 'nombre' | 'area' | 'tipo' | 'duracion_minutos' | 'activo' | 'updated_at';
  direction?: SortDirection;
}

export interface TipoServicioListParams {
  search?: string;
  estado?: EstadoCatalogo | '';
  sort?: 'nombre' | 'activo' | 'updated_at';
  direction?: SortDirection;
}

export interface AreaPayload {
  nombre: string;
  descripcion: string | null;
  color: string;
  activo?: boolean;
  version?: number;
}

export interface ServicioPayload {
  area_id: number;
  tipo_servicio_id: number;
  nombre: string;
  descripcion: string | null;
  duracion_minutos: number;
  activo?: boolean;
  version?: number;
}

export interface TipoServicioPayload {
  nombre: string;
  activo?: boolean;
  version?: number;
}

export interface StatusPayload {
  motivo: string;
  version: number;
}

export const ESTADO_OPTIONS = [
  { value: '', label: 'Todos los estados' },
  { value: 'activo', label: 'Activos' },
  { value: 'inactivo', label: 'Inactivos' },
];

export const ESTADO_FORM_OPTIONS = [
  { value: 'activo', label: 'Activo' },
  { value: 'inactivo', label: 'Inactivo' },
];

export const SECTION_OPTIONS: { value: CatalogoServiciosSection; label: string }[] = [
  { value: 'servicios', label: 'Servicios' },
  { value: 'areas', label: 'Áreas' },
  { value: 'tipos', label: 'Tipos de servicio' },
];

export const COLOR_PRESETS = [
  { label: 'Marca', value: '#671C61' },
  { label: 'Secundario', value: '#D27EBA' },
  { label: 'Acento', value: '#F9733F' },
  { label: 'Resaltado', value: '#FFBD5D' },
];

export const DEFAULT_AREA_COLOR = '#6B7280';

export const CATALOGO_TEXTS = {
  title: 'Catálogo de servicios',
  subtitle:
    'Administrá las áreas, los servicios y sus tipos. Los colores de área se usan en la agenda.',
  areasTab: 'Áreas',
  serviciosTab: 'Servicios',
  tiposTab: 'Tipos de servicio',
  emptyAreas: 'Todavía no hay áreas registradas.',
  emptyServicios: 'No se encontraron servicios con los filtros actuales.',
  emptyTipos: 'Todavía no hay tipos de servicio registrados.',
  areaDeleteNote: 'Las áreas no se eliminan: se desactivan para conservar el historial.',
  servicioDeleteNote:
    'Los servicios no se eliminan: se desactivan para conservar tarifas, inscripciones y turnos.',
};
