export type EstadoTurno =
  | 'programado'
  | 'asistio'
  | 'falto'
  | 'cancelado'
  | 'reprogramado';

export type AgendaVista = 'semana' | 'profesional' | 'sala';

export interface SucursalDisponible {
  id: number;
  codigo: string;
  nombre: string;
  activo: boolean;
}

export interface SucursalesDisponiblesResponse {
  data: SucursalDisponible[];
  predeterminada_id: number;
  can: {
    ver: boolean;
    exportar: boolean;
  };
  zona_horaria: string;
}

export interface DiaAgenda {
  fecha: string;
  nombre: string;
  dia_semana: number;
  visible: boolean;
  es_hoy: boolean;
}

export interface SemanaAgenda {
  inicio: string;
  fin: string;
  inicio_visible: string;
  fin_visible: string;
  dias: DiaAgenda[];
}

export interface DisponibilidadDatos {
  pacientes: boolean;
  profesionales: boolean;
  salas: boolean;
  servicios: boolean;
  areas: boolean;
}

export interface PersonaResumen {
  id: number;
  nombre_completo: string | null;
  disponible: boolean;
}

export interface ServicioResumen {
  id: number;
  nombre: string | null;
  area_id: number | null;
  area_nombre: string | null;
  area_color: string | null;
  color_texto: string | null;
  disponible: boolean;
}

export interface SalaResumen {
  id: number;
  nombre: string | null;
  disponible: boolean;
}

export interface TurnoAgenda {
  id: number;
  fecha: string;
  hora_inicio: string;
  hora_fin: string;
  duracion_minutos: number;
  estado: EstadoTurno;
  estado_label: string;
  paciente: PersonaResumen;
  servicio: ServicioResumen;
  sala: SalaResumen | null;
  profesional: PersonaResumen | null;
  sin_asignar: boolean;
  sin_sala: boolean;
  es_hoy: boolean;
}

export interface ResumenSemanaData {
  total: number;
  base: 'turnos_filtrados_visibles';
  por_estado: Record<EstadoTurno, number>;
}

export interface AvisoAgenda {
  codigo: string;
  cantidad: number;
  dias: string[];
  mensaje: string;
}

export interface RangoHorario {
  hora_inicio: string;
  hora_fin: string;
}

export interface SucursalAgenda {
  id: number;
  codigo: string;
  nombre: string;
  disponible: boolean;
}

export interface AgendaSemanalData {
  sucursal: SucursalAgenda;
  semana: SemanaAgenda;
  rango_horario: RangoHorario;
  turnos: TurnoAgenda[];
  resumen: ResumenSemanaData;
  avisos: AvisoAgenda[];
  disponibilidad: DisponibilidadDatos;
  zona_horaria: string;
}

export interface AgendaSemanalResponse {
  data: AgendaSemanalData;
  can: {
    ver: boolean;
    exportar: boolean;
  };
  filtros: AgendaSemanaParams;
}

export interface AreaOpcion {
  id: number;
  nombre: string;
  color: string;
  color_texto: string | null;
}

export interface ServicioOpcion {
  id: number;
  nombre: string;
  area_id: number;
}

export interface SalaOpcion {
  id: number;
  nombre: string;
}

export interface ProfesionalOpcion {
  id: number;
  nombre_completo: string;
}

export interface EstadoOpcion {
  value: EstadoTurno;
  label: string;
}

export interface OpcionesFiltrosData {
  areas: AreaOpcion[];
  servicios: ServicioOpcion[];
  salas: SalaOpcion[];
  profesionales: ProfesionalOpcion[];
  estados: EstadoOpcion[];
  especiales: {
    profesional_sin_asignar: { value: 'sin_asignar'; label: string };
    sala_sin_asignar: { value: 'sin_asignar'; label: string };
  };
}

export interface OpcionesFiltrosResponse {
  data: OpcionesFiltrosData;
  disponibilidad: Pick<DisponibilidadDatos, 'profesionales' | 'salas' | 'servicios' | 'areas'>;
  can: {
    ver: boolean;
    exportar: boolean;
  };
}

export interface AccionTurno {
  codigo: string;
  etiqueta: string;
  metodo: string;
  ruta: string;
}

export interface TurnoDetalleData extends TurnoAgenda {
  turno_origen_id: number | null;
}

export interface TurnoDetalleResponse {
  data: TurnoDetalleData;
  acciones: AccionTurno[];
  acciones_disponibles: boolean;
  mensaje_acciones: string | null;
  can: {
    ver: boolean;
    exportar: boolean;
  };
}

export interface TurnoDetalleVista {
  turno: TurnoDetalleData;
  acciones: AccionTurno[];
  mensaje: string | null;
}

export interface AgendaFiltros {
  area_id: string;
  sala_id: string;
  profesional_id: string;
  servicio_id: string;
  estado: string;
}

export interface AgendaSemanaParams {
  sucursal_id?: number;
  semana?: string;
  area_id?: number;
  sala_id?: number;
  sala_sin_asignar?: boolean;
  profesional_id?: number;
  profesional_sin_asignar?: boolean;
  servicio_id?: number;
  estado?: EstadoTurno;
}

export interface AgendaApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  modulo?: string;
}

export const EMPTY_FILTROS: AgendaFiltros = {
  area_id: '',
  sala_id: '',
  profesional_id: '',
  servicio_id: '',
  estado: '',
};
