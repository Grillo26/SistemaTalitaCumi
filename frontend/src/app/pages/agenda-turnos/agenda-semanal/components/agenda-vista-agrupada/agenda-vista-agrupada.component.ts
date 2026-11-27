import { Component, EventEmitter, Input, Output } from '@angular/core';
import { BadgeComponent } from '../../../../../shared/components/ui/badge/badge.component';
import {
  AGENDA_TEXTS,
  ESTADO_BADGES,
  ESTADO_LABELS,
} from '../../agenda-semanal.constants';
import type {
  DiaAgenda,
  EstadoTurno,
  TurnoAgenda,
} from '../../agenda-semanal.models';
import {
  buildTurnoTooltip,
  formatLongDate,
  formatTimeRange,
  pacienteLabel,
  profesionalLabel,
  salaLabel,
  sanitizeHexColor,
  servicioLabel,
} from '../../agenda-semanal.utils';

interface DiaGrupo {
  dia: DiaAgenda;
  turnos: TurnoAgenda[];
}

interface GrupoVista {
  key: string;
  titulo: string;
  turnos: TurnoAgenda[];
  dias: DiaGrupo[];
}

@Component({
  selector: 'app-agenda-vista-agrupada',
  imports: [BadgeComponent],
  host: { class: 'block' },
  template: `
    <div class="space-y-5">
      @for (grupo of grupos; track grupo.key) {
        <section class="rounded-xl border border-gray-200 dark:border-gray-800">
          <header
            class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-100 px-4 py-3 dark:border-gray-800"
          >
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">
              {{ grupo.titulo }}
            </h3>
            <span class="text-xs text-gray-500 dark:text-gray-400">
              {{ grupo.turnos.length }} turno(s)
            </span>
          </header>

          <div class="divide-y divide-gray-100 dark:divide-gray-800">
            @for (dia of grupo.dias; track dia.dia.fecha) {
              <div class="p-4">
                <h4 class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400">
                  {{ dia.dia.nombre }} · {{ formatLongDate(dia.dia.fecha) }}
                </h4>

                <div class="grid grid-cols-1 gap-2 md:grid-cols-2 xl:grid-cols-3">
                  @for (turno of dia.turnos; track turno.id) {
                    <button
                      type="button"
                      class="flex w-full items-start gap-3 rounded-lg border border-gray-200 bg-white p-3 text-start transition-colors hover:border-brand-300 hover:bg-brand-50/40 focus:outline-none focus-visible:ring-3 focus-visible:ring-brand-500/20 dark:border-gray-700 dark:bg-white/[0.03] dark:hover:border-brand-500/40 dark:hover:bg-brand-500/10"
                      [attr.aria-label]="tooltip(turno)"
                      [attr.title]="tooltip(turno)"
                      (click)="turnoClick.emit(turno.id)"
                    >
                      <span
                        class="mt-1 size-2.5 shrink-0 rounded-full"
                        [style.background-color]="areaColor(turno)"
                        aria-hidden="true"
                      ></span>

                      <span class="min-w-0 flex-1">
                        <span class="block text-xs font-medium text-gray-500 dark:text-gray-400">
                          {{ formatTimeRange(turno.hora_inicio, turno.hora_fin) }}
                        </span>
                        <span class="block truncate text-sm font-semibold text-gray-800 dark:text-white/90">
                          {{ pacienteLabel(turno) }}
                        </span>
                        <span class="block truncate text-xs text-gray-500 dark:text-gray-400">
                          {{ servicioLabel(turno) }}
                          · {{ salaLabel(turno) }}
                        </span>

                        <span class="mt-1.5 flex flex-wrap items-center gap-1.5">
                          <app-badge
                            [color]="estadoBadge(turno.estado).color"
                            variant="light"
                            size="sm"
                          >
                            {{ estadoBadge(turno.estado).icon }} {{ estadoLabel(turno.estado) }}
                          </app-badge>
                          @if (turno.sin_asignar) {
                            <app-badge color="warning" variant="light" size="sm">
                              {{ texts.unassigned }}
                            </app-badge>
                          }
                        </span>
                      </span>
                    </button>
                  }
                </div>
              </div>
            }
          </div>
        </section>
      }

      @if (grupos.length === 0) {
        <p class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500 dark:border-gray-700 dark:text-gray-400">
          {{ texts.noTurnsInGroup }}
        </p>
      }
    </div>
  `,
})
export class AgendaVistaAgrupadaComponent {
  @Input() turnos: TurnoAgenda[] = [];
  @Input() dias: DiaAgenda[] = [];
  @Input() grupo: 'profesional' | 'sala' = 'profesional';

  @Output() turnoClick = new EventEmitter<number>();

  readonly texts = AGENDA_TEXTS;
  readonly formatLongDate = formatLongDate;
  readonly formatTimeRange = formatTimeRange;
  readonly pacienteLabel = pacienteLabel;
  readonly servicioLabel = servicioLabel;
  readonly salaLabel = salaLabel;

  get grupos(): GrupoVista[] {
    const groups = new Map<string, { titulo: string; turnos: TurnoAgenda[] }>();

    for (const turno of this.turnos) {
      const key =
        this.grupo === 'profesional'
          ? (turno.profesional?.id.toString() ?? 'sin_asignar')
          : (turno.sala?.id.toString() ?? 'sin_sala');
      const titulo =
        this.grupo === 'profesional'
          ? profesionalLabel(turno)
          : salaLabel(turno);
      const existing = groups.get(key);

      if (existing === undefined) {
        groups.set(key, { titulo, turnos: [turno] });
      } else {
        existing.turnos.push(turno);
      }
    }

    return [...groups.entries()]
      .map(([key, group]) => ({
        key,
        titulo: group.titulo,
        turnos: [...group.turnos].sort((a, b) =>
          `${a.fecha} ${a.hora_inicio}`.localeCompare(`${b.fecha} ${b.hora_inicio}`),
        ),
        dias: this.dias
          .map((dia) => ({
            dia,
            turnos: group.turnos
              .filter((turno) => turno.fecha === dia.fecha)
              .sort((a, b) => a.hora_inicio.localeCompare(b.hora_inicio)),
          }))
          .filter((dia) => dia.turnos.length > 0),
      }))
      .sort((a, b) => {
        const aSpecial = a.key === 'sin_asignar' || a.key === 'sin_sala';
        const bSpecial = b.key === 'sin_asignar' || b.key === 'sin_sala';

        if (aSpecial !== bSpecial) {
          return aSpecial ? 1 : -1;
        }

        return a.titulo.localeCompare(b.titulo, 'es');
      });
  }

  areaColor(turno: TurnoAgenda): string {
    return sanitizeHexColor(turno.servicio.area_color) ?? '#D0D5DD';
  }

  estadoBadge(estado: EstadoTurno): { color: 'primary' | 'success' | 'error' | 'warning' | 'gray' | 'info'; icon: string } {
    return ESTADO_BADGES[estado];
  }

  estadoLabel(estado: EstadoTurno): string {
    return ESTADO_LABELS[estado];
  }

  tooltip(turno: TurnoAgenda): string {
    return buildTurnoTooltip(turno);
  }
}
