export type PacienteEstado = 'activo' | 'en_pausa' | 'retirado';
export type PacienteSexo = 'M' | 'F';
export type PacienteSort = 'nombre_completo' | 'codigo' | 'fecha_nacimiento' | 'estado' | 'updated_at';

export interface EdadPaciente {
  anios: number;
  meses: number;
  dias: number;
  total_meses: number;
  etiqueta: string;
}

export interface SucursalResumen {
  id: number;
  codigo?: string;
  nombre?: string;
  activo?: boolean;
}

export interface TutorAbilities {
  edit_data: boolean;
  edit_contact: boolean;
  change_status: boolean;
  view_contact: boolean;
  delete: boolean;
  manage_tutors: boolean;
  view_patients: boolean;
}

export interface TutorPageAbilities extends TutorAbilities {
  create: boolean;
  export: boolean;
}

export interface TutorResumen {
  tutor_id: number;
  es_principal: boolean;
  nombre_completo: string;
  ci: string | null;
  telefono: string;
  telefono_alternativo: string | null;
  email: string | null;
  direccion: string | null;
  parentesco_id: number | null;
  parentesco: string | null;
  activo: boolean;
  estado: 'activo' | 'inactivo';
  sucursal_registro: SucursalResumen | null;
  version: number;
  created_at: string | null;
  updated_at: string | null;
  can: TutorAbilities;
}

export interface PacienteRowAbilities {
  edit: boolean;
  change_status: boolean;
  view_sensibles: boolean;
  delete: boolean;
  view_tutors: boolean;
  manage_tutors: boolean;
  view_emergency_contacts: boolean;
  manage_emergency_contacts: boolean;
}

export interface PacientePageAbilities {
  create: boolean;
  edit: boolean;
  change_status: boolean;
  view_sensibles: boolean;
  export: boolean;
  tutors: boolean;
  diagnostics: boolean;
  manage_tutors: boolean;
  view_emergency_contacts: boolean;
  manage_emergency_contacts: boolean;
}

export interface ContactoEmergencia {
  id: number;
  paciente_id: number;
  nombre_completo: string;
  parentesco_id: number | null;
  parentesco: string | null;
  telefono: string;
  version: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface ContactoEmergenciaCreatePayload {
  nombre_completo: string;
  parentesco_id: number;
  telefono: string;
}

export interface ContactoEmergenciaUpdatePayload {
  nombre_completo?: string;
  parentesco_id?: number;
  telefono?: string;
  version: number;
}

export interface Paciente {
  id: number;
  codigo: string | null;
  nombre: string;
  apellido_paterno: string;
  apellido_materno: string | null;
  nombre_completo: string;
  fecha_nacimiento: string;
  edad: EdadPaciente;
  sexo: PacienteSexo;
  sexo_label: string;
  ci: string | null;
  tipo_diagnostico_id: number | null;
  sin_diagnostico_definido: boolean;
  sucursal_registro: SucursalResumen;
  estado: PacienteEstado;
  estado_label: string;
  version: number;
  created_at: string | null;
  updated_at: string | null;
  can: PacienteRowAbilities;
  observaciones?: string | null;
  alergias?: string | null;
  condiciones_medicas?: string | null;
  sensibles_protegidos?: boolean;
  tutores_disponibles?: boolean;
  tutores_mensaje?: string | null;
  tutores?: TutorResumen[] | null;
  contactos_emergencia?: ContactoEmergencia[];
}

export interface PacientePaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface PacienteListResponse {
  data: Paciente[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: PacientePaginationMeta;
  can: PacientePageAbilities;
}

export interface PacienteResponse {
  data: Paciente;
  can: PacientePageAbilities;
}

export interface PacienteListParams {
  search?: string;
  estado?: PacienteEstado | '';
  tipo_diagnostico_id?: number | '';
  sin_diagnostico?: boolean;
  sucursal_registro_id?: number | '';
  page?: number;
  per_page?: number;
  sort?: PacienteSort;
  direction?: 'asc' | 'desc';
}

export interface PacienteEstadoOption {
  value: PacienteEstado;
  label: string;
}

export interface PacienteSexoOption {
  value: PacienteSexo;
  label: string;
}

export interface PacienteSucursalOption {
  id: number;
  codigo: string;
  nombre: string;
}

export interface PacienteModuleAvailability {
  disponible: boolean;
  mensaje: string;
}

export interface PacienteOptions {
  estados: PacienteEstadoOption[];
  sexos: PacienteSexoOption[];
  sucursales: PacienteSucursalOption[];
  tipos_diagnostico: PacienteModuleAvailability & { items: unknown[] };
  inscripciones: PacienteModuleAvailability;
  tutores: PacienteModuleAvailability;
  parentescos: TutorParentescoOption[];
  puede_ver_sensibles: boolean;
  can: PacientePageAbilities;
}

export interface PacienteOptionsResponse {
  data: PacienteOptions;
  can: PacientePageAbilities;
}

export interface TutorPrincipalNewPayload {
  modo: 'nuevo';
  nombre_completo: string;
  ci: string | null;
  telefono: string;
  telefono_alternativo: string | null;
  email: string | null;
  direccion: string | null;
  parentesco_id: number;
}

export interface TutorPrincipalExistingPayload {
  modo: 'existente';
  tutor_id: number;
  parentesco_id?: number | null;
}

export type TutorPrincipalPayload = TutorPrincipalNewPayload | TutorPrincipalExistingPayload;

export interface PacienteCreatePayload {
  nombre: string;
  apellido_paterno: string;
  apellido_materno: string | null;
  fecha_nacimiento: string;
  sexo: PacienteSexo;
  ci: string | null;
  tipo_diagnostico_id: number | null;
  sucursal_registro_id: number;
  alergias?: string | null;
  condiciones_medicas?: string | null;
  observaciones?: string | null;
  tutor_principal: TutorPrincipalPayload;
  confirmar_duplicado?: boolean;
  motivo_confirmacion?: string | null;
}

export interface PacienteUpdatePayload {
  nombre?: string;
  apellido_paterno?: string;
  apellido_materno?: string | null;
  fecha_nacimiento?: string;
  sexo?: PacienteSexo;
  ci?: string | null;
  tipo_diagnostico_id?: number | null;
  alergias?: string | null;
  condiciones_medicas?: string | null;
  observaciones?: string | null;
  version: number;
}

export interface PacienteStatusPayload {
  estado: PacienteEstado;
  motivo: string | null;
  version: number;
}

export interface TutorPaciente {
  id: number;
  codigo: string | null;
  nombre_completo: string;
  estado: PacienteEstado;
  estado_label: string;
  sucursal_registro_id: number;
  es_principal: boolean;
  parentesco_id: number | null;
  parentesco: string | null;
}

export interface Tutor {
  id: number;
  sucursal_registro: SucursalResumen | null;
  nombre_completo: string;
  ci: string | null;
  telefono: string;
  telefono_alternativo: string | null;
  email: string | null;
  direccion: string | null;
  parentesco_id: number | null;
  parentesco: string | null;
  activo: boolean;
  estado: 'activo' | 'inactivo';
  version: number;
  motivo_estado?: string | null;
  estado_cambiado_en?: string | null;
  created_at: string | null;
  updated_at: string | null;
  pacientes?: TutorPaciente[];
  can: TutorAbilities;
}

export type TutorSort = 'nombre_completo' | 'ci' | 'activo' | 'updated_at';

export interface TutorListParams {
  search?: string;
  activo?: boolean;
  sucursal_registro_id?: number;
  page?: number;
  per_page?: number;
  sort?: TutorSort;
  direction?: 'asc' | 'desc';
}

export interface TutorListResponse {
  data: Tutor[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: PacientePaginationMeta;
  can: TutorPageAbilities;
}

export interface TutorResponse {
  data: Tutor;
  can: TutorPageAbilities;
}

export interface TutorCreatePayload {
  nombre_completo: string;
  ci: string | null;
  telefono: string;
  telefono_alternativo: string | null;
  email: string | null;
  direccion: string | null;
  parentesco_id: number;
  sucursal_registro_id?: number;
}

export interface TutorUpdatePayload {
  nombre_completo?: string;
  ci?: string | null;
  telefono?: string;
  telefono_alternativo?: string | null;
  email?: string | null;
  direccion?: string | null;
  parentesco_id?: number;
  version: number;
}

export interface TutorStatusPayload {
  activo: boolean;
  motivo: string | null;
  version: number;
}

export interface TutorParentescoOption {
  id: number;
  nombre: string;
}

export interface TutorOptions {
  parentescos: TutorParentescoOption[];
  sucursales: PacienteSucursalOption[];
  estados: { value: 'activo' | 'inactivo'; label: string }[];
  can: TutorPageAbilities;
}

export interface TutorOptionsResponse {
  data: TutorOptions;
  can: TutorPageAbilities;
}

export interface PacienteTutorAssociation {
  paciente_id: number;
  tutor_id: number;
  es_principal: boolean;
  tutor: Tutor;
}

export interface PacientesApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  data?: DuplicateResponseData;
}

export interface DuplicateMinimalPatient {
  id: number;
  codigo: string | null;
  nombre_completo: string;
  fecha_nacimiento: string;
  estado: PacienteEstado;
  estado_label: string;
  puede_ver_ficha: boolean;
  tipo_coincidencia?: string;
  tipo_coincidencia_label?: string;
}

export interface DuplicateResponseData {
  coincide_documento?: boolean;
  fuera_de_alcance?: boolean;
  paciente_existente?: DuplicateMinimalPatient | null;
  requiere_confirmacion?: boolean;
  coincidencias?: DuplicateMinimalPatient[];
  coincidencias_fuera_de_alcance?: boolean;
}

export const ESTADO_OPTIONS: { value: PacienteEstado; label: string }[] = [
  { value: 'activo', label: 'Activo' },
  { value: 'en_pausa', label: 'En pausa' },
  { value: 'retirado', label: 'Retirado' },
];

export const ESTADO_FILTER_OPTIONS: { value: string; label: string }[] = [
  { value: '', label: 'Todos los estados' },
  ...ESTADO_OPTIONS.map((estado) => ({ value: estado.value, label: estado.label })),
];

export const SEXO_OPTIONS: { value: PacienteSexo; label: string }[] = [
  { value: 'F', label: 'Femenino' },
  { value: 'M', label: 'Masculino' },
];

export const EMPTY_PACIENTE_ABILITIES: PacientePageAbilities = {
  create: false,
  edit: false,
  change_status: false,
  view_sensibles: false,
  export: false,
  tutors: false,
  diagnostics: false,
  manage_tutors: false,
  view_emergency_contacts: false,
  manage_emergency_contacts: false,
};

export const EMPTY_PACIENTE_ROW_ABILITIES: PacienteRowAbilities = {
  edit: false,
  change_status: false,
  view_sensibles: false,
  delete: false,
  view_tutors: false,
  manage_tutors: false,
  view_emergency_contacts: false,
  manage_emergency_contacts: false,
};

export const EMPTY_TUTOR_ABILITIES: TutorAbilities = {
  edit_data: false,
  edit_contact: false,
  change_status: false,
  view_contact: false,
  delete: false,
  manage_tutors: false,
  view_patients: false,
};

export const EMPTY_TUTOR_PAGE_ABILITIES: TutorPageAbilities = {
  ...EMPTY_TUTOR_ABILITIES,
  create: false,
  export: false,
};

export const PACIENTES_TEXTS = {
  title: 'Pacientes',
  description: 'Fichas, búsqueda y seguimiento de los niños y niñas del centro.',
  searchPlaceholder: 'Buscar por nombre, documento o código...',
  noResults: 'No hay pacientes para los filtros seleccionados.',
  loading: 'Cargando pacientes...',
  create: 'Nuevo paciente',
  edit: 'Editar paciente',
  status: 'Cambiar estado',
  save: 'Guardar',
  saving: 'Guardando...',
  cancel: 'Cancelar',
  close: 'Cerrar',
  back: 'Volver',
  fichaIngreso: 'Ficha de ingreso',
  deleteUnavailable: 'Los pacientes no se eliminan. Cambiá el estado a retirado.',
  tutorsUnavailable: 'Disponible con el submódulo de tutores y contactos (04.02).',
  diagnosticsUnavailable: 'Disponible con el catálogo de diagnósticos (módulo 05).',
  enrollmentFilterUnavailable: 'El filtro por sucursal de inscripción estará disponible con Inscripciones (módulo 06).',
  sensitiveProtected: 'Datos sensibles protegidos. Necesitás el permiso paciente.ver_sensibles.',
  auditNotice: 'Esta consulta queda registrada en la bitácora.',
  noTutor: 'Sin tutor asociado',
  principal: 'Principal',
  makePrincipal: 'Hacer principal',
  removeTutor: 'Quitar',
  addTutor: 'Agregar tutor',
  existingTutor: 'Usar tutor existente',
  newTutor: 'Crear tutor nuevo',
  tutorSearch: 'Buscar tutor por nombre, documento o teléfono...',
  noTutorsFound: 'No se encontraron tutores.',
  emergencyContacts: 'Contactos de emergencia',
  emergencyContactsDesc: 'Personas adicionales a las que contactar ante una emergencia.',
  addEmergencyContact: 'Agregar contacto',
  editEmergencyContact: 'Editar contacto',
  deleteEmergencyContact: 'Eliminar contacto',
  noEmergencyContacts: 'Sin contactos de emergencia registrados.',
  emergencyContactName: 'Nombre completo',
  emergencyContactPhone: 'Teléfono',
  emergencyContactSaved: 'Contacto guardado',
  emergencyContactDeleted: 'Contacto eliminado',
  duplicateTitle: 'Posible duplicado',
  duplicateHardTitle: 'Documento ya registrado',
  duplicateConfirm: 'No es el mismo, continuar',
  duplicateReason: 'Motivo de la confirmación',
  duplicateReasonRequired: 'Para continuar tenés que explicar por qué no es un duplicado.',
  openFicha: 'Abrir ficha',
  outOfScopeDuplicate: 'Existe una posible coincidencia fuera de tu alcance. Si estás seguro de que es otra persona, confirmá con un motivo.',
  versionConflict: 'Otro usuario modificó la ficha. Recargá los datos antes de guardar.',
  auditUnavailable: 'No se pudo registrar la consulta en la bitácora. Por seguridad no se muestra la ficha. Reintentá.',
  notFound: 'El paciente no existe o está fuera de tu alcance.',
  networkError: 'No se pudo conectar con el servidor. Reintentá la operación.',
} as const;

export const TUTORES_TEXTS = {
  title: 'Tutores',
  description: 'Contactos familiares, teléfonos y pacientes asociados.',
  searchPlaceholder: 'Buscar por nombre, documento o teléfono...',
  loading: 'Cargando tutores...',
  noResults: 'No hay tutores para los filtros seleccionados.',
  create: 'Nuevo tutor',
  edit: 'Editar tutor',
  status: 'Cambiar estado',
  export: 'Exportar Excel',
  exporting: 'Exportando...',
  exportSuccess: 'Exportación generada',
  view: 'Ver ficha',
  back: 'Volver a tutores',
  save: 'Guardar',
  saving: 'Guardando...',
  cancel: 'Cancelar',
  close: 'Cerrar',
  editContactOnly: 'Solo podés editar los datos de contacto de este tutor.',
  deactivateReason: 'Motivo de la desactivación *',
  statusReason: 'Motivo',
  active: 'Activo',
  inactive: 'Inactivo',
  principal: 'Principal',
  noPatients: 'Sin pacientes asociados.',
  patients: 'Pacientes asociados',
  contact: 'Contacto',
  identity: 'Identificación',
  branch: 'Sucursal de registro',
  allBranches: 'Todas las sucursales',
  allStates: 'Todos los estados',
  noParentesco: 'Sin parentesco',
  auditNotice: 'La consulta de la ficha queda registrada en la bitácora.',
  auditUnavailable:
    'No se pudo registrar la consulta en la bitácora. Por seguridad no se muestra la ficha. Reintentá.',
  notFound: 'El tutor no existe o está fuera de tu alcance.',
  versionConflict: 'Otro usuario modificó la ficha. Recargá los datos antes de guardar.',
  duplicate: 'Ya existe un tutor con datos similares.',
  phoneHint: '8 dígitos. Móvil: empieza en 6 o 7. Alternativo: también 2, 3 o 4.',
  statusReasonRequired: 'Indicá el motivo para desactivar al tutor.',
  deleteUnavailable: 'Los tutores no se eliminan. Desactivalos para conservar el historial.',
} as const;
