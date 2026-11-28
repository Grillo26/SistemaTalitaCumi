export type TipoOrigenProgramacion = 'inscripcion_sala' | 'paquete_sesiones';

export type ModoProgramacion = 'generar' | 'regenerar';

export type SeveridadHallazgo = 'bloqueo' | 'advertencia' | 'informativo';

export type EstadoFilaPreview = 'generable' | 'feriado' | 'pasada' | 'omitida';

export interface HallazgoProgramacion {
  tipo: string;
  severidad: SeveridadHallazgo;
  severidad_label: string;
  mensaje: string;
  fecha: string | null;
  hora_inicio: string | null;
  turno_id: number | null;
}

export interface FilaPreview {
  fecha: string;
  hora_inicio: string;
  hora_fin: string;
  estado: EstadoFilaPreview;
  generable: boolean;
  hallazgos: HallazgoProgramacion[];
}

export interface ResumenGeneracion {
  generables: number;
  bloqueados: number;
  advertencias: number;
  feriados: number;
  pasadas: number;
  omitidas: number;
  faltantes: number;
  requiere_confirmar_advertencias: boolean;
  puede_confirmar: boolean;
  cancelar?: number;
  preservar?: number;
  asistidos?: number;
  faltados?: number;
  pasados_sin_marcar?: number;
}

export interface ResumenEntidad {
  id: number;
  nombre: string;
}

export interface PatronProgramacion {
  dias_semana: number[];
  hora_inicio: string;
  hora_fin: string;
  duracion_minutos: number;
  profesional_id: number | null;
}

export interface OrigenProgramacion {
  tipo: TipoOrigenProgramacion;
  tipo_label: string;
  id: number;
  sucursal_id: number;
  paciente_id: number;
  servicio_id: number;
  sala_id: number | null;
  fecha_inicio: string;
  fecha_fin: string;
  cantidad_sesiones: number | null;
  estado_efectivo: string;
  estado_efectivo_label: string;
  patron: PatronProgramacion | null;
  patron_disponible: boolean;
  patron_no_disponible_motivo: string | null;
  paciente: ResumenEntidad;
  servicio: ResumenEntidad;
  sala: ResumenEntidad | null;
  profesional: ResumenEntidad | null;
}

export interface TurnoResumen {
  id: number;
  fecha: string;
  hora_inicio: string;
  hora_fin: string;
  estado: string;
  estado_label: string;
  profesional_id: number | null;
  sala_id: number | null;
  lote_generacion_id: number | null;
}

export interface TurnoOrigen extends TurnoResumen {
  duracion_minutos: number;
  paciente: ResumenEntidad;
  servicio: ResumenEntidad;
  sala: ResumenEntidad | null;
  profesional: ResumenEntidad | null;
  cancelado_por_lote_id: number | null;
  cancelacion_origen: string | null;
  cancelado_en: string | null;
}

export interface PermisosProgramacion {
  ver: boolean;
  generar: boolean;
  regenerar: boolean;
  asignar_profesional: boolean;
  feriados: boolean;
}

export interface OrigenProgramacionData {
  origen: OrigenProgramacion;
  turnos_existentes: number;
  puede_generar: boolean;
  puede_regenerar: boolean;
}

export interface OrigenProgramacionResponse {
  data: OrigenProgramacionData;
  can: PermisosProgramacion;
}

export interface PreviewGeneracionData {
  origen: OrigenProgramacion;
  patron: PatronProgramacion;
  filas: FilaPreview[];
  hallazgos: HallazgoProgramacion[];
  resumen: ResumenGeneracion;
}

export interface PreviewGeneracionResponse {
  data: PreviewGeneracionData;
  can: PermisosProgramacion;
}

export interface PreviewRegeneracionData {
  origen: OrigenProgramacion;
  patron: PatronProgramacion;
  objetivo: number | null;
  cancelar: TurnoResumen[];
  preservar: TurnoResumen[];
  filas: FilaPreview[];
  hallazgos: HallazgoProgramacion[];
  resumen: ResumenGeneracion;
}

export interface PreviewRegeneracionResponse {
  data: PreviewRegeneracionData;
  can: PermisosProgramacion;
}

export interface LoteResultado {
  lote_id: number;
  tipo_origen: TipoOrigenProgramacion;
  origen_id: number;
  accion: ModoProgramacion;
  cantidad_creados: number;
  cantidad_cancelados: number;
  cantidad_preservados: number;
  turno_ids: number[];
  reutilizado: boolean;
}

export interface LoteResultadoResponse {
  data: LoteResultado;
  can: PermisosProgramacion;
}

export interface TurnosOrigenResponse {
  data: {
    origen: OrigenProgramacion;
    turnos: TurnoOrigen[];
    total: number;
  };
  can: PermisosProgramacion;
}

export interface Feriado {
  id: number;
  fecha: string;
  descripcion: string;
  sucursal_id: number | null;
  activo: boolean;
  created_at: string | null;
  updated_at: string | null;
}

export interface FeriadosResponse {
  data: Feriado[];
  can: PermisosProgramacion;
}

export interface ProfesionalAgendaOpcion {
  id: number;
  nombre_completo: string;
}

export interface OpcionesAgendaResponse {
  data: {
    profesionales: ProfesionalAgendaOpcion[];
  };
}

export interface ProgramacionApiError {
  status: number;
  message: string;
  code?: string;
  context?: Record<string, unknown>;
  errors?: Record<string, string[]>;
}

export interface GenerarTurnosPayload {
  clave_idempotencia: string;
  confirmar_advertencias: boolean;
  usar_profesional_origen: boolean;
  profesional_id?: number | null;
}

export interface PreviewGeneracionParams {
  usar_profesional_origen: boolean;
  profesional_id?: number | null;
}

export const PROGRAMACION_TEXTS = {
  pageTitle: 'Programación de turnos',
  tabProgramacion: 'Programación',
  tabFeriados: 'Feriados',
  originTitle: 'Entidad origen',
  originHint:
    'Ingresá el tipo y el identificador de la inscripción o paquete. También podés abrir esta pantalla desde la ficha de la entidad.',
  typeLabel: 'Tipo de origen',
  idLabel: 'Identificador',
  openWizard: 'Abrir asistente',
  generateTurns: 'Generar turnos',
  regenerateTurns: 'Regenerar turnos',
  existingTurns: 'Turnos generados',
  noExistingTurns: 'La entidad todavía no tiene turnos generados.',
  noOrigin: 'Seleccioná una entidad origen para ver su programación.',
  pattern: 'Patrón',
  period: 'Período',
  sessions: 'Sesiones',
  effectiveState: 'Estado',
  patient: 'Paciente',
  service: 'Servicio',
  room: 'Sala',
  professional: 'Profesional',
  unassigned: 'Sin asignar',
  notAvailable: 'No disponible',
  stepPattern: 'Patrón y período',
  stepPreview: 'Previsualización',
  stepProfessional: 'Profesional',
  stepConfirm: 'Confirmación',
  previewTitle: 'Previsualización',
  date: 'Fecha',
  day: 'Día',
  start: 'Inicio',
  end: 'Fin',
  status: 'Estado',
  findings: 'Hallazgos',
  willGenerate: 'Se generará',
  holiday: 'Feriado',
  past: 'Fecha pasada',
  omitted: 'Omitida',
  blocked: 'Bloqueo',
  warning: 'Advertencia',
  informative: 'Informativo',
  noRows: 'No hay fechas para mostrar en el período.',
  confirmWarnings: 'Confirmo las advertencias y deseo continuar.',
  summary: 'Resumen',
  generable: 'Generables',
  blockedCount: 'Bloqueados',
  warningCount: 'Advertencias',
  holidayCount: 'Feriados',
  pastCount: 'Fechas pasadas',
  omittedCount: 'Omitidas',
  missingCount: 'Faltantes',
  cancelCount: 'Se cancelarán',
  preserveCount: 'Se preservarán',
  attendedCount: 'Asistidos',
  absentCount: 'Faltados',
  pastUnmarkedCount: 'Programados pasados sin marcar',
  cancelTitle: 'Turnos que se cancelarán',
  preserveTitle: 'Turnos que se preservarán',
  noCancellations: 'No hay turnos futuros programados para cancelar.',
  back: 'Volver',
  next: 'Continuar',
  confirmGenerate: 'Confirmar generación',
  confirmRegenerate: 'Confirmar regeneración',
  processing: 'Procesando…',
  resultTitle: 'Resultado',
  created: 'Turnos creados',
  cancelled: 'Turnos cancelados',
  preserved: 'Turnos preservados',
  close: 'Cerrar',
  finish: 'Finalizar',
  holidayTitle: 'Feriados configurados',
  holidayHint:
    'Los feriados excluyen fechas de la generación. No se precarga ningún feriado: sin registros, la generación funciona igual.',
  holidayDate: 'Fecha',
  holidayDescription: 'Descripción',
  holidayScope: 'Alcance',
  global: 'Global',
  branch: 'Sucursal',
  addHoliday: 'Agregar feriado',
  deactivate: 'Desactivar',
  active: 'Activo',
  inactive: 'Inactivo',
  noHolidays: 'No hay feriados configurados.',
  year: 'Año',
  filter: 'Filtrar',
  generating: 'Generando turnos…',
  regeneratingNote:
    'La regeneración cancela solo los turnos futuros en estado Programado. Los turnos asistidos, faltados y pasados sin marcar se preservan.',
  patternUnavailable:
    'La generación de salas está pendiente: falta que Organización exponga los días y la hora de la sala.',
  requiredId: 'Ingresá un identificador válido.',
  successGenerated: 'Turnos generados correctamente.',
  successRegenerated: 'Turnos regenerados correctamente.',
  successHoliday: 'Feriado guardado correctamente.',
  successDeactivated: 'Feriado desactivado.',
  loadError: 'No se pudo cargar la programación.',
  previewError: 'No se pudo calcular la previsualización.',
  saveError: 'No se pudo completar la operación.',
  noPermission: 'No tenés permiso para realizar esta acción.',
  noBranch: 'No tenés una sucursal asignada para operar.',
  idempotencyReused: 'La operación ya fue aplicada con esta clave.',
} as const;

export const DIA_SEMANA_LABEL: Record<number, string> = {
  1: 'Lunes',
  2: 'Martes',
  3: 'Miércoles',
  4: 'Jueves',
  5: 'Viernes',
  6: 'Sábado',
  7: 'Domingo',
};
