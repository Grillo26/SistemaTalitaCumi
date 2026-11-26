export type EstadoCatalogo = 'activo' | 'inactivo';
export type EstadoVigencia = 'vigente' | 'futura' | 'cerrada';
export type SortDirection = 'asc' | 'desc';
export type TarifasSection = 'tarifas' | 'categorias';
export type FormMode = 'create' | 'edit';

export interface CategoriaTarifa {
  id: number;
  nombre: string;
  descripcion: string | null;
  activo: boolean;
  estado: EstadoCatalogo;
  tarifas_vigentes_count: number | null;
  tarifas_total_count: number | null;
  version: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface Tarifa {
  id: number;
  servicio: {
    id: number;
    nombre: string;
    activo: boolean;
    area: { id: number; nombre: string; color: string } | null;
  } | null;
  categoria: { id: number; nombre: string; activo: boolean } | null;
  sucursal: { id: number; nombre: string } | null;
  monto: string;
  vigente_desde: string;
  vigente_hasta: string | null;
  activo: boolean;
  estado_vigencia: EstadoVigencia;
  version: number;
  created_at: string | null;
  updated_at: string | null;
  can: {
    update: boolean;
    close: boolean;
    delete: boolean;
  };
}

export interface TarifaAplicable {
  tarifa_id: number;
  servicio_id: number;
  servicio_nombre: string | null;
  categoria_tarifa_id: number;
  categoria_nombre: string | null;
  sucursal_id: number | null;
  sucursal_nombre: string | null;
  monto: string;
  vigente_desde: string;
  vigente_hasta: string | null;
  origen: 'sucursal' | 'global';
}

export interface TarifaConflict {
  id: number | null;
  vigente_desde: string;
  vigente_hasta: string | null;
  monto: string | null;
  sucursal: string | null;
  categoria: string | null;
}

export interface TarifasAbilities {
  tarifas: {
    view: boolean;
    create: boolean;
    update: boolean;
    close: boolean;
    delete: boolean;
    global_scope: boolean;
  };
  categorias: {
    view: boolean;
    create: boolean;
    update: boolean;
    change_status: boolean;
    delete: boolean;
  };
  export: boolean;
}

export const EMPTY_TARIFAS_ABILITIES: TarifasAbilities = {
  tarifas: {
    view: false,
    create: false,
    update: false,
    close: false,
    delete: false,
    global_scope: false,
  },
  categorias: {
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
  can: TarifasAbilities;
}

export interface SimpleListResponse<T> {
  data: T[];
  can: TarifasAbilities;
}

export interface DetailResponse<T> {
  data: T;
  can: TarifasAbilities;
}

export interface AplicableResponse {
  data: TarifaAplicable | null;
  can: TarifasAbilities;
}

export interface TarifasApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  conflict?: TarifaConflict;
  active_tariffs_count?: number;
}

export interface CategoriaListParams {
  search?: string;
  estado?: EstadoCatalogo | '';
  sort?: 'nombre' | 'activo' | 'updated_at';
  direction?: SortDirection;
}

export interface TarifaListParams {
  search?: string;
  servicio_id?: number;
  categoria_tarifa_id?: number;
  sucursal_id?: number;
  solo_global?: boolean;
  estado_vigencia?: EstadoVigencia | '';
  page?: number;
  per_page?: number;
  sort?: 'servicio' | 'categoria' | 'sucursal' | 'monto' | 'vigente_desde' | 'vigente_hasta' | 'updated_at';
  direction?: SortDirection;
}

export interface CategoriaPayload {
  nombre: string;
  descripcion: string | null;
  activo?: boolean;
  version?: number;
}

export interface TarifaPayload {
  servicio_id: number;
  categoria_tarifa_id: number;
  sucursal_id: number | null;
  monto: string;
  vigente_desde: string;
  vigente_hasta?: string | null;
  version?: number;
}

export interface CloseVigenciaPayload {
  vigente_hasta: string;
  motivo: string;
  version: number;
}

export interface StatusCategoriaPayload {
  motivo?: string;
  version: number;
}

export interface AplicableParams {
  servicio_id: number;
  sucursal_id: number;
  categoria_tarifa_id: number;
  fecha: string;
}

export const SECTION_OPTIONS: { value: TarifasSection; label: string }[] = [
  { value: 'tarifas', label: 'Tarifas' },
  { value: 'categorias', label: 'Categorías' },
];

export const ESTADO_CATEGORIA_OPTIONS = [
  { value: '', label: 'Todos los estados' },
  { value: 'activo', label: 'Activas' },
  { value: 'inactivo', label: 'Inactivas' },
];

export const ESTADO_VIGENCIA_OPTIONS = [
  { value: '', label: 'Todas las vigencias' },
  { value: 'vigente', label: 'Vigentes' },
  { value: 'futura', label: 'Futuras' },
  { value: 'cerrada', label: 'Cerradas' },
];

export const TARIFAS_TEXTS = {
  title: 'Tarifas y categorías',
  subtitle:
    'Administrá precios por servicio, categoría y sucursal, con vigencia controlada y trazabilidad.',
  tarifasTab: 'Tarifas',
  categoriasTab: 'Categorías',
  emptyTarifas: 'No se encontraron tarifas con los filtros actuales.',
  emptyCategorias: 'Todavía no hay categorías de tarifa registradas.',
  noAplicable: 'No hay tarifa aplicable para esa combinación y fecha.',
  global: 'Global',
};

export function formatBs(value: string | null | undefined): string {
  return `Bs ${value ?? '0.00'}`;
}
