import { Temporal } from 'temporal-polyfill';
import { environment } from '../../../../environments/environment';
import { AGENDA_TEXTS } from './agenda-semanal.constants';
import type {
  AgendaFiltros,
  AgendaSemanaParams,
  DiaAgenda,
  EstadoTurno,
  TurnoAgenda,
} from './agenda-semanal.models';

const DAY_MS = 86_400_000;

export function todayInAgendaTimezone(): string {
  return Temporal.Now.plainDateISO(environment.timezone).toString();
}

export function mondayOf(date: string): string {
  const plainDate = Temporal.PlainDate.from(date);
  return plainDate.subtract({ days: plainDate.dayOfWeek - 1 }).toString();
}

export function addWeeks(date: string, weeks: number): string {
  return Temporal.PlainDate.from(mondayOf(date)).add({ weeks }).toString();
}

export function addDays(date: string, days: number): string {
  return Temporal.PlainDate.from(date).add({ days }).toString();
}

export function formatWeekRange(inicio: string, finVisible: string): string {
  const from = formatShortDate(inicio);
  const to = formatShortDate(finVisible);
  return `${from} – ${to}`;
}

export function formatShortDate(date: string): string {
  return new Intl.DateTimeFormat(environment.locale, {
    day: 'numeric',
    month: 'short',
  }).format(new Date(`${date}T12:00:00`));
}

export function formatLongDate(date: string): string {
  const formatted = new Intl.DateTimeFormat(environment.locale, {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  }).format(new Date(`${date}T12:00:00`));

  return formatted.charAt(0).toUpperCase() + formatted.slice(1);
}

export function formatTimeRange(inicio: string, fin: string): string {
  return `${inicio} – ${fin}`;
}

export function formatGeneratedAt(value: string | null | undefined): string {
  if (!value) {
    return '';
  }

  const parsed = new Date(value);

  if (Number.isNaN(parsed.getTime())) {
    return '';
  }

  return new Intl.DateTimeFormat(environment.locale, {
    hour: '2-digit',
    minute: '2-digit',
  }).format(parsed);
}

export function toApiParams(filters: AgendaFiltros): AgendaSemanaParams {
  const params: AgendaSemanaParams = {};

  if (filters.area_id !== '') {
    params.area_id = Number(filters.area_id);
  }

  if (filters.sala_id === 'sin_asignar') {
    params.sala_sin_asignar = true;
  } else if (filters.sala_id !== '') {
    params.sala_id = Number(filters.sala_id);
  }

  if (filters.profesional_id === 'sin_asignar') {
    params.profesional_sin_asignar = true;
  } else if (filters.profesional_id !== '') {
    params.profesional_id = Number(filters.profesional_id);
  }

  if (filters.servicio_id !== '') {
    params.servicio_id = Number(filters.servicio_id);
  }

  if (filters.estado !== '') {
    params.estado = filters.estado as EstadoTurno;
  }

  return params;
}

export function isValidHexColor(value: string | null | undefined): value is string {
  return typeof value === 'string' && /^#(?:[0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/.test(value);
}

export function sanitizeHexColor(value: string | null | undefined): string | null {
  return isValidHexColor(value) ? value.toUpperCase() : null;
}

/**
 * Texto legible sobre el color del área. El backend envía `color_texto`;
 * esto es solo un respaldo por si la respuesta llegara incompleta.
 */
export function contrastText(value: string | null | undefined): string {
  const color = sanitizeHexColor(value);

  if (color === null) {
    return '#101828';
  }

  const hex = color.slice(1);
  const normalized =
    hex.length === 3
      ? hex
          .split('')
          .map((part) => part + part)
          .join('')
      : hex;

  const channels = [0, 2, 4].map((offset) => {
    const value = Number.parseInt(normalized.slice(offset, offset + 2), 16) / 255;
    return value <= 0.03928
      ? value / 12.92
      : ((value + 0.055) / 1.055) ** 2.4;
  });

  const luminance =
    0.2126 * (channels[0] ?? 0) +
    0.7152 * (channels[1] ?? 0) +
    0.0722 * (channels[2] ?? 0);

  return luminance > 0.179 ? '#101828' : '#FFFFFF';
}

export function pacienteLabel(turno: TurnoAgenda): string {
  return turno.paciente.nombre_completo ?? AGENDA_TEXTS.noAvailable;
}

export function servicioLabel(turno: TurnoAgenda): string {
  return turno.servicio.nombre ?? AGENDA_TEXTS.noAvailable;
}

export function salaLabel(turno: TurnoAgenda): string {
  if (turno.sala === null) {
    return AGENDA_TEXTS.noRoom;
  }

  return turno.sala.nombre ?? AGENDA_TEXTS.noAvailable;
}

export function profesionalLabel(turno: TurnoAgenda): string {
  if (turno.profesional === null) {
    return AGENDA_TEXTS.unassigned;
  }

  return turno.profesional.nombre_completo ?? AGENDA_TEXTS.noAvailable;
}

export function buildTurnoTooltip(turno: TurnoAgenda): string {
  return [
    `${pacienteLabel(turno)} · ${servicioLabel(turno)}`,
    `Sala: ${salaLabel(turno)}`,
    `Profesional: ${profesionalLabel(turno)}`,
    `Fecha: ${formatLongDate(turno.fecha)}`,
    `Hora: ${formatTimeRange(turno.hora_inicio, turno.hora_fin)}`,
    `Estado: ${turno.estado_label}`,
  ].join('\n');
}

export function calendarStart(fecha: string, hora: string): string {
  const [year, month, day] = fecha.split('-').map(Number);
  const [hour, minute] = hora.split(':').map(Number);

  return Temporal.ZonedDateTime.from({
    timeZone: environment.timezone,
    year: year ?? 1970,
    month: month ?? 1,
    day: day ?? 1,
    hour: hour ?? 0,
    minute: minute ?? 0,
  })
    .toInstant()
    .toString();
}

export function visibleDays(dias: DiaAgenda[]): DiaAgenda[] {
  return dias.filter((dia) => dia.visible);
}

export function hiddenFullCalendarDays(dias: DiaAgenda[]): number[] {
  const visible = new Set(dias.filter((dia) => dia.visible).map((dia) => dia.dia_semana % 7));

  return [0, 1, 2, 3, 4, 5, 6].filter((day) => !visible.has(day));
}

export function dayCount(dias: DiaAgenda[]): number {
  return Math.max(1, dias.filter((dia) => dia.visible).length);
}

export function diffInDays(from: string, to: string): number {
  return Math.round(
    (new Date(`${to}T12:00:00`).getTime() - new Date(`${from}T12:00:00`).getTime()) / DAY_MS,
  );
}
