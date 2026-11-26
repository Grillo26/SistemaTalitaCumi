export type EstadoPaquete = 'activo' | 'en_pausa' | 'vencido' | 'renovado' | 'retirado';

export type SortDirection = 'asc' | 'desc';

export type PaqueteSort =
  'fecha_inicio' | 'fecha_fin' | 'estado' | 'monto_esperado' | 'cantidad_sesiones';

export type FichaTab = 'resumen' | 'consumo' | 'pago' | 'comprobante';

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

export interface ServicioResumen {
  id: number;
  nombre: string;
  tipo_servicio?: string | null;
}

export interface CategoriaResumen {
  id: number;
  nombre: string;
}

export interface ProfesionalResumen {
  id: number;
  nombre: string;
}

export interface PaqueteRowCan {
  view: boolean;
  update: boolean;
  renew: boolean;
  withdraw: boolean;
}

export interface PaqueteRow {
  id: number;
  paciente: PacienteResumen;
  tutor: Pick<TutorResumen, 'id' | 'nombre_completo'>;
  sucursal: SucursalResumen;
  servicio: ServicioResumen;
  categoria: CategoriaResumen;
  profesional: ProfesionalResumen | null;
  cantidad_sesiones: number;
  dias_semana: number[];
  dias_semana_labels: string[];
  hora: string;
  duracion_minutos: number;
  fecha_inicio: string;
  fecha_fin: string;
  monto_esperado: string;
  estado: EstadoPaquete;
  estado_mostrado: EstadoPaquete;
  estado_label: string;
  paquete_origen_id: number | null;
  created_at: string | null;
  updated_at: string | null;
  can: PaqueteRowCan;
}

export interface PaqueteRetiro {
  fecha_baja: string | null;
  motivo_codigo: string | null;
  motivo_detalle: string | null;
  registrado_en: string | null;
  usuario_id: number | null;
  usuario_nombre: string | null;
}

export interface PaqueteVinculo {
  id: number;
  fecha_inicio: string;
  fecha_fin: string;
  cantidad_sesiones: number;
  estado: EstadoPaquete;
  estado_label: string;
}

export interface PaqueteAbilities {
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
  consumo_disponible: boolean;
  turnos_futuros_disponible: boolean;
}

export const EMPTY_ABILITIES: PaqueteAbilities = {
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
  consumo_disponible: false,
  turnos_futuros_disponible: false,
};

export interface PaqueteDetalle extends Omit<PaqueteRow, 'can'> {
  paciente: PacienteResumen & {
    fecha_nacimiento: string;
    edad_anios: number;
    estado_label: string;
  };
  tutor: TutorResumen;
  tarifa_id: number;
  observaciones: string | null;
  origen: PaqueteVinculo | null;
  renovada_por: PaqueteVinculo | null;
  retiro: PaqueteRetiro | null;
  can: PaqueteAbilities;
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
  can: PaqueteAbilities;
}

export interface DetailResponse<T> {
  data: T;
  can: PaqueteAbilities;
}

export interface TarifaAplicable {
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

export interface MontoPreview {
  tarifa: TarifaAplicable | null;
  tarifa_disponible: boolean;
  cantidad_sesiones: number;
  monto_sesion: string | null;
  monto_total: string | null;
  sesiones_estimadas: number | null;
  advertencia: string | null;
  message: string | null;
}

export interface MontoPreviewResponse {
  data: MontoPreview;
  can: PaqueteAbilities;
}

export interface RenovacionPreview {
  paquete_origen_id: number;
  fecha_inicio: string;
  fecha_fin: string;
  cantidad_sesiones: number;
  dias_semana: number[];
  hora: string;
  duracion_minutos: number;
  profesional_id: number | null;
  servicio: ServicioResumen;
  categoria: CategoriaResumen;
  sucursal: SucursalResumen;
  tutor: TutorResumen;
  tarifa: TarifaAplicable | null;
  tarifa_disponible: boolean;
  monto_sesion: string | null;
  monto_total: string | null;
  sesiones_estimadas: number | null;
  advertencia: string | null;
}

export interface ConsumoPaquete {
  paquete_id: number;
  pactadas: number;
  realizadas: number;
  faltadas: number;
  canceladas: number;
  saldo: number;
}

export interface PagoEstado {
  estado: string;
  monto_esperado: string;
  monto_pagado: string;
  saldo: string;
  pagos_registrados: number;
  actualizado_en: string | null;
}

export interface TurnosFuturosResumen {
  cantidad: number;
  fechas: string[];
}

export interface PaqueteOpciones {
  defaults: {
    cantidad_sesiones_default: number;
    duracion_minutos_default: number;
  };
}

export interface ComprobantePaqueteData {
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
  paquete: {
    id: number;
    cantidad_sesiones: number;
    dias_semana: number[];
    dias_semana_labels: string[];
    hora: string;
    duracion_minutos: number;
    fecha_inicio: string;
    fecha_fin: string;
    monto_esperado: string;
    estado: EstadoPaquete;
    estado_label: string;
    paciente: { id: number; codigo: string | null; nombre_completo: string };
    tutor: { id: number; nombre_completo: string; parentesco: string | null };
    servicio: ServicioResumen;
    categoria: CategoriaResumen;
    sucursal: SucursalResumen;
    profesional: ProfesionalResumen | null;
  };
}

export interface PaqueteListParams {
  search?: string;
  sucursal_id?: number | '';
  paciente_id?: number | '';
  servicio_id?: number | '';
  profesional_id?: number | '';
  periodo_desde?: string;
  periodo_hasta?: string;
  estado?: EstadoPaquete | '';
  page?: number;
  per_page?: number;
  sort?: PaqueteSort;
  direction?: SortDirection;
}

export interface PaquetePayload {
  paciente_id: number;
  tutor_responsable_id: number;
  sucursal_id: number;
  servicio_id: number;
  profesional_id: number | null;
  categoria_tarifa_id: number;
  cantidad_sesiones: number;
  dias_semana: number[];
  hora: string;
  duracion_minutos: number;
  fecha_inicio: string;
  fecha_fin: string;
  observaciones: string | null;
  tarifa_id_esperada?: number | null;
  confirmar_tarifa_cambio?: boolean;
}

export interface PaqueteUpdatePayload {
  profesional_id: number | null;
  dias_semana: number[];
  hora: string;
  observaciones: string | null;
}

export interface RenovacionPayload {
  fecha_inicio: string;
  fecha_fin: string;
  cantidad_sesiones: number;
  dias_semana: number[];
  hora: string;
  duracion_minutos: number;
  profesional_id: number | null;
  observaciones: string | null;
  tarifa_id_esperada?: number | null;
  confirmar_tarifa_cambio?: boolean;
}

export interface BajaPaquetePayload {
  motivo: string;
  motivo_detalle: string | null;
  fecha_baja: string;
}

export interface PaqueteApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  conflict?: Record<string, unknown>;
  tarifa_aplicable?: TarifaAplicable;
  modulo?: string;
}

export const DIAS_SEMANA_OPTIONS = [
  { value: 1, label: 'Lunes' },
  { value: 2, label: 'Martes' },
  { value: 3, label: 'Miércoles' },
  { value: 4, label: 'Jueves' },
  { value: 5, label: 'Viernes' },
  { value: 6, label: 'Sábado' },
];

export const MOTIVO_BAJA_PAQUETE_OPTIONS = [
  { value: 'retiro_paciente', label: 'Retiro del paciente' },
  { value: 'cambio_servicio', label: 'Cambio de servicio' },
  { value: 'cambio_profesional', label: 'Cambio de profesional' },
  { value: 'otro', label: 'Otro' },
];

export const ESTADO_PAQUETE_OPTIONS = [
  { value: '', label: 'Todos los estados' },
  { value: 'activo', label: 'Activos' },
  { value: 'vencido', label: 'Vencidos' },
  { value: 'retirado', label: 'Retirados' },
  { value: 'en_pausa', label: 'En pausa' },
  { value: 'renovado', label: 'Renovados' },
];

export const PAQUETES_TEXTS = {
  title: 'Paquetes de sesiones individuales',
  listTitle: 'Paquetes registrados',
  subtitle: 'Altas, renovaciones y bajas de paquetes de terapia individual.',
  newPackage: 'Nuevo paquete',
  filters: 'Filtros',
  allBranches: 'Todas las sucursales',
  allServices: 'Todos los servicios',
  allProfessionals: 'Todos los profesionales',
  clearFilters: 'Limpiar filtros',
  filter: 'Filtrar',
  loading: 'Cargando paquetes...',
  empty: 'No hay paquetes para los filtros seleccionados.',
  patient: 'Paciente',
  service: 'Servicio',
  professional: 'Profesional',
  period: 'Período',
  pattern: 'Días y hora',
  sessions: 'Sesiones',
  amount: 'Monto esperado',
  state: 'Estado',
  actions: 'Acciones',
  view: 'Ver ficha',
  edit: 'Editar',
  renew: 'Renovar',
  withdraw: 'Dar de baja',
  save: 'Guardar paquete',
  saving: 'Guardando...',
  cancel: 'Cancelar',
  close: 'Cerrar',
  observations: 'Observaciones',
  observationsHint: 'Máximo 500 caracteres.',
  startDate: 'Fecha de inicio',
  endDate: 'Fecha de fin',
  quantity: 'Cantidad de sesiones',
  duration: 'Duración por sesión (minutos)',
  hour: 'Hora de la sesión',
  days: 'Días de la semana',
  expectedAmount: 'Monto esperado',
  amountHint: 'El monto total lo calcula el sistema a partir de la tarifa vigente.',
  calculateAmount: 'Calcular monto',
  calculating: 'Calculando...',
  tariffChanged: 'La tarifa vigente cambió',
  tariffChangedDetail:
    'El monto calculado cambió. Revisá el nuevo monto y confirmá para continuar.',
  confirmTariff: 'Confirmar nueva tarifa',
  patientInactive: 'Debe reactivar al paciente antes de crear un paquete.',
  selectPatient: 'Seleccioná un paciente.',
  selectTutor: 'Seleccioná un tutor responsable.',
  selectBranch: 'Seleccioná una sucursal.',
  selectService: 'Seleccioná un servicio de terapia individual.',
  selectCategory: 'Seleccioná una categoría.',
  selectDays: 'Seleccioná al menos un día.',
  noTariff: 'No hay una tarifa vigente para la fecha seleccionada.',
  duplicate: 'Ya existe un paquete activo del paciente para ese servicio con un período solapado.',
  editHint:
    'Solo podés cambiar profesional, días, hora y observaciones. Para cambiar fechas, cantidad o tarifa usá la renovación.',
  retiro: 'Datos de la baja',
  retiroDate: 'Fecha de baja',
  retiroMotive: 'Motivo',
  retiroDetail: 'Detalle del motivo',
  retiroOtherRequired: 'El motivo "Otro" requiere un detalle.',
  consumo: 'Consumo',
  payment: 'Estado de pago',
  receipt: 'Comprobante',
  print: 'Imprimir comprobante',
  noPayment: 'Pagos y caja no disponible todavía.',
  noConsumo: 'Agenda y turnos no disponible todavía.',
  noFutureTurns: 'La verificación de turnos futuros no está disponible.',
  futureTurnsWarning: 'Hay turnos futuros programados. Sugerimos cancelarlos antes de la baja.',
  noPdf: 'PDF pendiente de infraestructura.',
  noExport: 'Exportación PDF/Excel pendiente de infraestructura.',
  origin: 'Paquete de origen',
  renewedBy: 'Renovado por',
  noOrigin: 'Sin vínculo de renovación.',
  requestError: 'No se pudo completar la operación.',
  export: 'Exportar',
  exportEmpty: 'Exportación no disponible.',
  resumen: 'Resumen',
  schedule: 'Días y horario',
  student: 'Paciente',
  tutor: 'Tutor responsable',
  branch: 'Sucursal',
  category: 'Categoría',
  totalSessions: 'Sesiones contratadas',
  estimated: 'Sesiones estimadas en el período',
  balance: 'Saldo',
  realized: 'Realizadas',
  missed: 'Faltadas',
  cancelled: 'Canceladas',
  tariff: 'Tarifa aplicada',
  amountPerSession: 'Monto por sesión',
  renewalTitle: 'Renovar paquete',
  renewalSubtitle: 'Se propone el período siguiente con la misma duración.',
  renewalConfirm: 'Confirmar renovación',
  renewalNoTransfer:
    'Las sesiones no consumidas del paquete anterior no se trasladan al nuevo período.',
  withdrawalTitle: 'Dar de baja el paquete',
  withdrawalConfirm: 'Confirmar baja',
  withdrawalHint: 'La baja conserva el historial y no elimina el paquete.',
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

export function weekdaysLabel(dias: number[]): string {
  return dias
    .map((dia) => DIAS_SEMANA_OPTIONS.find((option) => option.value === dia)?.label ?? String(dia))
    .join(', ');
}
