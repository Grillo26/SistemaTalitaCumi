import { Component, Input } from '@angular/core';
import {
  AGENDA_TEXTS,
  ESTADO_LABELS,
  ESTADO_OPTIONS,
} from '../../agenda-semanal.constants';
import type {
  AgendaFiltros,
  AgendaSemanalData,
  OpcionesFiltrosData,
  TurnoAgenda,
} from '../../agenda-semanal.models';
import {
  formatLongDate,
  formatTimeRange,
  formatWeekRange,
  pacienteLabel,
  profesionalLabel,
  salaLabel,
  sanitizeHexColor,
  servicioLabel,
  visibleDays,
} from '../../agenda-semanal.utils';

@Component({
  selector: 'app-agenda-impresion',
  host: { class: 'contents' },
  template: `
    @if (data !== null) {
      <section id="agenda-print" class="agenda-print-only" aria-hidden="true">
        <header class="mb-4 border-b border-gray-300 pb-3">
          <h1 class="text-xl font-bold">{{ texts.title }}</h1>
          <p class="text-sm">
            {{ data.sucursal.nombre }} · {{ weekLabel }}
          </p>
          @if (filtrosLabel !== '') {
            <p class="mt-1 text-xs">Filtros: {{ filtrosLabel }}</p>
          }
          <p class="mt-1 text-xs">{{ texts.summaryBase }} · {{ texts.summaryTotal }}: {{ data.resumen.total }}</p>
        </header>

        @for (dia of visibleDays; track dia.fecha) {
          <section class="agenda-print-day mb-4">
            <h2 class="mb-2 text-sm font-bold">{{ formatLongDate(dia.fecha) }}</h2>

            @if (turnosDelDia(dia.fecha).length === 0) {
              <p class="text-xs text-gray-600">{{ texts.noTurnsInGroup }}</p>
            } @else {
              <table class="w-full border-collapse text-xs">
                <thead>
                  <tr class="border-b border-gray-300 text-start">
                    <th class="py-1 pe-2 text-start">Hora</th>
                    <th class="py-1 pe-2 text-start">Paciente</th>
                    <th class="py-1 pe-2 text-start">Servicio</th>
                    <th class="py-1 pe-2 text-start">Sala</th>
                    <th class="py-1 pe-2 text-start">Profesional</th>
                    <th class="py-1 text-start">Estado</th>
                  </tr>
                </thead>
                <tbody>
                  @for (turno of turnosDelDia(dia.fecha); track turno.id) {
                    <tr class="border-b border-gray-200 align-top">
                      <td class="py-1 pe-2 whitespace-nowrap">
                        {{ formatTimeRange(turno.hora_inicio, turno.hora_fin) }}
                      </td>
                      <td class="py-1 pe-2">
                        <span
                          class="me-1 inline-block size-2 rounded-full align-middle"
                          [style.background-color]="areaColor(turno)"
                        ></span>
                        {{ pacienteLabel(turno) }}
                      </td>
                      <td class="py-1 pe-2">{{ servicioLabel(turno) }}</td>
                      <td class="py-1 pe-2">{{ salaLabel(turno) }}</td>
                      <td class="py-1 pe-2">
                        {{ profesionalLabel(turno) }}
                      </td>
                      <td class="py-1">{{ estadoLabel(turno) }}</td>
                    </tr>
                  }
                </tbody>
              </table>
            }
          </section>
        }

        <footer class="mt-4 border-t border-gray-300 pt-3 text-xs">
          <strong>{{ texts.summaryTitle }}:</strong>
          @for (option of estadoOptions; track option.value) {
            <span class="me-3">{{ option.label }}: {{ data.resumen.por_estado[option.value] }}</span>
          }
        </footer>
      </section>
    }
  `,
})
export class AgendaImpresionComponent {
  @Input() data: AgendaSemanalData | null = null;
  @Input() filtros: AgendaFiltros | null = null;
  @Input() opciones: OpcionesFiltrosData | null = null;

  readonly texts = AGENDA_TEXTS;
  readonly estadoOptions = ESTADO_OPTIONS;
  readonly formatLongDate = formatLongDate;
  readonly formatTimeRange = formatTimeRange;
  readonly pacienteLabel = pacienteLabel;
  readonly servicioLabel = servicioLabel;
  readonly salaLabel = salaLabel;
  readonly profesionalLabel = profesionalLabel;

  get visibleDays() {
    return this.data === null ? [] : visibleDays(this.data.semana.dias);
  }

  get weekLabel(): string {
    if (this.data === null) {
      return '';
    }

    return formatWeekRange(this.data.semana.inicio_visible, this.data.semana.fin_visible);
  }

  get filtrosLabel(): string {
    if (this.filtros === null) {
      return '';
    }

    const parts: string[] = [];
    const area = this.opciones?.areas.find(
      (item) => String(item.id) === this.filtros?.area_id,
    );
    const sala = this.opciones?.salas.find(
      (item) => String(item.id) === this.filtros?.sala_id,
    );
    const profesional = this.opciones?.profesionales.find(
      (item) => String(item.id) === this.filtros?.profesional_id,
    );
    const servicio = this.opciones?.servicios.find(
      (item) => String(item.id) === this.filtros?.servicio_id,
    );

    if (area !== undefined) {
      parts.push(`Área: ${area.nombre}`);
    }
    if (this.filtros.sala_id === 'sin_asignar') {
      parts.push(`Sala: ${this.texts.noRoom}`);
    } else if (sala !== undefined) {
      parts.push(`Sala: ${sala.nombre}`);
    }
    if (this.filtros.profesional_id === 'sin_asignar') {
      parts.push(`Profesional: ${this.texts.unassigned}`);
    } else if (profesional !== undefined) {
      parts.push(`Profesional: ${profesional.nombre_completo}`);
    }
    if (servicio !== undefined) {
      parts.push(`Servicio: ${servicio.nombre}`);
    }
    if (this.filtros.estado !== '') {
      const estado = ESTADO_OPTIONS.find(
        (option) => option.value === this.filtros?.estado,
      );
      parts.push(`Estado: ${estado?.label ?? this.filtros.estado}`);
    }

    return parts.join(' · ');
  }

  turnosDelDia(fecha: string): TurnoAgenda[] {
    return this.data === null
      ? []
      : this.data.turnos
          .filter((turno) => turno.fecha === fecha)
          .sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio));
  }

  areaColor(turno: TurnoAgenda): string {
    return sanitizeHexColor(turno.servicio.area_color) ?? '#D0D5DD';
  }

  estadoLabel(turno: TurnoAgenda): string {
    return ESTADO_LABELS[turno.estado];
  }
}
