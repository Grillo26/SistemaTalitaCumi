export type SeveridadCambio = 'bloqueo' | 'advertencia' | 'informativo';

export interface HallazgoCambio {
  tipo: string;
  severidad: SeveridadCambio;
  severidad_label: string;
  mensaje: string;
  fecha: string | null;
  hora_inicio: string | null;
  turno_id: number | null;
}

export interface MotivoTurno {
  id: number;
  codigo: string;
  nombre: string;
  origen: 'centro' | 'paciente' | null;
  origen_label?: string | null;
  descuenta_saldo: boolean | null;
  requiere_texto: boolean;
}

export interface PersonaCambio {
  id: number;
  nombre: string;
}

export interface SalaCambio {
  id: number;
  nombre: string;
}

export interface TurnoCambio {
  id: number;
  fecha: string;
  hora_inicio: string;
  hora_fin: string;
  duracion_minutos: number;
  estado: string;
  estado_label: string;
  turno_origen_id: number | null;
  paciente: PersonaCambio;
  servicio: PersonaCambio;
  sala: SalaCambio | null;
  profesional: PersonaCambio | null;
}

export interface OrigenCambio {
  tipo: string;
  id: number;
  fecha_inicio: string | null;
  fecha_fin: string | null;
  estado_efectivo: string | null;
}

export interface TurnoCambioDetalle {
  turno: TurnoCambio;
  origen: OrigenCambio | null;
  profesionales: PersonaCambio[];
  salas: SalaCambio[];
  motivos: {
    reprogramar: MotivoTurno[];
    cancelar: MotivoTurno[];
  };
  can: {
    reprogramar: boolean;
    cancelar: boolean;
    historial: boolean;
  };
}

export interface TurnoCambioDetalleResponse {
  data: TurnoCambioDetalle;
  can: CanCambios;
}

export interface CanCambios {
  ver: boolean;
  reprogramar: boolean;
  cancelar: boolean;
  cancelar_masivo: boolean;
  historial: boolean;
  reporte: boolean;
  exportar: boolean;
}

export interface PreviewReprogramacion {
  turno: TurnoCambio;
  nuevo: {
    fecha: string;
    hora_inicio: string;
    hora_fin: string;
    duracion_minutos: number;
    profesional_id: number | null;
    sala_id: number | null;
    servicio_id: number;
    sala: SalaCambio | null;
    profesional: PersonaCambio | null;
  };
  motivo: MotivoTurno;
  origen: OrigenCambio | null;
  hallazgos: HallazgoCambio[];
  advertencias: string[];
  puede_confirmar: boolean;
  requiere_confirmar_advertencias: boolean;
}

export interface PreviewReprogramacionResponse {
  data: PreviewReprogramacion;
  can: CanCambios;
}

export interface SaldoEfecto {
  aplicable: boolean;
  disponible: boolean;
  consume_saldo: boolean;
  pactadas?: number;
  realizadas?: number;
  faltadas?: number;
  canceladas?: number;
  canceladas_consumen?: number;
  saldo_actual?: number;
  saldo_resultante?: number;
  mensaje: string;
}

export interface PreviewCancelacion {
  turno: TurnoCambio;
  motivo: MotivoTurno;
  consume_saldo: boolean;
  saldo: SaldoEfecto;
  puede_confirmar: boolean;
}

export interface PreviewCancelacionResponse {
  data: PreviewCancelacion;
  can: CanCambios;
}

export interface ReprogramacionResultado {
  turno_original: TurnoCambio;
  turno_nuevo: TurnoCambio & { id: number };
  motivo: MotivoTurno;
  advertencias: string[];
}

export interface CancelacionResultado {
  turno: TurnoCambio;
  motivo: MotivoTurno;
  saldo: SaldoEfecto;
}

export interface HistorialItem {
  id: number;
  accion: string;
  accion_label: string;
  fecha: string | null;
  usuario: string | null;
  estado_anterior: string | null;
  estado_anterior_label: string | null;
  estado_nuevo: string | null;
  estado_nuevo_label: string | null;
  motivo_codigo: string | null;
  motivo_nombre: string | null;
  motivo_texto: string | null;
  turno_vinculado_id: number | null;
  consume_saldo: boolean | null;
  advertencias: string[];
  lote_id: string | null;
  critico: boolean;
  origen: string;
}

export interface HistorialTurnoData {
  turno_id: number;
  estado_actual: string;
  estado_actual_label: string;
  items: HistorialItem[];
}

export interface HistorialTurnoResponse {
  data: HistorialTurnoData;
  can: CanCambios;
}

export interface WhatsappCambio {
  disponible: boolean;
  telefono?: string;
  tutor_nombre?: string;
  whatsapp_url?: string;
  mensaje?: string;
}

export interface WhatsappCambioResponse {
  data: WhatsappCambio;
  can: CanCambios;
}

export interface OrigenCancelable {
  tipo: string;
  id: number;
  servicio: PersonaCambio;
  sala: SalaCambio | null;
  fecha_inicio: string | null;
  fecha_fin: string | null;
  estado_efectivo: string | null;
  total_turnos_futuros: number;
  elegibles: number;
  excluidos: number;
}

export interface TurnoElegibleMasivo {
  id: number;
  fecha: string;
  hora_inicio: string;
  hora_fin: string;
  estado: string;
  estado_label: string;
  paciente: PersonaCambio;
  servicio: PersonaCambio;
  sala: SalaCambio | null;
  profesional: PersonaCambio | null;
}

export interface TurnoExcluidoMasivo {
  turno_id: number;
  motivo: string;
  estado: string;
  estado_label: string;
  fecha: string;
  hora_inicio: string;
}

export interface PreviewCancelacionMasiva {
  paciente_id: number;
  entidades: Array<{ tipo: string; id: number }>;
  sucursales: number[];
  elegibles: TurnoElegibleMasivo[];
  excluidos: TurnoExcluidoMasivo[];
  cantidades: { elegibles: number; excluidos: number };
  motivo: MotivoTurno;
  consume_saldo: boolean;
  efecto_saldo: string;
  puede_confirmar: boolean;
}

export interface PreviewCancelacionMasivaResponse {
  data: PreviewCancelacionMasiva;
  can: CanCambios;
}

export interface CancelacionMasivaResultado {
  lote_id: string;
  cantidad: number;
  turno_ids: number[];
  entidades: Array<{ tipo: string; id: number }>;
  sucursales: number[];
  motivo: MotivoTurno;
  consume_saldo: boolean;
  excluidos: TurnoExcluidoMasivo[];
}

export interface ReporteCambioRow {
  id: number;
  tipo_cambio: 'reprogramado' | 'cancelado';
  accion: string;
  fecha_evento: string | null;
  usuario: string | null;
  sucursal_id: number;
  turno_id: number | null;
  turno_nuevo_id: number | null;
  fecha_turno: string | null;
  hora_inicio: string | null;
  fecha_nueva: string | null;
  hora_nueva: string | null;
  paciente: PersonaCambio | null;
  servicio: PersonaCambio | null;
  profesional: PersonaCambio | null;
  motivo: string | null;
  motivo_codigo: string | null;
  consume_saldo: boolean | null;
  lote_id: string | null;
  critico: boolean;
}

export interface ReporteCambiosData {
  data: ReporteCambioRow[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
  };
  conteos: { reprogramados: number; cancelados: number };
  filtros: Record<string, unknown>;
  can: { reporte: boolean; exportar: boolean };
}

export interface CambiosApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  context?: Record<string, unknown>;
}

export const CAMBIOS_TEXTS = {
  pageTitle: 'Cambios de turnos',
  tabMasiva: 'Cancelación masiva',
  tabReporte: 'Reporte de cambios',
  patient: 'Paciente',
  service: 'Servicio',
  room: 'Sala',
  professional: 'Profesional',
  unassigned: 'Sin asignar',
  sinSala: 'Sin sala',
  motive: 'Motivo',
  motivePlaceholder: 'Seleccioná un motivo',
  otherDetail: 'Detalle del motivo',
  otherRequired: 'Escribí el detalle del motivo.',
  date: 'Nueva fecha',
  startTime: 'Nueva hora',
  state: 'Estado',
  preview: 'Previsualizar',
  confirm: 'Confirmar',
  processing: 'Procesando…',
  cancel: 'Cancelar',
  close: 'Cerrar',
  reprogramTitle: 'Reprogramar turno',
  cancelTitle: 'Cancelar turno',
  historyTitle: 'Historial del turno',
  reprogramAction: 'Reprogramar',
  cancelAction: 'Cancelar',
  historyAction: 'Ver historial',
  previewTitle: 'Resumen del cambio',
  warnings: 'Advertencias',
  blocked: 'Bloqueos',
  confirmWarnings: 'Confirmo las advertencias y deseo continuar.',
  noPreview: 'Completá los datos y previsualizá el cambio.',
  successReprogram: 'Turno reprogramado correctamente.',
  successCancel: 'Turno cancelado correctamente.',
  successMass: 'Turnos cancelados correctamente.',
  whatsapp: 'Avisar por WhatsApp',
  whatsappUnavailable: 'Enlace de WhatsApp no disponible.',
  noHistory: 'El turno todavía no tiene cambios registrados.',
  historyChain: 'Turno vinculado',
  origin: 'Origen',
  period: 'Período',
  balance: 'Efecto en saldo',
  balanceUnavailable: 'Efecto en saldo no disponible.',
  saldoActual: 'Saldo actual',
  saldoResultante: 'Saldo resultante',
  massTitle: 'Cancelar turnos futuros',
  massHint: 'Seleccioná las inscripciones o paquetes del paciente y un motivo. La cancelación es todo o nada.',
  searchPatient: 'Buscar paciente',
  origins: 'Orígenes con turnos futuros',
  selectOrigins: 'Seleccioná al menos un origen.',
  noOrigins: 'El paciente no tiene turnos futuros en tus sucursales.',
  eligible: 'Se cancelarán',
  excluded: 'Se excluyen',
  total: 'Total',
  confirmMass: 'Confirmar cancelación',
  massConfirm: 'Confirmo la cancelación de los turnos listados.',
  noEligible: 'No hay turnos elegibles para cancelar.',
  reportTitle: 'Reporte de cambios',
  from: 'Desde',
  to: 'Hasta',
  branch: 'Sucursal',
  allBranches: 'Todas',
  changeType: 'Tipo de cambio',
  reprogrammed: 'Reprogramados',
  cancelled: 'Cancelados',
  filter: 'Filtrar',
  clear: 'Limpiar',
  exportPdf: 'Exportar PDF',
  exportExcel: 'Exportar Excel',
  exportUnavailable: 'Exportación pendiente de infraestructura.',
  noResults: 'Sin resultados para los filtros aplicados.',
  eventDate: 'Fecha del evento',
  turnDate: 'Turno original',
  newDate: 'Nueva fecha',
  user: 'Usuario',
  motivo: 'Motivo',
  actions: 'Acciones',
  page: 'Página',
  of: 'de',
  previous: 'Anterior',
  next: 'Siguiente',
  reasonRequired: 'Seleccioná un motivo.',
  unavailable: 'No disponible',
} as const;

export const ESTADO_CAMBIO_LABEL: Record<string, string> = {
  programado: 'Programado',
  asistio: 'Asistió',
  falto: 'Faltó',
  cancelado: 'Cancelado',
  reprogramado: 'Reprogramado',
};

export function fechaLegible(fecha: string | null | undefined): string {
  if (!fecha) {
    return '—';
  }

  const [anio, mes, dia] = fecha.split('-');

  return `${dia}/${mes}/${anio}`;
}

export function fechaHoraLegible(fecha: string | null | undefined): string {
  if (!fecha) {
    return '—';
  }

  const date = new Date(fecha);

  return Number.isNaN(date.getTime())
    ? fecha
    : date.toLocaleString('es-BO', { dateStyle: 'short', timeStyle: 'short' });
}
