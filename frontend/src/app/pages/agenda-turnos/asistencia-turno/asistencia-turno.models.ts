export type EstadoAsistencia = 'asistio' | 'falto';

export type EstadoTurnoAsistencia =
  | 'programado'
  | 'asistio'
  | 'falto'
  | 'cancelado'
  | 'reprogramado';

export interface AsistenciaOrigen {
  tipo: string;
  id: number;
  estado_efectivo: string;
  fecha_inicio: string;
  fecha_fin: string;
  cantidad_sesiones: number | null;
}

export interface AsistenciaVentana {
  dentro: boolean;
  horas: number;
  vence_en: string | null;
  motivo: string;
}

export interface AsistenciaPersona {
  id: number;
  nombre: string | null;
}

export interface AsistenciaTurnoInfo {
  id: number;
  fecha: string;
  hora_inicio: string;
  hora_fin: string;
  estado: EstadoTurnoAsistencia;
  estado_label: string;
  version: number;
  asistencia_registrada_en: string | null;
  sucursal_id: number;
  paciente_id: number;
  servicio_id: number;
  profesional_id: number | null;
  sala_id: number | null;
  origen: AsistenciaOrigen | null;
  es_diagnostico: boolean;
  ventana: AsistenciaVentana;
  errores_registro: string[];
  destino_correccion: EstadoAsistencia | null;
  paciente: AsistenciaPersona;
  servicio: AsistenciaPersona;
  sala: AsistenciaPersona | null;
  profesional: AsistenciaPersona | null;
  usuario_asistencia: string | null;
}

export interface AsistenciaEfectoSaldo {
  aplicable: boolean;
  disponible: boolean;
  mensaje: string;
  pactadas?: number;
  realizadas?: number;
  faltadas?: number;
  canceladas?: number;
  saldo_actual?: number;
  saldo_resultante?: number;
  saldo_agotado?: boolean;
}

export interface AsistenciaEfectoIndicadores {
  disponible: boolean;
  mensaje: string;
  periodo_desde?: string;
  periodo_hasta?: string;
  asistidos?: number;
  faltos?: number;
  cancelados?: number;
  porcentaje_actual?: number;
  porcentaje_resultante?: number;
}

export interface AsistenciaCorreccion {
  destino: EstadoAsistencia | null;
  destino_label: string | null;
  fuera_ventana: boolean;
  requiere_motivo: boolean;
}

export interface AsistenciaAccion {
  codigo: string;
  etiqueta: string;
}

export interface AsistenciaCan {
  ver: boolean;
  registrar: boolean;
  corregir: boolean;
  corregir_fuera_ventana: boolean;
  ver_historial: boolean;
}

export interface AsistenciaTurnoData {
  turno: AsistenciaTurnoInfo;
  ventana: AsistenciaVentana;
  correccion: AsistenciaCorreccion;
  efecto: {
    saldo: AsistenciaEfectoSaldo;
    indicadores: AsistenciaEfectoIndicadores;
  } | null;
  mensaje: string | null;
  can: AsistenciaCan;
  acciones: AsistenciaAccion[];
}

export interface AsistenciaTurnoResponse {
  data: AsistenciaTurnoData;
  can: AsistenciaCanPantalla;
}

export interface AsistenciaCanPantalla {
  ver: boolean;
  registrar: boolean;
  corregir_fuera_ventana: boolean;
  ver_indicadores: boolean;
  resumen_diario: boolean;
  exportar: boolean;
}

export interface AsistenciaResultado {
  turno: AsistenciaTurnoInfo;
  sin_cambios: boolean;
  accion: 'registro' | 'correccion' | 'sin_cambios';
  fuera_ventana?: boolean;
}

export interface AsistenciaResultadoResponse {
  data: AsistenciaResultado;
  can: AsistenciaCanPantalla;
}

export interface AsistenciaIndicadorFila {
  clave: number | string | null;
  etiqueta: string;
  total: number;
  asistidos: number;
  faltos: number;
  cancelados: number;
  pendientes: number;
  reprogramados: number;
  porcentaje: number;
}

export interface AsistenciaIndicadoresData {
  periodo: { desde: string; hasta: string };
  sucursal: { id: number; nombre: string };
  sucursal_id: number;
  agrupacion: string;
  filas: AsistenciaIndicadorFila[];
  totales: {
    total: number;
    asistidos: number;
    faltos: number;
    cancelados: number;
    pendientes: number;
    reprogramados: number;
    porcentaje: number;
  };
  filtros: Record<string, unknown>;
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
}

export interface AsistenciaIndicadoresResponse {
  data: AsistenciaIndicadoresData;
  can: AsistenciaCanPantalla;
}

export interface AsistenciaResumenDiarioData {
  fecha: string;
  sucursal: { id: number; nombre: string };
  zona_horaria: string;
  es_hoy: boolean;
  conteos: {
    total: number;
    atendidos: number;
    faltados: number;
    cancelados: number;
    pendientes: number;
    reprogramados: number;
  };
  por_sala: Array<{
    sala_id: number;
    sala: string;
    total: number;
    atendidos: number;
    faltados: number;
    cancelados: number;
    pendientes: number;
    reprogramados: number;
  }>;
  mensaje: string | null;
}

export interface AsistenciaResumenDiarioResponse {
  data: AsistenciaResumenDiarioData;
  can: AsistenciaCanPantalla;
}

export interface AsistenciaApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  context?: Record<string, unknown>;
}

export const ASISTENCIA_TEXTS = {
  title: 'Asistencia',
  current: 'Asistencia actual',
  notRegistered: 'Sin asistencia registrada',
  register: 'Registrar asistencia',
  attended: 'Asistió',
  missed: 'Faltó',
  correct: 'Corregir asistencia',
  history: 'Ver historial',
  loading: 'Cargando asistencia…',
  saving: 'Guardando…',
  successAttended: 'Turno marcado como Asistió.',
  successMissed: 'Turno marcado como Faltó.',
  successCorrected: 'Asistencia corregida.',
  noChanges: 'La asistencia ya estaba en ese estado.',
  outsideWindow: 'La ventana de edición venció. Contactá a Administración para corregir la asistencia.',
  blocked: 'El turno no admite asistencia.',
  reason: 'Motivo',
  reasonRequired: 'Ingresá el motivo de la corrección.',
  reasonPlaceholder: 'Detalle de la corrección fuera de ventana',
  confirm: 'Confirmar corrección',
  close: 'Cerrar',
  cancel: 'Cancelar',
  warningTitle: 'Corrección de asistencia',
  warning: 'Vas a cambiar el estado de asistencia. Revisá el efecto antes de confirmar.',
  confirmCheck: 'Confirmo la corrección de la asistencia.',
  balance: 'Efecto en saldo',
  indicators: 'Efecto en indicadores',
  balanceUnavailable: 'Efecto en saldo no disponible.',
  indicatorsUnavailable: 'Efecto en indicadores no disponible.',
  percentage: 'Porcentaje de asistencia',
  noData: 'Sin datos para el período.',
  windowOpen: 'Dentro de la ventana de edición.',
  windowClosed: 'Fuera de la ventana de edición.',
} as const;

export const ESTADO_ASISTENCIA_LABEL: Record<string, string> = {
  programado: 'Programado',
  asistio: 'Asistió',
  falto: 'Faltó',
  cancelado: 'Cancelado',
  reprogramado: 'Reprogramado',
};

export function fechaAsistencia(value: string | null | undefined): string {
  if (!value) {
    return '—';
  }

  const [anio, mes, dia] = value.split('-');

  return `${dia}/${mes}/${anio}`;
}

export function fechaHoraAsistencia(value: string | null | undefined): string {
  if (!value) {
    return '—';
  }

  const date = new Date(value);

  return Number.isNaN(date.getTime())
    ? value
    : date.toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' });
}

export function porcentajeAsistencia(value: number | null | undefined): string {
  return value === null || value === undefined ? '—' : `${value.toFixed(2)}%`;
}
