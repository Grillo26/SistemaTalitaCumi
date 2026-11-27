export type TipoAtencion = 'entrevista' | 'diagnostico';
export type EstadoAtencion = 'activa' | 'retirada';
export type SortDirection = 'asc' | 'desc';
export type FormMode = 'create' | 'edit';
export type FichaTab = 'resumen' | 'pago' | 'comprobante';

export interface PacienteResumen {
  id: number;
  codigo: string | null;
  nombre_completo: string;
  estado?: string;
  estado_label?: string;
  fecha_nacimiento?: string;
  edad_anios?: number;
}

export interface TutorResumen {
  id: number;
  nombre_completo: string;
  parentesco?: string | null;
  es_principal?: boolean;
  activo?: boolean;
  telefono?: string | null;
  telefono_enmascarado?: string | null;
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

export interface TipoDiagnosticoResumen {
  id: number;
  nombre: string;
  dias_estimados: number;
}

export interface ProfesionalResumen {
  id: number;
  nombre_completo: string;
  especialidades?: { id: number; nombre: string }[];
}

export interface RequisitoEspecialidad {
  especialidad_id: number;
  especialidad_nombre: string;
  cantidad_minima: number;
  activo?: boolean;
}

export interface AtencionRetiro {
  fecha_baja: string | null;
  motivo_codigo: string | null;
  motivo_detalle: string | null;
  registrado_en: string | null;
  usuario_id: number | null;
  usuario_nombre: string | null;
}

export interface AtencionAbilities {
  view: boolean;
  create: boolean;
  update: boolean;
  withdraw: boolean;
  export: boolean;
  print: boolean;
  pdf: boolean;
  pago_disponible: boolean;
  view_tutor_contact: boolean;
}

export interface AtencionRowAbilities {
  view: boolean;
  update: boolean;
  withdraw: boolean;
}

export interface EntrevistaRow {
  id: number;
  paciente: PacienteResumen;
  tutor: TutorResumen;
  sucursal: SucursalResumen;
  servicio: ServicioResumen;
  categoria: CategoriaResumen;
  fecha: string;
  monto_esperado: string;
  estado: EstadoAtencion;
  estado_label: string;
  created_at: string | null;
  updated_at: string | null;
  can: AtencionRowAbilities;
}

export interface DiagnosticoRow {
  id: number;
  paciente: PacienteResumen;
  sucursal: SucursalResumen;
  servicio: ServicioResumen;
  tipo_diagnostico: TipoDiagnosticoResumen;
  categoria: CategoriaResumen;
  fecha_inicio: string;
  fecha_fin: string;
  monto_esperado: string;
  estado: EstadoAtencion;
  estado_label: string;
  omitio_entrevista: boolean;
  motivo_omision_entrevista: string | null;
  entrevista_previa_id: number | null;
  profesionales: ProfesionalResumen[];
  created_at: string | null;
  updated_at: string | null;
  can: AtencionRowAbilities;
}

export interface EntrevistaDetalle extends EntrevistaRow {
  paciente: PacienteResumen & { fecha_nacimiento?: string; edad_anios?: number };
  tarifa_id: number;
  notas_admin: string | null;
  retiro: AtencionRetiro | null;
  can: AtencionAbilities & AtencionRowAbilities;
}

export interface DiagnosticoDetalle extends DiagnosticoRow {
  paciente: PacienteResumen & { fecha_nacimiento?: string; edad_anios?: number };
  tarifa_id: number;
  entrevista_previa: { id: number; fecha: string; sucursal_id: number } | null;
  requisitos: RequisitoEspecialidad[];
  notas_admin: string | null;
  retiro: AtencionRetiro | null;
  can: AtencionAbilities & AtencionRowAbilities;
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
  can: AtencionAbilities;
}

export interface DetailResponse<T> {
  data: T;
  can: AtencionAbilities;
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
  can: AtencionAbilities;
}

export interface RequisitosDiagnosticoResponse {
  data: {
    tipo: {
      id: number;
      nombre: string;
      descripcion: string | null;
      dias_estimados: number;
      activo: boolean;
      servicio_id: number;
      servicio_nombre: string;
      servicio_activo: boolean;
      tipo_servicio_codigo: string | null;
      area_id: number | null;
      area_nombre: string | null;
      area_color: string | null;
    };
    especialidades: RequisitoEspecialidad[];
  };
}

export interface EntrevistaPrevia {
  id: number;
  fecha: string;
  sucursal: SucursalResumen;
}

export interface PagoEstado {
  estado: string;
  monto_esperado: string;
  monto_pagado: string;
  saldo: string;
  pagos_registrados: number;
  actualizado_en: string | null;
}

export interface ComprobanteAtencionData {
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
  tipo: TipoAtencion;
  tipo_label: string;
  atencion: {
    id: number;
    paciente: { id: number; codigo: string | null; nombre_completo: string };
    tutor: TutorResumen | null;
    sucursal: SucursalResumen;
    servicio: ServicioResumen;
    categoria: CategoriaResumen;
    tipo_diagnostico: TipoDiagnosticoResumen | null;
    fecha: string | null;
    fecha_inicio: string | null;
    fecha_fin: string | null;
    monto_esperado: string;
    estado: EstadoAtencion;
    estado_label: string;
    profesionales: ProfesionalResumen[];
    requisitos: RequisitoEspecialidad[];
  };
}

export interface EntrevistaListParams {
  search?: string;
  sucursal_id?: number | '';
  paciente_id?: number | '';
  periodo_desde?: string;
  periodo_hasta?: string;
  estado?: EstadoAtencion | '';
  page?: number;
  per_page?: number;
  sort?: 'fecha' | 'monto_esperado' | 'estado' | 'updated_at';
  direction?: SortDirection;
}

export interface DiagnosticoListParams extends Omit<EntrevistaListParams, 'sort'> {
  tipo_diagnostico_id?: number | '';
  profesional_id?: number | '';
  sort?: 'fecha_inicio' | 'fecha_fin' | 'monto_esperado' | 'estado' | 'updated_at';
}

export interface EntrevistaPayload {
  paciente_id: number;
  tutor_id: number;
  sucursal_id: number;
  servicio_id: number;
  categoria_tarifa_id: number;
  fecha: string;
  notas_admin: string | null;
  tarifa_id_esperada?: number | null;
  confirmar_tarifa_cambio?: boolean;
}

export interface EntrevistaUpdatePayload {
  fecha?: string;
  notas_admin?: string | null;
}

export interface DiagnosticoPayload {
  paciente_id: number;
  sucursal_id: number;
  tipo_diagnostico_id: number;
  categoria_tarifa_id: number;
  fecha_inicio: string;
  fecha_fin: string;
  profesionales: number[];
  omitio_entrevista: boolean;
  motivo_omision_entrevista: string | null;
  entrevista_previa_id: number | null;
  notas_admin: string | null;
  tarifa_id_esperada?: number | null;
  confirmar_tarifa_cambio?: boolean;
}

export interface DiagnosticoUpdatePayload {
  fecha_inicio?: string;
  fecha_fin?: string;
  profesionales?: number[];
  notas_admin?: string | null;
}

export interface BajaAtencionPayload {
  motivo: string;
  motivo_detalle: string | null;
  fecha_baja: string;
}

export interface AtencionApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  faltantes?: { especialidad_id: number; nombre: string; requeridos: number; asignados: number }[];
  tarifa_aplicable?: PreviewMonto;
  modulo?: string;
}

export const ESTADO_OPTIONS = [
  { value: '', label: 'Todos los estados' },
  { value: 'activa', label: 'Activas' },
  { value: 'retirada', label: 'Retiradas' },
];

export const EMPTY_ABILITIES: AtencionAbilities = {
  view: false,
  create: false,
  update: false,
  withdraw: false,
  export: false,
  print: false,
  pdf: false,
  pago_disponible: false,
  view_tutor_contact: false,
};

export const EMPTY_ROW_ABILITIES: AtencionRowAbilities = {
  view: false,
  update: false,
  withdraw: false,
};

export const MOTIVO_BAJA_OPTIONS = [
  { value: 'retiro_paciente', label: 'Retiro del paciente' },
  { value: 'error_registro', label: 'Error de registro' },
  { value: 'otro', label: 'Otro' },
];

export const ATENCION_TEXTS = {
  title: 'Entrevistas y diagnósticos',
  subtitle:
    'Registrá entrevistas con los padres y diagnósticos o evaluaciones con sus profesionales, fechas y monto esperado.',
  interviews: 'Entrevistas',
  diagnoses: 'Diagnósticos',
  listTitleInterviews: 'Entrevistas registradas',
  listTitleDiagnoses: 'Diagnósticos registrados',
  newInterview: 'Nueva entrevista',
  newDiagnosis: 'Nuevo diagnóstico',
  editInterview: 'Editar entrevista',
  editDiagnosis: 'Editar diagnóstico',
  emptyInterviews: 'No hay entrevistas para los filtros seleccionados.',
  emptyDiagnoses: 'No hay diagnósticos para los filtros seleccionados.',
  loading: 'Cargando atenciones...',
  filters: 'Filtros',
  clearFilters: 'Limpiar filtros',
  searchPatient: 'Buscar paciente...',
  allBranches: 'Todas las sucursales',
  allTypes: 'Todos los tipos',
  allProfessionals: 'Todos los profesionales',
  allStates: 'Todos los estados',
  periodFrom: 'Desde',
  periodTo: 'Hasta',
  amount: 'Monto esperado',
  payment: 'Pago',
  noPayment: 'No disponible',
  actions: 'Acciones',
  view: 'Ver',
  edit: 'Editar',
  withdraw: 'Dar de baja',
  linkInterview: 'Vincular entrevista',
  save: 'Guardar',
  saving: 'Guardando...',
  cancel: 'Cancelar',
  close: 'Cerrar',
  observations: 'Notas administrativas',
  observationsHint: 'Máximo 500 caracteres. No incluir datos clínicos.',
  patient: 'Paciente',
  tutor: 'Tutor responsable',
  branch: 'Sucursal',
  service: 'Servicio',
  type: 'Tipo de diagnóstico',
  category: 'Categoría',
  date: 'Fecha',
  startDate: 'Fecha de inicio',
  endDate: 'Fecha de fin',
  professionals: 'Profesionales',
  requirements: 'Especialidades requeridas',
  noProfessionals: 'Sin asignar',
  missingProfessionals: 'Faltan profesionales requeridos por el tipo.',
  omitInterview: 'Omitir entrevista',
  omitInterviewReason: 'Motivo de la omisión (opcional)',
  previousInterview: 'El paciente ya tiene una entrevista previa. Podés vincularla o registrar el diagnóstico como omitido.',
  linkedInterview: 'Entrevista previa vinculada',
  omittedInterview: 'Sin entrevista previa',
  expectedAmount: 'Monto esperado',
  calculating: 'Calculando...',
  tariffChanged: 'La tarifa vigente cambió',
  tariffChangedDetail: 'Revisá el nuevo monto y confirmá para continuar.',
  confirmTariff: 'Confirmar nueva tarifa',
  noTariff: 'No hay una tarifa vigente para la selección.',
  summary: 'Resumen',
  receipt: 'Comprobante',
  print: 'Imprimir comprobante',
  noPdf: 'PDF pendiente de infraestructura.',
  back: 'Volver',
  deleted: 'Las atenciones no se eliminan: se dan de baja con motivo y fecha.',
  interviewCreated: 'Entrevista registrada',
  diagnosisCreated: 'Diagnóstico registrado',
  updated: 'Atención actualizada',
  withdrawn: 'Atención dada de baja',
  linked: 'Entrevista vinculada',
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
