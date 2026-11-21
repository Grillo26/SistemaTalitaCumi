export type TipoEspecialidad = 'principal' | 'secundaria';

export interface ProfesionalEspecialidad {
  relacion_id: number;
  id: number;
  nombre: string;
  tipo: TipoEspecialidad;
  activo: boolean;
}

export interface ProfesionalSucursalResumen {
  id: number;
  codigo: string;
  nombre: string;
  activo: boolean;
  relacion_id: number;
  activo_relacion: boolean;
  motivo: string | null;
  version: number;
}

export interface MotivoNoAsignable {
  codigo: string;
  mensaje: string;
}

export interface ProfesionalRowAbilities {
  update_data: boolean;
  update_contact: boolean;
  change_status: boolean;
  delete: boolean;
  manage_branches: boolean;
}

export interface Profesional {
  id: number;
  nombre_completo: string;
  ci: string;
  telefono: string | null;
  telefono_2: string | null;
  email: string | null;
  matricula: string | null;
  activo: boolean;
  estado: 'activo' | 'inactivo';
  motivo_estado: string | null;
  estado_cambiado_en: string | null;
  especialidad_principal_id: number | null;
  especialidades: ProfesionalEspecialidad[];
  sucursales: ProfesionalSucursalResumen[];
  asignable: boolean;
  motivos_no_asignable: MotivoNoAsignable[];
  version: number;
  created_at: string | null;
  updated_at: string | null;
  can: ProfesionalRowAbilities;
}

export interface ProfesionalPageAbilities {
  create: boolean;
  export: boolean;
  view_matrix: boolean;
  manage_branches: boolean;
  secundarias: boolean;
}

export interface ProfesionalPaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface ProfesionalListResponse {
  data: Profesional[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: ProfesionalPaginationMeta;
  can: ProfesionalPageAbilities;
}

export interface ProfesionalResponse {
  data: Profesional;
  can: ProfesionalPageAbilities;
}

export interface Especialidad {
  id: number;
  nombre: string;
  descripcion: string | null;
  activo: boolean;
  estado: 'activo' | 'inactivo';
  motivo_estado: string | null;
  estado_cambiado_en: string | null;
  profesionales_activos_count: number;
  version: number;
  created_at: string | null;
  updated_at: string | null;
  can: EspecialidadRowAbilities;
}

export interface EspecialidadRowAbilities {
  update: boolean;
  change_status: boolean;
  delete: boolean;
}

export interface EspecialidadPageAbilities {
  create: boolean;
  administrar: boolean;
  export: boolean;
}

export interface EspecialidadListResponse {
  data: Especialidad[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: ProfesionalPaginationMeta;
  can: EspecialidadPageAbilities;
}

export interface EspecialidadResponse {
  data: Especialidad;
  can: EspecialidadPageAbilities;
}

export interface ProfesionalOption {
  id: number;
  nombre: string;
}

export interface SucursalOption {
  id: number;
  codigo: string;
  nombre: string;
}

export interface ProfesionalOptions {
  especialidades: ProfesionalOption[];
  sucursales: SucursalOption[];
  puede_secundarias: boolean;
}

export interface ProfesionalOptionsResponse {
  data: ProfesionalOptions;
  can: ProfesionalPageAbilities;
}

export interface ProfesionalSucursalFila {
  id: number;
  codigo: string;
  nombre: string;
  activo: boolean;
  relacion_id: number | null;
  activo_relacion: boolean | null;
  motivo: string | null;
  version: number | null;
}

export interface ProfesionalSucursalesResponse {
  data: ProfesionalSucursalFila[];
  can: {
    administrar: boolean;
  };
}

export interface ProfesionalSucursalResponse {
  data: {
    id: number;
    profesional_id: number;
    sucursal_id: number;
    sucursal: {
      id: number;
      codigo: string;
      nombre: string;
      activo: boolean;
    } | null;
    activo: boolean;
    motivo: string | null;
    version: number;
    created_at: string | null;
    updated_at: string | null;
  };
  can: {
    administrar: boolean;
  };
}


export interface MatrizProfesional {
  id: number;
  nombre_completo: string;
  ci: string;
  activo: boolean;
}

export interface MatrizEspecialidad {
  id: number;
  nombre: string;
  tipo: TipoEspecialidad;
  activo: boolean;
}

export interface MatrizSucursal {
  id: number;
  codigo: string;
  nombre: string;
  activo: boolean;
}

export interface MatrizRow {
  profesional: MatrizProfesional;
  especialidad: MatrizEspecialidad;
  sucursal: MatrizSucursal;
  relacion_sucursal_activa: boolean;
  asignable: boolean;
  motivos_no_asignable: MotivoNoAsignable[];
}

export interface MatrizListResponse {
  data: MatrizRow[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: ProfesionalPaginationMeta;
  can: {
    export: boolean;
    view_professionals: boolean;
  };
}

export interface MatrizProfesionalOption {
  id: number;
  nombre: string;
  ci: string;
  activo: boolean;
}

export interface MatrizEspecialidadOption {
  id: number;
  nombre: string;
  activo: boolean;
}

export interface MatrizSucursalOption {
  id: number;
  codigo: string;
  nombre: string;
  activo: boolean;
}

export interface MatrizOptions {
  profesionales: MatrizProfesionalOption[];
  especialidades: MatrizEspecialidadOption[];
  sucursales: MatrizSucursalOption[];
}

export interface MatrizOptionsResponse {
  data: MatrizOptions;
  can: {
    export: boolean;
  };
}

export interface MatrizListParams {
  search?: string;
  profesional_id?: number | '';
  especialidad_id?: number | '';
  sucursal_id?: number | '';
  estado?: EstadoFiltro;
  solo_asignables?: boolean;
  page?: number;
  per_page?: number;
  sort?: 'profesional_nombre' | 'profesional_ci' | 'especialidad_nombre' | 'sucursal_nombre' | 'profesional_updated_at';
  direction?: 'asc' | 'desc';
}

export type EstadoFiltro = '' | 'activo' | 'inactivo';

export interface ProfesionalListParams {
  search?: string;
  especialidad_id?: number | '';
  sucursal_id?: number | '';
  estado?: EstadoFiltro;
  page?: number;
  per_page?: number;
  sort?: 'nombre_completo' | 'ci' | 'activo' | 'updated_at';
  direction?: 'asc' | 'desc';
}

export interface EspecialidadListParams {
  search?: string;
  estado?: EstadoFiltro;
  page?: number;
  per_page?: number;
  sort?: 'nombre' | 'activo' | 'updated_at';
  direction?: 'asc' | 'desc';
}

export interface ProfesionalCreatePayload {
  nombre_completo: string;
  ci: string;
  telefono: string | null;
  telefono_2: string | null;
  email: string | null;
  matricula: string | null;
  activo: boolean;
  especialidad_principal_id: number;
  especialidades_secundarias: number[];
}

export interface ProfesionalUpdatePayload {
  nombre_completo?: string;
  ci?: string;
  telefono?: string | null;
  telefono_2?: string | null;
  email?: string | null;
  matricula?: string | null;
  especialidad_principal_id?: number;
  especialidades_secundarias?: number[];
  version: number;
}

export interface EspecialidadCreatePayload {
  nombre: string;
  descripcion: string | null;
  activo: boolean;
}

export interface EspecialidadUpdatePayload {
  nombre?: string;
  descripcion?: string | null;
  version: number;
}

export interface StatusPayload {
  motivo: string;
  version: number;
}

export interface ProfesionalesApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  profesionales_activos_count?: number;
}

export const PROFESIONALES_TEXTS = {
  loadError: 'No se pudo cargar la información de profesionales.',
  saveError: 'No se pudo guardar el profesional.',
  saved: 'Profesional guardado correctamente.',
  specialtySaved: 'Especialidad guardada correctamente.',
  statusSaved: 'Estado actualizado correctamente.',
  branchSaved: 'Sucursales actualizadas correctamente.',
  networkError: 'No se pudo conectar con el servidor. Reintentá la operación.',
  versionConflict: 'El registro fue modificado por otro usuario. Recargá los datos antes de guardar.',
  documentDuplicated: 'Ya existe un profesional con ese documento.',
  specialtyInUse: 'No se puede desactivar una especialidad con profesionales activos asociados.',
  noBranches: 'Sin sucursal',
  notAssignable: 'No asignable',
  assignable: 'Asignable',
} as const;

export const ESTADO_OPTIONS = [
  { value: 'activo', label: 'Activo' },
  { value: 'inactivo', label: 'Inactivo' },
];

export const SECTION_OPTIONS: { value: ProfesionalesSection; label: string }[] = [
  { value: 'directorio', label: 'Directorio' },
  { value: 'especialidades', label: 'Especialidades' },
  { value: 'matriz', label: 'Matriz' },
];

export type ProfesionalesSection = 'directorio' | 'especialidades' | 'matriz';
export type FormMode = 'create' | 'edit';
export type StatusMode = 'activate' | 'deactivate';

export const EMPTY_PROFESIONAL_ABILITIES: ProfesionalPageAbilities = {
  create: false,
  export: false,
  view_matrix: false,
  manage_branches: false,
  secundarias: false,
};

export const EMPTY_ESPECIALIDAD_ABILITIES: EspecialidadPageAbilities = {
  create: false,
  administrar: false,
  export: false,
};
