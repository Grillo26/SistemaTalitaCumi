import {
  Component,
  EventEmitter,
  Input,
  OnChanges,
  Output,
  SimpleChanges,
} from '@angular/core';
import { FullCalendarModule } from '@fullcalendar/angular';
import type { CalendarOptions, EventClickInfo, EventInput } from 'fullcalendar';
import interactionPlugin from 'fullcalendar/interaction';
import esLocale from 'fullcalendar/locales/es';
import themePlugin from 'fullcalendar/themes/classic';
import timeGridPlugin from 'fullcalendar/timegrid';
import { environment } from '../../../../../../environments/environment';
import type { AgendaSemanalData, TurnoAgenda } from '../../agenda-semanal.models';
import type { EstadoAsistencia } from '../../../asistencia-turno/asistencia-turno.models';
import {
  buildTurnoTooltip,
  calendarStart,
  hiddenFullCalendarDays,
} from '../../agenda-semanal.utils';
import { renderAgendaEvent } from '../agenda-event-renderer';

@Component({
  selector: 'app-agenda-calendario',
  imports: [FullCalendarModule],
  host: { class: 'block' },
  template: `
    <div class="agenda-calendar-wrapper overflow-x-auto">
      <full-calendar [options]="calendarOptions"></full-calendar>
    </div>
  `,
})
export class AgendaCalendarioComponent implements OnChanges {
  @Input() data: AgendaSemanalData | null = null;
  @Input() puedeRegistrar = false;
  @Output() turnoClick = new EventEmitter<number>();
  @Output() asistenciaRapida = new EventEmitter<{
    turnoId: number;
    estado: EstadoAsistencia;
  }>();

  calendarOptions: CalendarOptions = this.buildOptions(null);

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['data'] || changes['puedeRegistrar']) {
      this.calendarOptions = this.buildOptions(this.data);
    }
  }

  private buildOptions(data: AgendaSemanalData | null): CalendarOptions {
    const events = data === null ? [] : this.toEvents(data);
    const hiddenDays = data === null ? [] : hiddenFullCalendarDays(data.semana.dias);
    const slotMinTime = data === null ? '00:00:00' : `${data.rango_horario.hora_inicio}:00`;
    const slotMaxTime = data === null ? '24:00:00' : `${data.rango_horario.hora_fin}:00`;

    return {
      plugins: [themePlugin, timeGridPlugin, interactionPlugin],
      initialView: 'timeGridWeek',
      locale: esLocale,
      timeZone: environment.timezone,
      headerToolbar: false,
      firstDay: 1,
      hiddenDays,
      slotMinTime,
      slotMaxTime,
      slotDuration: '00:30:00',
      slotMinHeight: 56,
      allDaySlot: false,
      nowIndicator: true,
      height: 'auto',
      contentHeight: 'auto',
      expandRows: true,
      eventClass: 'focus:shadow-none',
      columnEventClass:
        'bg-transparent! border-0! p-0! shadow-none! hover:shadow-none! focus:outline-none',
      columnEventInnerClass: 'p-0! border-0! bg-transparent! h-full',
      rowEventClass:
        'bg-transparent! border-0! px-1! py-0.5! shadow-none! hover:shadow-none! focus:outline-none',
      rowEventInnerClass: 'p-0! border-0! bg-transparent!',
      editable: false,
      selectable: false,
      eventStartEditable: false,
      eventDurationEditable: false,
      eventOverlap: true,
      slotEventOverlap: true,
      eventDisplay: 'block',
      dayMaxEvents: false,
      events,
      eventContent: (arg) =>
        renderAgendaEvent(
          arg,
          this.puedeRegistrar,
          data?.zona_horaria ?? environment.timezone,
        ),
      eventClick: (info: EventClickInfo) => {
        const target = info.jsEvent?.target;
        const boton = target instanceof Element
          ? (target.closest('[data-asistencia]') as HTMLElement | null)
          : null;

        if (boton !== null) {
          info.jsEvent?.preventDefault();
          info.jsEvent?.stopPropagation();
          const estado = boton.dataset['asistencia'];

          if (estado === 'asistio' || estado === 'falto') {
            this.asistenciaRapida.emit({
              turnoId: Number(info.event.id),
              estado,
            });
          }

          return;
        }

        info.jsEvent?.preventDefault();
        this.turnoClick.emit(Number(info.event.id));
      },
      eventDidMount: (arg: any) => {
        const turno = arg.event.extendedProps?.['turno'] as TurnoAgenda | undefined;

        if (turno !== undefined) {
          const tooltip = buildTurnoTooltip(turno);
          arg.el.setAttribute('title', tooltip);
          arg.el.setAttribute('aria-label', tooltip);
        }
      },
    };
  }

  private toEvents(data: AgendaSemanalData): EventInput[] {
    return data.turnos.map((turno) => ({
      id: String(turno.id),
      title: `${turno.paciente.nombre_completo ?? 'No disponible'} · ${
        turno.servicio.nombre ?? 'No disponible'
      }`,
      start: calendarStart(turno.fecha, turno.hora_inicio),
      end: calendarStart(turno.fecha, turno.hora_fin),
      allDay: false,
      backgroundColor: 'transparent',
      borderColor: 'transparent',
      extendedProps: { turno },
    }));
  }
}
