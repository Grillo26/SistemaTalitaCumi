import {
  ContactoEmergencia,
  Paciente,
  PacientePageAbilities,
  TutorResumen,
} from './pacientes.models';

export type ResumenBloqueEstado = 'disponible' | 'no_disponible';

export type ResumenBloqueMotivo =
  | 'MODULO_NO_IMPLEMENTADO'
  | 'SIN_SERVICIOS_VIGENTES'
  | 'SIN_PERMISO'
  | 'SIN_TUTOR'
  | 'ERROR';

export interface ResumenBloque<T = unknown> {
  estado: ResumenBloqueEstado;
  motivo: ResumenBloqueMotivo | null;
  mensaje: string;
  data: T | null;
}

export interface DatosSensiblesResumen {
  tiene_alergias: boolean;
  tiene_condiciones_medicas: boolean;
  tiene_observaciones: boolean;
}

export interface ServicioVigenteResumen {
  tipo: string;
  sucursal_id: number;
  sucursal_nombre: string | null;
  servicio: string;
  periodo_inicio: string;
  periodo_fin: string;
  estado: string;
  sesiones_pactadas: number | null;
  sesiones_disponibles: number | null;
}

export interface EstadoPagoResumen {
  estado: string;
  fecha_corte: string | null;
  proximo_vencimiento: string | null;
  saldo: string | null;
  moneda: string;
}

export interface AsistenciaResumen {
  asistidos: number;
  falto: number;
  cancelados: number;
  porcentaje: number;
  periodo_inicio: string | null;
  periodo_fin: string | null;
}

export interface SucursalServicioResumen {
  id: number;
  nombre: string | null;
}

export interface AlertaPagoResumen {
  tutor_id: number;
  tutor_nombre: string;
  telefono: string;
  whatsapp_url: string;
  mensaje: string;
  fecha_vencimiento: string | null;
}

export interface ResumenBloques {
  datos_basicos: ResumenBloque<Paciente>;
  datos_sensibles: ResumenBloque<DatosSensiblesResumen>;
  tutores: ResumenBloque<{ items: TutorResumen[] }>;
  contactos_emergencia: ResumenBloque<{ items: ContactoEmergencia[] }>;
  servicios_vigentes: ResumenBloque<{ items: ServicioVigenteResumen[] }>;
  sucursales: ResumenBloque<{ items: SucursalServicioResumen[] }>;
  estado_pago: ResumenBloque<EstadoPagoResumen>;
  proximo_vencimiento: ResumenBloque<{ fecha: string | null }>;
  asistencia: ResumenBloque<AsistenciaResumen>;
  alerta_pago: ResumenBloque<AlertaPagoResumen>;
}

export interface PacienteResumenData {
  paciente: Paciente;
  bloques: ResumenBloques;
  contexto_sucursal: number | null;
  sucursales_accesibles: number[] | null;
  fecha_corte: string | null;
  generado_en: string;
}

export interface PacienteResumenResponse {
  data: PacienteResumenData;
  can: PacientePageAbilities;
}

export type ResumenIndicadoresBloques = Pick<
  ResumenBloques,
  | 'servicios_vigentes'
  | 'sucursales'
  | 'estado_pago'
  | 'proximo_vencimiento'
  | 'asistencia'
  | 'alerta_pago'
>;

export interface PacienteResumenIndicadoresData {
  bloques: ResumenIndicadoresBloques;
  contexto_sucursal: number | null;
  sucursales_accesibles: number[] | null;
  fecha_corte: string | null;
  generado_en: string;
}

export interface PacienteResumenIndicadoresResponse {
  data: PacienteResumenIndicadoresData;
  can: PacientePageAbilities;
}

export const PACIENTE_RESUMEN_TEXTS = {
  title: 'Ficha resumen',
  loading: 'Cargando ficha resumen...',
  notFound: 'El paciente no existe o está fuera de tu alcance.',
  errorTitle: 'No se pudo cargar la ficha resumen',
  back: 'Volver a pacientes',
  fullFicha: 'Ver ficha completa',
  fichaIngreso: 'Ver ficha de ingreso',
  refresh: 'Actualizar indicadores',
  refreshing: 'Actualizando...',
  refreshed: 'Indicadores actualizados',
  updatedAt: 'Actualizado',
  cutoff: 'Fecha de corte',
  readOnly:
    'La ficha resumen es de solo lectura. Las modificaciones se hacen en la ficha completa.',
  auditNotice: 'Esta consulta queda registrada en la bitácora.',
  basicData: 'Datos del paciente',
  age: 'Edad',
  birthDate: 'Nacimiento',
  sex: 'Sexo',
  document: 'Documento',
  code: 'Código',
  branch: 'Sucursal de registro',
  status: 'Estado',
  diagnosis: 'Diagnóstico',
  sinDiagnostico: 'Sin diagnóstico definido',
  sensitive: 'Datos sensibles',
  sensitiveHelp:
    'Solo se muestran banderas de presencia. El contenido se consulta en la ficha completa con permiso.',
  hasAllergies: 'Tiene alergias registradas',
  hasConditions: 'Tiene condiciones médicas registradas',
  hasObservations: 'Tiene observaciones registradas',
  noSensitive: 'Sin datos sensibles registrados.',
  tutors: 'Tutores',
  contacts: 'Contactos de emergencia',
  services: 'Servicios vigentes',
  payment: 'Estado de pago',
  attendance: 'Resumen de asistencia',
  nextDue: 'Próximo vencimiento',
  branches: 'Sucursales con servicios',
  paymentAlert: 'Alerta de próximo pago',
  viewEnrollments: 'Ver inscripciones',
  viewAppointments: 'Ver turnos',
  viewPayments: 'Ver pagos',
  alertButton: 'Generar alerta de próximo pago',
  module06: 'Disponible con Inscripciones (módulo 06).',
  module07: 'Disponible con Agenda y asistencia (módulo 07).',
  module08: 'Disponible con Pagos y caja (módulo 08).',
  module09: 'Disponible con Alertas de próximo pago (módulo 09).',
  paymentAlertPending: 'Disponible con 04.02 Fase C y Alertas (módulo 09).',
  noPermission: 'No tenés permiso para ver este bloque.',
  noServices: 'Sin servicios vigentes.',
  noTutors: 'Sin tutores asociados.',
  noContacts: 'Sin contactos de emergencia registrados.',
  principal: 'Principal',
  inactive: 'Inactivo',
  phone: 'Teléfono',
  noData: 'Sin datos para mostrar.',
  period: 'Período',
  sessions: 'Sesiones',
  attendanceCounts: 'Asistidos / faltó / cancelados',
  attended: 'Asistidos',
  missed: 'Faltó',
  cancelled: 'Cancelados',
  percentage: 'Porcentaje de asistencia',
  openWhatsApp: 'Abrir WhatsApp',
  alDia: 'Al día',
  conSaldo: 'Con saldo',
  vencido: 'Vencido',
  sinServicios: 'Sin servicios',
  nextDueNone: 'Sin fecha de vencimiento registrada.',
  currentContext: 'Contexto',
  allBranches: 'Todas las sucursales accesibles',
  cutoffPending: 'Se mostrará cuando existan los módulos de pago y asistencia.',
} as const;
