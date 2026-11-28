export type PagoEstado = 'vigente' | 'anulado';

export interface PagoEntidadRef {
  tipo: string | null;
  id: number | null;
}

export interface PagoRow {
  id: number;
  numero_correlativo: string;
  numero_comprobante: string;
  sucursal_id: number;
  sucursal_codigo: string;
  sucursal_nombre: string;
  paciente_id: number;
  paciente_nombre: string;
  tutor_id: number;
  tutor_nombre: string;
  entidad: PagoEntidadRef;
  entidad_referencia: string;
  monto_esperado: string;
  monto: string;
  saldo_resultante: string;
  medio_pago: string;
  referencia_pago: string | null;
  grupo_pago_id: string;
  fecha_pago: string;
  fecha_futura_autorizada: boolean;
  motivo_fecha_futura: string | null;
  estado: PagoEstado;
  estado_label: string;
  anulado_en: string | null;
  motivo_anulacion: string | null;
  created_at: string | null;
  lineas?: PagoRow[];
}

export interface PagoAbilities {
  view: boolean;
  register: boolean;
  authorize_future: boolean;
  export: boolean;
  delete: boolean;
  reprint: boolean;
  payable_entities: boolean;
}

export interface PaginatedPagos {
  data: PagoRow[];
  links: Record<string, string | null>;
  meta: { current_page: number; last_page: number; per_page: number; total: number };
  can: PagoAbilities;
}

export interface PagoListParams {
  periodo_desde?: string;
  periodo_hasta?: string;
  medio_pago?: string;
  estado?: string;
  numero?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: 'asc' | 'desc';
}

export interface PagoEntidadResumen {
  tipo: string;
  tipo_label: string;
  id: number;
  sucursal_id: number;
  sucursal_codigo: string;
  sucursal_nombre: string;
  paciente_id: number;
  paciente_nombre: string;
  referencia: string;
  estado: string;
  monto_esperado: string;
  tutor_sugerido_id: number | null;
  tutor_sugerido_nombre: string | null;
  periodo: string | null;
}

export interface PagoHistorial {
  entidad: PagoEntidadResumen;
  monto_esperado: string;
  total_pagado: string;
  saldo: string;
  pagos: PagoRow[];
}

export interface PagoMedio {
  codigo: string;
  nombre: string;
}

export interface PagoRegistroLinea {
  medio_pago: string;
  monto: string;
  referencia?: string;
}

export interface PagoRegistroPayload {
  tipo_entidad: string;
  entidad_id: number;
  clave_idempotencia: string;
  fecha_pago?: string;
  tutor_id?: number | null;
  motivo_fecha_futura?: string;
  lineas: PagoRegistroLinea[];
}

export interface PagoRegistroResultado {
  grupo_pago_id: string;
  replay: boolean;
  saldo_resultante: string;
  lineas: PagoRow[];
}

export interface PagoApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
  saldo_maximo?: string;
}

export const PAGO_ESTADO_OPTIONS = [
  { value: '', label: 'Todos los estados' },
  { value: 'vigente', label: 'Vigentes' },
  { value: 'anulado', label: 'Anulados' },
];

export function formatBs(value: string | number | null | undefined): string {
  if (value === null || value === undefined || value === '') {
    return '—';
  }

  const [entero, decimales = '00'] = String(value).split('.');
  const grupos = entero.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

  return `Bs. ${grupos},${decimales.padEnd(2, '0').slice(0, 2)}`;
}

export function formatFecha(value: string | null | undefined): string {
  if (!value) {
    return '—';
  }

  const [anio, mes, dia] = value.slice(0, 10).split('-');

  return `${dia}/${mes}/${anio}`;
}

export const MONTO_PATTERN = /^\d{1,10}(\.\d{1,2})?$/;
