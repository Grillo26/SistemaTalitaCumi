import { ESTADO_BADGES } from '../agenda-semanal.constants';
import type { TurnoAgenda } from '../agenda-semanal.models';
import {
  buildTurnoTooltip,
  contrastText,
  isValidHexColor,
  sanitizeHexColor,
} from '../agenda-semanal.utils';

function escapeHtml(value: string): string {
  return value
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;');
}

function text(value: string | null | undefined, fallback = 'No disponible'): string {
  return escapeHtml(value && value.trim() !== '' ? value : fallback);
}

export function renderAgendaEvent(
  eventInfo: any,
  puedeRegistrar = false,
  zonaHoraria = 'America/La_Paz',
): { html: string } {
  const turno = eventInfo.event.extendedProps?.['turno'] as TurnoAgenda | undefined;

  if (turno === undefined) {
    return {
      html: `<div class="p-1 text-[10px] text-gray-500">${escapeHtml(
        eventInfo.event.title ?? '',
      )}</div>`,
    };
  }

  const mostrarAcciones =
    puedeRegistrar &&
    turno.estado === 'programado' &&
    turnoYaInicio(turno, zonaHoraria);

  const rawColor = sanitizeHexColor(turno.servicio.area_color);
  const color = rawColor !== null && isValidHexColor(rawColor) ? rawColor : null;
  const textColor = color !== null ? contrastText(color) : null;
  const style =
    color !== null && textColor !== null
      ? `background-color:${color};border-color:${color};color:${textColor};`
      : '';
  const colorClass =
    color === null
      ? 'border border-gray-200 bg-gray-100 text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100'
      : '';
  const estado = ESTADO_BADGES[turno.estado];
  const tooltip = escapeHtml(buildTurnoTooltip(turno));
  const sinAsignar = turno.sin_asignar
    ? `<span class="agenda-evento-flag">⚠ Sin asignar</span>`
    : '';
  const sala =
    turno.sala === null
      ? 'Sin sala'
      : (turno.sala.nombre ?? 'No disponible');

  return {
    html: `
      <div
        class="agenda-evento flex h-full w-full flex-col overflow-hidden rounded-md border p-1 text-[10px] leading-tight sm:rounded-lg sm:p-1.5 sm:text-[11px] ${colorClass}"
        style="${style}"
        title="${tooltip}"
        aria-label="${tooltip}"
      >
        <div class="agenda-evento-title flex items-center gap-1 font-semibold">
          <span aria-hidden="true">${estado.icon}</span>
          <span class="truncate">${text(turno.paciente.nombre_completo)}</span>
        </div>
        <div class="agenda-evento-line truncate opacity-90">${text(turno.servicio.nombre)}</div>
        <div class="agenda-evento-line truncate opacity-80">${text(sala, 'Sin sala')}</div>
        <div class="mt-auto flex flex-wrap items-center gap-x-1.5 gap-y-0.5 pt-0.5">
          <span class="agenda-evento-state font-medium">${escapeHtml(turno.estado_label)}</span>
          ${sinAsignar}
        </div>
        ${
          mostrarAcciones
            ? `<div class="agenda-evento-actions mt-1 flex items-center gap-1">
                <button type="button" data-asistencia="asistio" title="Marcar Asistió" aria-label="Marcar Asistió" class="rounded bg-white/90 px-1.5 py-0.5 text-[10px] font-bold text-success-700 shadow-sm hover:bg-white">✓</button>
                <button type="button" data-asistencia="falto" title="Marcar Faltó" aria-label="Marcar Faltó" class="rounded bg-white/90 px-1.5 py-0.5 text-[10px] font-bold text-error-700 shadow-sm hover:bg-white">✕</button>
              </div>`
            : ''
        }
      </div>
    `,
  };
}

function turnoYaInicio(turno: TurnoAgenda, zonaHoraria: string): boolean {
  try {
    const ahora = new Intl.DateTimeFormat('sv-SE', {
      timeZone: zonaHoraria,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      hour12: false,
    }).format(new Date());

    return `${turno.fecha} ${turno.hora_inicio}` <= ahora;
  } catch {
    return false;
  }
}
