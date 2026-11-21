export type ParameterType = 'entero' | 'decimal' | 'hora' | 'texto' | 'booleano';

export type ParameterCategory =
  | 'identidad'
  | 'operacion'
  | 'seguridad'
  | 'agenda'
  | 'pagos'
  | 'formato_documentos'
  | 'zona_horaria'
  | 'moneda';

export interface Parameter {
  id: number;
  clave: string;
  categoria: ParameterCategory;
  categoria_label: string;
  descripcion: string | null;
  tipo: ParameterType;
  tipo_label: string;
  valor: string | number | boolean;
  valor_texto: string;
  valor_defecto: string | number | boolean;
  valor_defecto_texto: string;
  unidad: string | null;
  minimo: string | null;
  maximo: string | null;
  sensible: boolean;
  editable: boolean;
  visible_recepcion: boolean;
  version: number;
  logo_url: string | null;
  updated_at: string | null;
}

export interface ConfiguracionAbilities {
  view: boolean;
  view_all: boolean;
  view_operatives: boolean;
  update: boolean;
  update_logo: boolean;
  restore: boolean;
  export: boolean;
}

export const EMPTY_CONFIGURACION_ABILITIES: ConfiguracionAbilities = {
  view: false,
  view_all: false,
  view_operatives: false,
  update: false,
  update_logo: false,
  restore: false,
  export: false,
};

export interface ParameterListResponse {
  data: Parameter[];
  can: ConfiguracionAbilities;
}

export interface ParameterDetailResponse {
  data: Parameter;
  can: ConfiguracionAbilities;
}

export interface ParameterHistoryUser {
  id: number;
  nombre: string;
}

export interface ParameterHistoryEntry {
  id: number;
  accion: 'CREAR' | 'EDITAR' | 'RESTAURAR' | string;
  motivo: string | null;
  usuario: ParameterHistoryUser | null;
  datos_anteriores: Record<string, unknown> | null;
  datos_nuevos: Record<string, unknown> | null;
  created_at: string | null;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface ParameterHistoryResponse {
  data: ParameterHistoryEntry[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: PaginationMeta;
  can: ConfiguracionAbilities;
}

export interface ParameterListParams {
  search?: string;
  categoria?: ParameterCategory | '';
}

export interface ParameterUpdatePayload {
  valor: string | number | boolean;
  version: number;
  confirmado?: boolean;
  motivo?: string | null;
}

export interface ParameterRestorePayload {
  version: number;
  confirmado?: boolean;
  motivo?: string | null;
}

export interface ParameterApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
}

export interface ParameterGroup {
  categoria: ParameterCategory;
  label: string;
  items: Parameter[];
}

export const CATEGORY_OPTIONS: { value: ParameterCategory | ''; label: string }[] = [
  { value: '', label: 'Todas las categorías' },
  { value: 'identidad', label: 'Identidad del centro' },
  { value: 'operacion', label: 'Operación' },
  { value: 'seguridad', label: 'Seguridad' },
  { value: 'agenda', label: 'Agenda' },
  { value: 'pagos', label: 'Pagos' },
  { value: 'formato_documentos', label: 'Formato de documentos' },
  { value: 'zona_horaria', label: 'Zona horaria' },
  { value: 'moneda', label: 'Moneda' },
];

export const TYPE_HINTS: Record<ParameterType, string> = {
  entero: 'Número entero',
  decimal: 'Número decimal (punto como separador)',
  hora: 'Hora en formato HH:MM',
  texto: 'Texto',
  booleano: 'Verdadero o falso',
};
