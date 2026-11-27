import type { AgendaVista, EstadoTurno } from './agenda-semanal.models';

export const AGENDA_TEXTS = {
  title: 'Agenda semanal',
  subtitle:
    'Consulta los turnos de la sucursal por día y hora. La agenda es de solo lectura; las acciones se ejecutan desde el detalle del turno.',
  branchLabel: 'Sucursal',
  weekPrevious: 'Semana anterior',
  weekNext: 'Semana siguiente',
  today: 'Hoy',
  filters: 'Filtros',
  clearFilters: 'Limpiar filtros',
  area: 'Área',
  room: 'Sala',
  professional: 'Profesional',
  service: 'Servicio',
  state: 'Estado',
  allAreas: 'Todas las áreas',
  allRooms: 'Todas las salas',
  allProfessionals: 'Todos los profesionales',
  allServices: 'Todos los servicios',
  allStates: 'Todos los estados',
  unassigned: 'Sin asignar',
  noRoom: 'Sin sala',
  view: 'Vista',
  viewWeek: 'Semana',
  viewProfessional: 'Por profesional',
  viewRoom: 'Por sala',
  exportPdf: 'Exportar PDF',
  print: 'Imprimir',
  loading: 'Cargando agenda...',
  emptyWeek: 'Sin turnos para esta semana',
  emptyProfessional: 'Sin turnos para este profesional',
  noBranchesTitle: 'Sin sucursales asignadas',
  noBranchesMessage:
    'No tenés sucursales asignadas. Contactá al Superadmin para que te asigne al menos una.',
  loadErrorTitle: 'No se pudo cargar la agenda',
  detailTitle: 'Detalle del turno',
  detailLoading: 'Cargando detalle...',
  detailError: 'No se pudo cargar el detalle del turno',
  close: 'Cerrar',
  patient: 'Paciente',
  date: 'Fecha',
  time: 'Hora',
  actions: 'Acciones',
  actionsUnavailable:
    'Las acciones de asistencia, reprogramación y cancelación se habilitarán en los próximos submódulos.',
  noActionsForUser: 'No tenés acciones disponibles para este turno.',
  noAvailable: 'No disponible',
  hiddenDaysTitle: 'Turnos en días no visibles',
  partialDataTitle: 'Algunos datos no están disponibles',
  partialDataMessage:
    'La agenda se cargó, pero uno o más datos operativos no pudieron resolverse. Los campos afectados se muestran como “No disponible”.',
  exportUnavailable: 'La exportación a PDF todavía no está disponible.',
  printHint: 'Para guardar como PDF, elegí “Guardar como PDF” en el diálogo de impresión.',
  summaryTitle: 'Resumen de la semana',
  summaryTotal: 'Total',
  summaryBase: 'Turnos filtrados visibles',
  noTurnsInGroup: 'Sin turnos',
  weekOf: 'Semana del',
  generatedAt: 'Actualizado',
  refreshNow: 'Actualizar',
} as const;

export const ESTADO_OPTIONS: { value: EstadoTurno; label: string }[] = [
  { value: 'programado', label: 'Programado' },
  { value: 'asistio', label: 'Asistió' },
  { value: 'falto', label: 'Faltó' },
  { value: 'cancelado', label: 'Cancelado' },
  { value: 'reprogramado', label: 'Reprogramado' },
];

export const ESTADO_LABELS: Record<EstadoTurno, string> = {
  programado: 'Programado',
  asistio: 'Asistió',
  falto: 'Faltó',
  cancelado: 'Cancelado',
  reprogramado: 'Reprogramado',
};

export type EstadoBadgeColor = 'primary' | 'success' | 'error' | 'warning' | 'gray' | 'info';

export const ESTADO_BADGES: Record<
  EstadoTurno,
  { color: EstadoBadgeColor; icon: string }
> = {
  programado: { color: 'info', icon: '•' },
  asistio: { color: 'success', icon: '✓' },
  falto: { color: 'error', icon: '✕' },
  cancelado: { color: 'gray', icon: '—' },
  reprogramado: { color: 'warning', icon: '↻' },
};

export const VISTA_OPTIONS: { value: AgendaVista; label: string }[] = [
  { value: 'semana', label: AGENDA_TEXTS.viewWeek },
  { value: 'profesional', label: AGENDA_TEXTS.viewProfessional },
  { value: 'sala', label: AGENDA_TEXTS.viewRoom },
];

export const ESTADO_VALUES: EstadoTurno[] = ESTADO_OPTIONS.map((option) => option.value);
