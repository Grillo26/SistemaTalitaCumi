export type TipoEntidadVigencia = 'inscripcion_sala' | 'paquete_sesiones';
export type EstadoVigencia = 'vigente' | 'por_vencer' | 'vencido' | 'renovado' | 'retirado';
export type VigenciaTab = 'panel' | 'reportes' | 'configuracion';
export type ExportFormat = 'pdf' | 'excel';

export interface VigenciaAbilities {
  view: boolean;
  reports: boolean;
  export: boolean;
  config: boolean;
  view_tutor_contact: boolean;
  renew: Record<TipoEntidadVigencia, boolean>;
  view_types: Record<TipoEntidadVigencia, boolean>;
}

export interface PacienteVigenciaResumen {
  id: number;
  codigo: string | null;
  nombre_completo: string;
  estado: string;
  estado_label: string;
}

export interface TutorVigenciaResumen {
  id: number;
  nombre_completo: string;
  telefono: string | null;
  telefono_enmascarado: string | null;
  whatsapp_disponible: boolean;
}

export interface SucursalVigenciaResumen {
  id: number;
  nombre: string;
}

export interface ServicioVigenciaResumen {
  id: number;
  nombre: string;
}

export interface ReferenciaVigenciaResumen {
  id: number;
  nombre?: string;
  nombre_completo?: string;
}

export interface VigenciaRow {
  tipo_entidad: TipoEntidadVigencia;
  tipo_entidad_label: string;
  id: number;
  paciente: PacienteVigenciaResumen;
  tutor: TutorVigenciaResumen;
  sucursal: SucursalVigenciaResumen;
  servicio: ServicioVigenciaResumen;
  sala: ReferenciaVigenciaResumen | null;
  profesional: ReferenciaVigenciaResumen | null;
  cantidad_sesiones: number | null;
  fecha_inicio: string;
  fecha_fin: string;
  dias_para_vencer: number;
  monto_esperado: string;
  estado_vigencia: EstadoVigencia;
  estado_vigencia_label: string;
  renovada: boolean;
  retirada: boolean;
  renovable: boolean;
  motivo_no_renovable: string | null;
  motivo_no_renovable_label: string | null;
  saldo: number | null;
  saldo_disponible: boolean;
  can: {
    view: boolean;
    renew: boolean;
    whatsapp: boolean;
  };
}

export interface PaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface PaginatedVigencias {
  data: VigenciaRow[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: PaginationMeta;
  can: VigenciaAbilities;
}

export interface TarifaVigente {
  tarifa_id: number;
  servicio_id?: number;
  servicio_nombre?: string | null;
  categoria_tarifa_id?: number;
  categoria_nombre?: string | null;
  sucursal_id?: number | null;
  sucursal_nombre?: string | null;
  monto?: string;
  vigente_desde?: string;
  vigente_hasta?: string | null;
  origen?: 'sucursal' | 'global';
}

export interface RenovacionPreview {
  tipo_entidad: TipoEntidadVigencia;
  tipo_entidad_label: string;
  entidad_id: number;
  entidad_origen_id: number;
  paciente: PacienteVigenciaResumen | null;
  tutor: TutorVigenciaResumen;
  sucursal: SucursalVigenciaResumen;
  servicio: ServicioVigenciaResumen;
  categoria: { id: number; nombre: string };
  sala: ReferenciaVigenciaResumen | null;
  profesional: number | null;
  fecha_inicio: string;
  fecha_fin: string;
  cantidad_sesiones: number | null;
  dias_semana?: number[];
  hora?: string;
  duracion_minutos?: number;
  tarifa_anterior: { tarifa_id: number; monto: string };
  tarifa_vigente: TarifaVigente | null;
  monto_anterior: string;
  monto_vigente: string | null;
  monto_sesion?: string | null;
  sesiones_estimadas?: number | null;
  advertencia?: string | null;
  cambio_tarifa: boolean;
  tarifa_disponible: boolean;
  renovable: boolean;
}

export interface RenovacionItem {
  fila: VigenciaRow;
  preview: RenovacionPreview;
  confirmarTarifa: boolean;
}

export interface RenovacionResult {
  tipo_entidad: TipoEntidadVigencia;
  tipo_entidad_label: string;
  entidad_origen_id: number;
  entidad_id: number;
  monto_esperado: string;
  fecha_inicio: string;
  fecha_fin: string;
}

export interface RenovacionMultipleResult {
  lote_id: string;
  paciente_id: number;
  items: RenovacionResult[];
}

export interface VentanaAlerta {
  id: number;
  clave: string;
  valor: number;
  valor_defecto: number;
  tipo: string;
  unidad: string | null;
  minimo: string | null;
  maximo: string | null;
  version: number;
  editable: boolean;
  actualizado_en: string | null;
}

export interface ConteosVigencia {
  vigente: number;
  por_vencer: number;
  vencido: number;
  renovado: number;
  retirado: number;
}

export interface ResumenVigenciaPaciente {
  paciente: {
    id: number;
    codigo: string | null;
    nombre_completo: string;
    estado: string;
    estado_label: string;
  };
  conteos: ConteosVigencia;
  items: VigenciaRow[];
}

export interface VigenciaApiError {
  status: number;
  message: string;
  code?: string;
  motivo?: string;
  errors?: Record<string, string[]>;
  monto_visto?: string;
  tarifa_vigente?: TarifaVigente | null;
  tarifa_aplicable?: TarifaVigente | null;
  modulo?: string;
}

export const ESTADO_VIGENCIA_OPTIONS: { value: string; label: string }[] = [
  { value: '', label: 'Todos los estados' },
  { value: 'vigente', label: 'Vigente' },
  { value: 'por_vencer', label: 'Por vencer' },
  { value: 'vencido', label: 'Vencido' },
  { value: 'renovado', label: 'Renovado' },
  { value: 'retirado', label: 'Retirado' },
];

export const TIPO_ENTIDAD_OPTIONS: { value: string; label: string }[] = [
  { value: '', label: 'Todas las entidades' },
  { value: 'inscripcion_sala', label: 'Inscripción a sala' },
  { value: 'paquete_sesiones', label: 'Paquete de sesiones' },
];

export const VIGENCIA_TEXTS = {
  title: 'Vigencias y renovaciones',
  subtitle:
    'Consultá vencimientos, renová inscripciones y paquetes y configurá la ventana de alerta.',
  panel: 'Panel',
  reports: 'Reportes',
  configuration: 'Configuración',
  panelTitle: 'Panel de vencimientos',
  reportsTitle: 'Reportes de vencidos y por vencer',
  configurationTitle: 'Ventana de alerta',
  newWindow: 'Días de antelación',
  save: 'Guardar',
  saving: 'Guardando...',
  cancel: 'Cancelar',
  close: 'Cerrar',
  renew: 'Renovar',
  renewSelected: 'Renovar seleccionadas',
  renewing: 'Renovando...',
  searching: 'Cargando vigencias...',
  noRows: 'Sin vencimientos próximos',
  noTariff: 'No hay una tarifa vigente para la nueva fecha.',
  noResults: 'Sin resultados para los filtros aplicados',
  noBranches: 'No tenés sucursales asignadas. Contactá al Superadmin.',
  noClinical: 'Sin datos clínicos.',
  filter: 'Filtrar',
  clear: 'Limpiar filtros',
  allBranches: 'Todas las sucursales',
  allTypes: 'Todas las entidades',
  allStates: 'Todos los estados',
  allServices: 'Todos los servicios',
  search: 'Buscar paciente...',
  periodFrom: 'Desde',
  periodTo: 'Hasta',
  exportPdf: 'Exportar PDF',
  exportExcel: 'Exportar Excel',
  exportUnavailable: 'La exportación no está disponible: falta la infraestructura PDF/Excel.',
  patient: 'Paciente',
  tutor: 'Tutor',
  service: 'Servicio',
  period: 'Período',
  expires: 'Vence',
  state: 'Estado',
  balance: 'Saldo',
  actions: 'Acciones',
  type: 'Tipo',
  origin: 'Origen',
  amount: 'Monto esperado',
  noBalance: 'No disponible',
  notApplicable: '—',
  whatsapp: 'WhatsApp',
  renewTitle: 'Renovar entidad',
  renewSubtitle: 'Se creará una nueva entidad vinculada; no se trasladan sesiones.',
  oldTariff: 'Tarifa anterior',
  currentTariff: 'Tarifa vigente',
  tariffChanged: 'La tarifa cambió',
  tariffConfirm: 'Confirmo la nueva tarifa',
  confirmRenew: 'Confirmar renovación',
  successRenew: 'Renovación completada',
  successMultiple: 'Renovaciones completadas',
  receipt: 'Ver comprobante',
  registerPayment: 'Registrar pago',
  paymentUnavailable: 'Pagos y caja todavía no está disponible.',
  receiptError: 'No se pudo obtener el comprobante.',
  configHelp:
    'La ventana se expresa en días y se usa para clasificar una entidad como "Por vencer".',
  configSaved: 'Ventana de alerta actualizada',
  configError: 'No se pudo actualizar la ventana de alerta.',
  noPermission: 'No tenés permiso para realizar esta acción.',
  reason: 'Motivo',
  dates: 'Fechas propuestas',
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

export function whatsappUrl(phone: string | null | undefined): string | null {
  if (!phone) {
    return null;
  }

  const digits = phone.replace(/\D/g, '');

  if (digits.length < 7) {
    return null;
  }

  const international = digits.startsWith('591') ? digits : `591${digits}`;
  return `https://wa.me/${international}`;
}

export function estadoVigenciaColor(
  estado: EstadoVigencia,
): 'success' | 'warning' | 'error' | 'info' | 'gray' {
  return {
    vigente: 'success',
    por_vencer: 'warning',
    vencido: 'error',
    renovado: 'info',
    retirado: 'gray',
  }[estado] as 'success' | 'warning' | 'error' | 'info' | 'gray';
}

export function entidadLabel(tipo: TipoEntidadVigencia): string {
  return tipo === 'inscripcion_sala' ? 'Inscripción a sala' : 'Paquete de sesiones';
}
