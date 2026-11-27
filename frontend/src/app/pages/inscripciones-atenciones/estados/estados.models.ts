export type EstadoPaciente = 'activo' | 'en_pausa' | 'retirado';
export type EstadoInscripcion = 'activa' | 'en_pausa' | 'vencida' | 'renovada' | 'retirada';
export type TipoEntidad = 'inscripcion_sala' | 'paquete_sesiones';
export type AccionEstado = 'pausar' | 'reactivar' | 'retirar';
export type EstadosTab = 'pacientes' | 'inscripciones' | 'reportes';
export type ExportFormat = 'pdf' | 'excel';

export interface EstadoAbilities {
  view: boolean;
  reports: boolean;
  export: boolean;
  patient: {
    pause: boolean;
    reactivate: boolean;
    retire: boolean;
  };
  inscription: {
    pause: boolean;
    reactivate: boolean;
  };
  types: Record<TipoEntidad, { view: boolean; withdraw: boolean }>;
}

export interface MotivoOption {
  value: string;
  label: string;
}

export interface OpcionesEstado {
  estados_paciente: { value: EstadoPaciente; label: string }[];
  estados_inscripcion: { value: EstadoInscripcion; label: string }[];
  acciones: { value: AccionEstado; label: string }[];
  motivos: {
    paciente: { pausar: MotivoOption[]; retirar: MotivoOption[] };
    inscripcion_sala: { pausar: MotivoOption[]; retirar: MotivoOption[] };
    paquete_sesiones: { pausar: MotivoOption[]; retirar: MotivoOption[] };
  };
}

export interface PacienteEstadoRow {
  id: number;
  codigo: string | null;
  nombre_completo: string;
  estado: EstadoPaciente;
  estado_label: string;
  version: number;
  sucursal_registro: { id: number; nombre: string | null };
  tutor_principal: string | null;
  can: {
    pause: boolean;
    reactivate: boolean;
    retire: boolean;
    history: boolean;
  };
}

export interface EstadoInscripcionRow {
  tipo_entidad: TipoEntidad;
  tipo_entidad_label: string;
  id: number;
  paciente: {
    id: number;
    codigo: string | null;
    nombre_completo: string;
    estado: string;
  };
  tutor: { id: number; nombre_completo: string };
  sucursal: { id: number; nombre: string };
  servicio: { id: number; nombre: string };
  sala: { id: number; nombre: string } | null;
  profesional: { id: number; nombre_completo: string } | null;
  cantidad_sesiones: number | null;
  fecha_inicio: string;
  fecha_fin: string;
  dias_para_vencer: number;
  monto_esperado: string;
  estado_almacenado: EstadoInscripcion;
  estado_almacenado_label: string;
  estado_efectivo: EstadoInscripcion;
  estado_efectivo_label: string;
  renovada: boolean;
  en_pausa: boolean;
  retirada: boolean;
  can: {
    view: boolean;
    pause: boolean;
    reactivate: boolean;
    retire: boolean;
    history: boolean;
  };
}

export interface HistorialEstadoItem {
  accion: string;
  estado_anterior: string | null;
  estado_anterior_label: string | null;
  estado_nuevo: string | null;
  estado_nuevo_label: string | null;
  motivo_codigo: string | null;
  motivo_texto: string | null;
  motivo_label: string | null;
  fecha: string | null;
  usuario: string | null;
  critico: boolean;
  origen: 'sistema' | 'manual' | string;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface PaginatedPacientes {
  data: PacienteEstadoRow[];
  meta: PaginationMeta;
  can: EstadoAbilities;
}

export interface PaginatedInscripciones {
  data: EstadoInscripcionRow[];
  meta: PaginationMeta;
  can: EstadoAbilities;
}

export interface PrechequeoItem {
  tipo_entidad?: TipoEntidad;
  id?: number;
  disponible: boolean;
  mensaje: string | null;
  data: Record<string, unknown> | null;
  alerta?: string | null;
}

export interface PrechequeoEstado {
  turnos: PrechequeoItem | null;
  pago: PrechequeoItem | null;
}

export interface PrechequeoPaciente {
  items: Array<{ tipo: TipoEntidad; id: number; sucursal_id: number }>;
  turnos: {
    disponible: boolean;
    mensaje: string | null;
    detalle: PrechequeoItem[];
  };
  pago: {
    disponible: boolean;
    mensaje: string | null;
    detalle: PrechequeoItem[];
  };
}

export interface PreviewRetiroPaciente {
  paciente: {
    id: number;
    codigo: string | null;
    nombre_completo: string;
    estado: EstadoPaciente;
    estado_label: string;
    version: number;
    sucursal_registro: { id: number; nombre: string | null };
  };
  inscripciones: Array<{
    tipo_entidad: TipoEntidad;
    tipo_entidad_label: string;
    id: number;
    paciente: { id: number; nombre_completo: string };
    sucursal: { id: number; nombre: string };
    servicio: { id: number; nombre: string };
    sala?: { id: number; nombre: string } | null;
    profesional?: { id: number; nombre_completo: string } | null;
    cantidad_sesiones?: number | null;
    fecha_inicio: string;
    fecha_fin: string;
    estado_efectivo: EstadoInscripcion;
    estado_efectivo_label: string;
  }>;
  conteos: { total: number; salas: number; paquetes: number };
}

export interface ReporteConteos {
  pacientes: Record<EstadoPaciente, number>;
  inscripciones: Record<EstadoInscripcion, number>;
}

export interface ReporteEstadosData {
  tipo: 'pacientes' | 'inscripciones';
  conteos: ReporteConteos;
  data: PacienteEstadoRow[] | EstadoInscripcionRow[];
  meta: PaginationMeta;
}

export interface EstadoApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  estado_actual?: string;
  estado_esperado?: string;
  sucursales_faltantes?: number[];
  modulo?: string;
}

export interface CambioEstadoPayload {
  estado?: string;
  estado_esperado: string;
  motivo?: string | null;
  motivo_detalle?: string | null;
  fecha_baja?: string;
}

export interface RetiroPacientePayload {
  estado_esperado: string;
  motivo: string;
  motivo_detalle: string | null;
  fecha_baja: string;
  inscripciones: Array<{
    tipo_entidad: TipoEntidad;
    id: number;
    estado_esperado: EstadoInscripcion;
  }>;
}

export const ESTADO_PACIENTE_OPTIONS: { value: string; label: string }[] = [
  { value: '', label: 'Todos los estados' },
  { value: 'activo', label: 'Activo' },
  { value: 'en_pausa', label: 'En pausa' },
  { value: 'retirado', label: 'Retirado' },
];

export const ESTADO_INSCRIPCION_OPTIONS: { value: string; label: string }[] = [
  { value: '', label: 'Todos los estados' },
  { value: 'activa', label: 'Activa' },
  { value: 'en_pausa', label: 'En pausa' },
  { value: 'vencida', label: 'Vencida' },
  { value: 'renovada', label: 'Renovada' },
  { value: 'retirada', label: 'Retirada' },
];

export const TIPO_ENTIDAD_OPTIONS: { value: string; label: string }[] = [
  { value: '', label: 'Todas las entidades' },
  { value: 'inscripcion_sala', label: 'Inscripción a sala' },
  { value: 'paquete_sesiones', label: 'Paquete de sesiones' },
];

export const ESTADOS_TEXTS = {
  title: 'Estados de pacientes e inscripciones',
  subtitle: 'Pausá, reactivá y retirá con trazabilidad, sin borrar historial.',
  patients: 'Pacientes',
  inscriptions: 'Inscripciones',
  reports: 'Reportes',
  patientTitle: 'Pacientes por estado',
  inscriptionTitle: 'Inscripciones por estado',
  reportsTitle: 'Reportes por estado',
  searchPatient: 'Buscar paciente...',
  searchInscription: 'Buscar por paciente, código o CI...',
  allBranches: 'Todas las sucursales',
  allServices: 'Todos los servicios',
  allTypes: 'Todas las entidades',
  allStates: 'Todos los estados',
  filter: 'Filtrar',
  clear: 'Limpiar',
  loading: 'Cargando...',
  noPatients: 'No hay pacientes para los filtros aplicados.',
  noInscriptions: 'No hay inscripciones para los filtros aplicados.',
  noHistory: 'Sin cambios de estado registrados.',
  patient: 'Paciente',
  tutor: 'Tutor',
  branch: 'Sucursal',
  service: 'Servicio',
  period: 'Período',
  expires: 'Vence',
  state: 'Estado',
  type: 'Tipo',
  actions: 'Acciones',
  history: 'Historial',
  pause: 'Pausar',
  reactivate: 'Reactivar',
  retire: 'Retirar',
  retirePatient: 'Retirar paciente',
  confirm: 'Confirmar',
  cancel: 'Cancelar',
  saving: 'Guardando...',
  close: 'Cerrar',
  motive: 'Motivo',
  motiveDetail: 'Detalle del motivo',
  date: 'Fecha de retiro',
  otherRequired: 'El motivo "Otro" requiere un detalle.',
  reasonRequired: 'El motivo es obligatorio.',
  stateChanged: 'El estado cambió desde que cargaste la pantalla. Recargá los datos.',
  forbidden: 'Acción no permitida para su rol.',
  noBranches: 'No tenés sucursales asignadas. Contactá al Superadmin.',
  noPayment: 'Estado de pago no disponible.',
  noTurns: 'Verificación de turnos no disponible.',
  warnings: 'Advertencias',
  patientRetireTitle: 'Retirar al paciente del centro',
  patientRetireHint:
    'El paciente y las inscripciones activas o en pausa quedarán retirados con el mismo motivo y fecha.',
  patientRetireConfirm: 'Confirmar retiro',
  noInscriptionsToDrag: 'El paciente no tiene inscripciones activas o en pausa para arrastrar.',
  historyTitle: 'Historial de estados',
  from: 'Desde',
  to: 'Hasta',
  exportPdf: 'Exportar PDF',
  exportExcel: 'Exportar Excel',
  exportUnavailable: 'La exportación no está disponible: falta la infraestructura PDF/Excel.',
  patientsCount: 'Pacientes',
  inscriptionsCount: 'Inscripciones',
  active: 'Activo',
  paused: 'En pausa',
  retired: 'Retirado',
  activeF: 'Activa',
  pausedF: 'En pausa',
  expired: 'Vencida',
  renewed: 'Renovada',
  withdrawn: 'Retirada',
  noData: 'Sin datos',
  preview: 'Inscripciones que se retirarán',
  precheckUnavailable: 'Verificación no disponible',
  selectState: 'Seleccioná la acción',
  dateToday: 'La fecha de retiro del paciente debe ser hoy.',
} as const;

export function formatDate(value: string | null | undefined): string {
  if (!value) {
    return '—';
  }

  const date = value.split('T')[0];
  const [year, month, day] = date.split('-');
  return `${day}/${month}/${year}`;
}

export function formatDateTime(value: string | null | undefined): string {
  if (!value) {
    return '—';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return formatDate(value);
  }

  return date.toLocaleString('es-BO', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  });
}

export function estadoColor(
  estado: string,
): 'success' | 'warning' | 'error' | 'info' | 'gray' {
  return (
    {
      activo: 'success',
      activa: 'success',
      en_pausa: 'warning',
      vencido: 'error',
      vencida: 'error',
      renovado: 'info',
      renovada: 'info',
      retirado: 'gray',
      retirada: 'gray',
    }[estado] ?? 'gray'
  ) as 'success' | 'warning' | 'error' | 'info' | 'gray';
}

export function diasLabel(dias: number): string {
  if (dias === 0) {
    return 'Vence hoy';
  }

  return dias > 0 ? `En ${dias} día(s)` : `Hace ${Math.abs(dias)} día(s)`;
}
