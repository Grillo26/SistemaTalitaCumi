import {
  ContactoEmergencia,
  Paciente,
  PacientePageAbilities,
  PacientesApiError,
  SucursalResumen,
} from './pacientes.models';

export type EstadoFichaIngreso = 'borrador' | 'completa' | 'archivada';

export interface FichaIngresoAbilities {
  view: boolean;
  create: boolean;
  edit: boolean;
  complete: boolean;
  archive: boolean;
  reactivate: boolean;
  view_sensibles: boolean;
  export: boolean;
}

export interface FichaIngreso {
  id: number;
  paciente_id: number;
  sucursal_ingreso: SucursalResumen | null;
  tutor_principal_id: number | null;
  fecha_ingreso: string;
  derivado_por: string | null;
  derivado_por_institucion: string | null;
  estado: EstadoFichaIngreso;
  estado_label: string;
  version: number;
  completada_en: string | null;
  archivada_en: string | null;
  created_at: string | null;
  updated_at: string | null;
  sensibles_protegidos: boolean;
  campos_protegidos: string[];
  entrevista_disponible: boolean;
  entrevista_mensaje: string;
  contactos_emergencia: ContactoEmergencia[];
  can: FichaIngresoAbilities;
  motivo_consulta_inicial?: string | null;
  composicion_familiar?: string | null;
  antecedentes_familiares?: string | null;
  observaciones?: string | null;
  derivado_por_contacto?: string | null;
  motivo_archivado?: string | null;
}

export interface FichaIngresoHistorial {
  id: number;
  fecha_ingreso: string;
  estado: EstadoFichaIngreso;
  estado_label: string;
  sucursal_ingreso: SucursalResumen | null;
  version: number;
}

export interface FichaIngresoResponse {
  data: FichaIngreso | null;
  paciente: Paciente;
  historial: FichaIngresoHistorial[];
  can: FichaIngresoAbilities;
}

export interface FichaIngresoRevision {
  id: number;
  version: number;
  accion: string;
  usuario_id: number;
  usuario_nombre: string | null;
  campos_modificados: string[];
  snapshot: Record<string, unknown> | null;
  contenido_protegido: boolean;
  created_at: string | null;
}

export interface FichaIngresoCreatePayload {
  motivo_consulta_inicial: string;
  fecha_ingreso: string;
  sucursal_ingreso_id: number;
  composicion_familiar: string | null;
  antecedentes_familiares: string | null;
  observaciones: string | null;
  derivado_por: string | null;
  derivado_por_institucion: string | null;
  derivado_por_contacto: string | null;
}

export interface FichaIngresoUpdatePayload extends Partial<FichaIngresoCreatePayload> {
  version: number;
}

export interface FichaIngresoRevisionResponse {
  data: FichaIngresoRevision[];
  can: FichaIngresoAbilities;
}

export interface FichaIngresoPermissionResponse {
  data: FichaIngreso;
  can: FichaIngresoAbilities;
}

export interface FichaIngresoPageOptions {
  sucursales: { id: number; codigo: string; nombre: string }[];
  abilities: FichaIngresoAbilities;
  pacienteAbilities: PacientePageAbilities | null;
}

export const FICHA_INGRESO_TEXTS = {
  title: 'Ficha de ingreso',
  description:
    'Datos administrativos y de contexto del ingreso. No es la entrevista terapéutica ni el registro clínico.',
  loading: 'Cargando ficha de ingreso...',
  notFound: 'El paciente no existe o está fuera de tu alcance.',
  noFicha: 'Este paciente todavía no tiene una ficha de ingreso.',
  create: 'Crear ficha de ingreso',
  save: 'Guardar cambios',
  saving: 'Guardando...',
  saveDraft: 'Guardar borrador',
  complete: 'Marcar como completa',
  archive: 'Archivar',
  reactivate: 'Reactivar',
  revisions: 'Ver revisiones',
  back: 'Volver a la ficha del paciente',
  cancel: 'Cancelar',
  close: 'Cerrar',
  edit: 'Editar ficha',
  sensitiveShow: 'Mostrar datos sensibles',
  sensitiveHide: 'Ocultar datos sensibles',
  sensitiveProtected:
    'Los datos sensibles están protegidos. Necesitás el permiso ficha_ingreso.ver_sensibles.',
  sensitiveFieldsProtected: 'Campos protegidos',
  auditNotice:
    'Cada consulta de esta ficha queda registrada en la bitácora. Guardar no genera una consulta adicional.',
  auditUnavailable:
    'No se pudo registrar la consulta en la bitácora. Por seguridad no se muestra la ficha. Reintentá.',
  versionConflict: 'Otro usuario modificó la ficha. Recargá los datos antes de guardar.',
  archived: 'La ficha está archivada y no admite edición.',
  archiveReason: 'Motivo de archivo *',
  reactivateReason: 'Motivo de reactivación *',
  archiveHelp: 'El archivo es reversible solo por Superadmin.',
  noContacts: 'Sin contactos de emergencia registrados.',
  contacts: 'Contactos de emergencia',
  contactsHelp: 'Se administran desde la ficha del paciente.',
  history: 'Fichas anteriores',
  noHistory: 'No hay fichas anteriores.',
  viewArchived: 'Ver ficha',
  completed: 'Ficha completada',
  archivedToast: 'Ficha archivada',
  reactivatedToast: 'Ficha reactivada',
  created: 'Ficha creada',
  saved: 'Ficha actualizada',
  date: 'Fecha de ingreso *',
  motive: 'Motivo de consulta *',
  branch: 'Sucursal de ingreso',
  composition: 'Composición familiar',
  antecedents: 'Antecedentes referidos por la familia',
  observations: 'Observaciones iniciales',
  referredBy: 'Persona o institución que deriva',
  referredInstitution: 'Institución',
  referredContact: 'Referencia de contacto',
  status: 'Estado',
  version: 'Versión',
  updatedAt: 'Última modificación',
  completedAt: 'Completada',
  archivedAt: 'Archivada',
  protectedField: 'Protegido',
  sensitiveWarning:
    'Los campos sensibles se ocultan sin permiso. El backend nunca los envía si no corresponde.',
  interviewUnavailable:
    'La vinculación con la entrevista estará disponible con el módulo 06.',
  archiveTitle: 'Archivar ficha de ingreso',
  reactivateTitle: 'Reactivar ficha de ingreso',
  revisionsTitle: 'Revisiones de la ficha',
  revisionsEmpty: 'No hay revisiones registradas.',
  revisionAction: 'Acción',
  revisionUser: 'Usuario',
  revisionDate: 'Fecha',
  revisionFields: 'Campos modificados',
  revisionProtected:
    'El contenido está protegido. Necesitás ficha_ingreso.ver_sensibles para ver el snapshot completo.',
  requiredFields: 'Completá el motivo y la fecha de ingreso.',
} as const;

export const EMPTY_FICHA_INGRESO_ABILITIES: FichaIngresoAbilities = {
  view: false,
  create: false,
  edit: false,
  complete: false,
  archive: false,
  reactivate: false,
  view_sensibles: false,
  export: false,
};

export type FichaIngresoApiError = PacientesApiError;
