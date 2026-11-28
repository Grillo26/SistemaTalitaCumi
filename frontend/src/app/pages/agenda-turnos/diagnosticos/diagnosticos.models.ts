export type EstadoGeneralDiagnostico =
  | 'incompleto'
  | 'en_proceso'
  | 'completo'
  | 'cancelado';

export type EstadoTurnoDiagnostico =
  | 'programado'
  | 'asistio'
  | 'falto'
  | 'cancelado'
  | 'reprogramado';

export interface EspecialidadResumen {
  id: number;
  nombre: string;
}

export interface TurnoDiagnostico {
  id: number;
  profesional_id: number | null;
  profesional_nombre: string;
  fecha: string;
  hora_inicio: string;
  hora_fin: string;
  estado: EstadoTurnoDiagnostico;
  estado_label: string;
  duracion_minutos?: number;
  es_futuro?: boolean;
  puede_reprogramar?: boolean;
  puede_registrar_asistencia?: boolean;
}

export interface ProfesionalDiagnostico {
  id: number;
  nombre_completo: string;
  especialidades: EspecialidadResumen[];
  activo_en_sucursal: boolean;
  turno_vigente: TurnoDiagnostico | null;
  turno_actual: TurnoDiagnostico | null;
  disponible_para_programar: boolean;
}

export interface ProfesionalDiagnosticoLista {
  id: number;
  nombre_completo: string;
  turno: TurnoDiagnostico | null;
}

export interface PuestoDiagnostico {
  especialidad_id: number;
  especialidad_nombre: string;
  cantidad_minima: number;
  asistidos: number;
  vigentes: number;
  cubierto: boolean;
  profesionales: Array<{
    id: number;
    nombre_completo: string;
    turno: TurnoDiagnostico | null;
    disponible_para_programar: boolean;
  }>;
}

export type PuestoDiagnosticoLista = Omit<PuestoDiagnostico, 'profesionales'>;

export interface FaltanteDiagnostico {
  tipo: 'sin_turno' | 'sin_asistencia' | 'sin_requisitos' | string;
  especialidad_id: number;
  mensaje: string;
}

export interface WhatsappDiagnostico {
  disponible: boolean;
  mensaje?: string;
  telefono?: string;
  tutor_nombre?: string;
  whatsapp_url?: string;
}

export interface DiagnosticoListaRow {
  id: number;
  paciente: {
    id: number;
    nombre_completo: string;
  };
  sucursal: {
    id: number;
    nombre: string;
  };
  tipo_diagnostico: {
    id: number;
    nombre: string;
  };
  fecha_inicio: string;
  fecha_fin: string;
  estado: string;
  estado_label: string;
  estado_general: EstadoGeneralDiagnostico;
  estado_general_label: string;
  profesionales: ProfesionalDiagnosticoLista[];
  turnos: TurnoDiagnostico[];
  puestos: PuestoDiagnosticoLista[];
  faltantes: FaltanteDiagnostico[];
}

export interface DiagnosticoDetalle {
  id: number;
  paciente: {
    id: number;
    codigo: string | null;
    nombre_completo: string;
    estado: string | null;
    estado_label: string | null;
  };
  sucursal: {
    id: number;
    nombre: string;
  };
  servicio: {
    id: number;
    nombre: string;
  };
  tipo_diagnostico: {
    id: number;
    nombre: string;
  };
  fecha_inicio: string;
  fecha_fin: string;
  estado: string;
  estado_label: string;
  estado_general: EstadoGeneralDiagnostico;
  estado_general_label: string;
  estado_general_guardado: string;
  profesionales: ProfesionalDiagnostico[];
  turnos: TurnoDiagnostico[];
  puestos: PuestoDiagnostico[];
  faltantes: FaltanteDiagnostico[];
  whatsapp: WhatsappDiagnostico;
}

export interface DiagnosticosCan {
  ver: boolean;
  programar: boolean;
  cancelar: boolean;
  actualizar_estado: boolean;
  exportar: boolean;
  whatsapp: boolean;
}

export interface DiagnosticosListaCan {
  ver: boolean;
  programar: boolean;
  exportar: boolean;
}

export interface DiagnosticosListaResponse {
  data: DiagnosticoListaRow[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  can: DiagnosticosListaCan;
}

export interface DiagnosticoDetalleResponse {
  data: DiagnosticoDetalle;
  can: DiagnosticosCan;
}

export interface DiagnosticosOpcionesData {
  sucursal_id: number;
  sucursales: Array<{ id: number; nombre: string }>;
  tipos_diagnostico: Array<{ id: number; nombre: string }>;
  profesionales: Array<{ id: number; nombre_completo: string }>;
  estados: Array<{ value: EstadoGeneralDiagnostico; label: string }>;
}

export interface DiagnosticosOpcionesResponse {
  data: DiagnosticosOpcionesData;
  can: Pick<DiagnosticosCan, 'ver' | 'exportar'>;
}

export interface ProgramarTurnoPayload {
  profesional_id: number;
  fecha: string;
  hora_inicio: string;
  duracion_minutos?: number;
}

export interface ProgramarTurnoData {
  turno: {
    id: number;
    diagnostico_id: number;
    profesional_id: number;
    fecha: string;
    hora_inicio: string;
    hora_fin: string;
    duracion_minutos: number;
    estado: EstadoTurnoDiagnostico;
    estado_label: string;
  };
  estado_general: EstadoGeneralDiagnostico;
  estado_general_label: string;
}

export interface ProgramarTurnoResponse {
  data: ProgramarTurnoData;
  can: DiagnosticosCan;
}

export interface CancelarDiagnosticoData {
  diagnostico_id: number;
  estado_general: 'cancelado';
  estado_general_label: string;
  turnos_cancelados: number[];
  cantidad_cancelados: number;
  turnos_conservados: number;
}

export interface CancelarDiagnosticoResponse {
  data: CancelarDiagnosticoData;
  can: DiagnosticosCan;
}

export interface ActualizarEstadoPayload {
  estado_general: Exclude<EstadoGeneralDiagnostico, 'cancelado'>;
  motivo: string;
}

export interface ActualizarEstadoData {
  diagnostico_id: number;
  estado_general: EstadoGeneralDiagnostico;
  estado_general_label: string;
  sin_cambios: boolean;
  faltantes: FaltanteDiagnostico[];
}

export interface ActualizarEstadoResponse {
  data: ActualizarEstadoData;
  can: DiagnosticosCan;
}

export interface WhatsappDiagnosticoResponse {
  data: WhatsappDiagnostico;
}

export interface DiagnosticosApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  context?: {
    faltantes?: FaltanteDiagnostico[];
    estado_sugerido?: string;
    estado_actual?: string;
    [key: string]: unknown;
  };
}

export const ESTADO_GENERAL_COLOR: Record<
  EstadoGeneralDiagnostico,
  'success' | 'warning' | 'info' | 'error' | 'gray'
> = {
  incompleto: 'warning',
  en_proceso: 'info',
  completo: 'success',
  cancelado: 'error',
};

export const ESTADO_TURNO_COLOR: Record<
  EstadoTurnoDiagnostico,
  'success' | 'warning' | 'info' | 'error' | 'gray'
> = {
  programado: 'info',
  asistio: 'success',
  falto: 'error',
  cancelado: 'gray',
  reprogramado: 'gray',
};

export function fechaLegible(fecha: string | null | undefined): string {
  if (!fecha) {
    return '—';
  }

  const [anio, mes, dia] = fecha.split('-');

  return anio && mes && dia ? `${dia}/${mes}/${anio}` : fecha;
}

export function horaLegible(hora: string | null | undefined): string {
  return (hora ?? '').slice(0, 5) || '—';
}
