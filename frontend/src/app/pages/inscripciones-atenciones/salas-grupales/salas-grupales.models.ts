export type EstadoInscripcion = 'activa' | 'en_pausa' | 'vencida' | 'renovada' | 'retirada';

export type EstadoMostrado = EstadoInscripcion;

export type SortDirection = 'asc' | 'desc';

export interface PacienteResumen {
  id: number;
  codigo: string | null;
  nombre_completo: string;
  estado: string;
  estado_label?: string;
}

export interface TutorResumen {
  id: number;
  nombre_completo: string;
  parentesco?: string | null;
  es_principal?: boolean;
  activo?: boolean;
  telefono?: string | null;
  telefono_enmascarado?: string | null;
  whatsapp_disponible?: boolean;
  contacto_protegido?: boolean;
}

export interface SucursalResumen {
  id: number;
  nombre: string;
}

export interface SalaResumen {
  id: number;
  nombre: string;
  tipo_sala: string | null;
  turno: string | null;
}

export interface ServicioResumen {
  id: number;
  nombre: string;
  tipo_servicio?: string | null;
}

export interface CategoriaResumen {
  id: number;
  nombre: string;
}

export interface InscripcionRowCan {
  view: boolean;
  update: boolean;
  renew: boolean;
  withdraw: boolean;
}

export interface InscripcionRow {
  id: number;
  paciente: PacienteResumen;
  tutor: TutorResumen;
  sucursal: SucursalResumen;
  sala: SalaResumen;
  servicio: ServicioResumen;
  categoria: CategoriaResumen;
  fecha_inicio: string;
  fecha_fin: string;
  monto_esperado: string;
  estado: EstadoInscripcion;
  estado_mostrado: EstadoMostrado;
  estado_label: string;
  inscripcion_origen_id: number | null;
  created_at: string | null;
  updated_at: string | null;
  can: InscripcionRowCan;
}

export interface InscripcionRetiro {
  fecha_baja: string | null;
  motivo_codigo: string | null;
  motivo_detalle: string | null;
  registrado_en: string | null;
  usuario_id: number | null;
  usuario_nombre: string | null;
}

export interface InscripcionVinculo {
  id: number;
  fecha_inicio: string;
  fecha_fin: string;
  estado: EstadoInscripcion;
  estado_label: string;
}

export interface InscripcionAbilities {
  view: boolean;
  create: boolean;
  update: boolean;
  renew: boolean;
  withdraw: boolean;
  export: boolean;
  print: boolean;
  pdf: boolean;
  view_tutor_contact: boolean;
  pago_disponible: boolean;
  asistencia_disponible: boolean;
  turnos_futuros_disponible: boolean;
}

export const EMPTY_ABILITIES: InscripcionAbilities = {
  view: false,
  create: false,
  update: false,
  renew: false,
  withdraw: false,
  export: false,
  print: false,
  pdf: false,
  view_tutor_contact: false,
  pago_disponible: false,
  asistencia_disponible: false,
  turnos_futuros_disponible: false,
};

export interface InscripcionDetalle extends Omit<InscripcionRow, 'can'> {
  paciente: PacienteResumen & {
    fecha_nacimiento: string;
    edad_anios: number;
    estado_label: string;
  };
  tutor: TutorResumen;
  tarifa_id: number;
  observaciones: string | null;
  origen: InscripcionVinculo | null;
  renovada_por: InscripcionVinculo | null;
  retiro: InscripcionRetiro | null;
  can: InscripcionAbilities;
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface PaginatedResponse<T> {
  data: T[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: PaginationMeta;
  can: InscripcionAbilities;
}

export interface DetailResponse<T> {
  data: T;
  can: InscripcionAbilities;
}

export interface PreviewMonto {
  tarifa_id: number;
  servicio_id: number;
  servicio_nombre: string | null;
  categoria_tarifa_id: number;
  categoria_nombre: string | null;
  sucursal_id: number | null;
  sucursal_nombre: string | null;
  monto: string;
  vigente_desde: string;
  vigente_hasta: string | null;
  origen: 'sucursal' | 'global';
}

export interface PreviewMontoResponse {
  data: PreviewMonto | null;
  tarifa_disponible: boolean;
  message: string | null;
  can: InscripcionAbilities;
}

export interface RenovacionPreview {
  inscripcion_origen_id: number;
  fecha_inicio: string;
  fecha_fin: string;
  servicio: ServicioResumen;
  categoria: CategoriaResumen;
  sala: SalaResumen;
  sucursal: SucursalResumen;
  tutor: TutorResumen;
  tarifa: PreviewMonto | null;
  tarifa_disponible: boolean;
}

export interface PagoEstado {
  estado: string;
  monto_esperado: string;
  monto_pagado: string;
  saldo: string;
  pagos_registrados: number;
  actualizado_en: string | null;
}

export interface AsistenciaResumen {
  asistencias: number;
  faltas: number;
  cancelados: number;
  total: number;
  periodo_desde: string | null;
  periodo_hasta: string | null;
}

export interface TurnosFuturosResumen {
  cantidad: number;
  fechas: string[];
}

export interface ComprobanteData {
  centro: {
    nombre: string;
    direccion: string;
    telefono: string;
    logo_url: string | null;
  };
  moneda: {
    codigo: string;
    simbolo: string;
  };
  leyenda_no_factura: string;
  fecha_emision: string;
  inscripcion: {
    id: number;
    fecha_inicio: string;
    fecha_fin: string;
    monto_esperado: string;
    estado: EstadoInscripcion;
    estado_label: string;
    paciente: { id: number; codigo: string | null; nombre_completo: string };
    tutor: TutorResumen;
    sala: SalaResumen;
    servicio: ServicioResumen;
    categoria: CategoriaResumen;
    sucursal: SucursalResumen;
  };
}

export interface InscripcionListParams {
  search?: string;
  sucursal_id?: number | '';
  sala_id?: number | '';
  paciente_id?: number | '';
  periodo_mes?: number | '';
  periodo_anio?: number | '';
  estado?: EstadoInscripcion | '';
  page?: number;
  per_page?: number;
  sort?: 'fecha_inicio' | 'fecha_fin' | 'estado' | 'monto_esperado' | 'created_at';
  direction?: SortDirection;
}

export interface InscripcionPayload {
  paciente_id: number;
  tutor_responsable_id: number;
  sucursal_id: number;
  sala_id: number;
  servicio_id: number;
  categoria_tarifa_id: number;
  fecha_inicio: string;
  fecha_fin: string;
  observaciones: string | null;
  tarifa_id_esperada?: number | null;
  confirmar_tarifa_cambio?: boolean;
}

export interface InscripcionUpdatePayload {
  fecha_inicio?: string;
  fecha_fin?: string;
  servicio_id?: number;
  categoria_tarifa_id?: number;
  observaciones?: string | null;
  tarifa_id_esperada?: number | null;
  confirmar_tarifa_cambio?: boolean;
}

export interface RenovacionPayload {
  fecha_inicio?: string;
  fecha_fin?: string;
  servicio_id?: number;
  categoria_tarifa_id?: number;
  observaciones?: string | null;
  tarifa_id_esperada?: number | null;
  confirmar_tarifa_cambio?: boolean;
}

export interface BajaPayload {
  motivo: string;
  motivo_detalle: string | null;
  fecha_baja: string;
}

export interface InscripcionApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  conflict?: Record<string, unknown>;
  tarifa_aplicable?: PreviewMonto;
  modulo?: string;
}

export const MOTIVO_BAJA_OPTIONS = [
  { value: 'retiro_paciente', label: 'Retiro del paciente' },
  { value: 'cambio_sala', label: 'Cambio de sala' },
  { value: 'cambio_sucursal', label: 'Cambio de sucursal' },
  { value: 'otro', label: 'Otro' },
];

export const ESTADO_OPTIONS = [
  { value: '', label: 'Todos los estados' },
  { value: 'activa', label: 'Activas' },
  { value: 'vencida', label: 'Vencidas' },
  { value: 'retirada', label: 'Retiradas' },
];

export const MONTH_OPTIONS = [
  { value: '', label: 'Todos los meses' },
  { value: '1', label: 'Enero' },
  { value: '2', label: 'Febrero' },
  { value: '3', label: 'Marzo' },
  { value: '4', label: 'Abril' },
  { value: '5', label: 'Mayo' },
  { value: '6', label: 'Junio' },
  { value: '7', label: 'Julio' },
  { value: '8', label: 'Agosto' },
  { value: '9', label: 'Septiembre' },
  { value: '10', label: 'Octubre' },
  { value: '11', label: 'Noviembre' },
  { value: '12', label: 'Diciembre' },
];

export const SALAS_GRUPALES_TEXTS = {
  title: 'Inscripciones a salas grupales',
  subtitle: 'Altas, renovaciones y bajas de inscripciones mensuales a salas grupales.',
  newInscription: 'Nueva inscripción',
  filters: 'Filtros',
  searchPatient: 'Buscar paciente',
  allBranches: 'Todas las sucursales',
  allRooms: 'Todas las salas',
  period: 'Período',
  state: 'Estado',
  clearFilters: 'Limpiar filtros',
  loading: 'Cargando inscripciones...',
  empty: 'No hay inscripciones para los filtros seleccionados.',
  patient: 'Paciente',
  tutor: 'Tutor responsable',
  room: 'Sala',
  service: 'Servicio',
  category: 'Categoría',
  amount: 'Monto esperado',
  actions: 'Acciones',
  view: 'Ver ficha',
  edit: 'Editar',
  renew: 'Renovar',
  withdraw: 'Dar de baja',
  save: 'Guardar inscripción',
  saving: 'Guardando...',
  cancel: 'Cancelar',
  close: 'Cerrar',
  observations: 'Observaciones',
  observationsHint: 'Máximo 500 caracteres.',
  startDate: 'Fecha de inicio',
  endDate: 'Fecha de fin',
  expectedAmount: 'Monto esperado',
  calculateAmount: 'Calcular monto',
  calculating: 'Calculando...',
  tariffChanged: 'La tarifa vigente cambió',
  tariffChangedDetail:
    'El monto calculado cambió. Revisá el nuevo monto y confirmá para continuar.',
  confirmTariff: 'Confirmar nueva tarifa',
  patientInactive: 'Debe reactivar al paciente antes de inscribir.',
  noTariff: 'No hay una tarifa vigente para la fecha seleccionada.',
  duplicate: 'Ya existe una inscripción activa del paciente en esa sala para el período.',
  retiro: 'Datos de la baja',
  retiroDate: 'Fecha de baja',
  retiroMotive: 'Motivo',
  retiroDetail: 'Detalle del motivo',
  retiroOtherRequired: 'El motivo "Otro" requiere un detalle.',
  payment: 'Estado de pago',
  attendance: 'Asistencia',
  receipt: 'Comprobante',
  print: 'Imprimir comprobante',
  noPayment: 'Pagos y caja no disponible todavía.',
  noAttendance: 'Agenda y turnos no disponible todavía.',
  noFutureTurns: 'La verificación de turnos futuros no está disponible.',
  futureTurnsWarning: 'Hay turnos futuros programados. Sugerimos cancelarlos antes de la baja.',
  noPdf: 'PDF pendiente de infraestructura.',
  noExport: 'Exportación PDF/Excel pendiente de infraestructura.',
  origin: 'Inscripción de origen',
  renewedBy: 'Renovada por',
  noOrigin: 'Sin vínculo de renovación.',
  periodLabel: 'Período',
  invalidDates: 'La fecha de fin no puede ser anterior al inicio.',
  dateRange: 'El período no puede superar un mes.',
  selectPatient: 'Seleccioná un paciente.',
  selectTutor: 'Seleccioná un tutor responsable.',
  selectBranch: 'Seleccioná una sucursal.',
  selectRoom: 'Seleccioná una sala.',
  selectService: 'Seleccioná un servicio de sala grupal.',
  selectCategory: 'Seleccioná una categoría.',
  requestError: 'No se pudo completar la operación.',
  patientLink: 'Ver ficha del paciente',
  whatsapp: 'Contactar por WhatsApp',
};

export function formatBs(value: string | null | undefined): string {
  return `Bs ${value ?? '0.00'}`;
}

export function formatDate(value: string | null | undefined): string {
  if (!value) {
    return '—';
  }

  const [year, month, day] = value.split('-');
  return `${day}/${month}/${year}`;
}
