import type { EventInput } from 'fullcalendar';

export type CalendarEventLevel =
  | 'Primary'
  | 'Secondary'
  | 'Accent'
  | 'Highlight'
  | 'Danger'
  | 'Success'
  | 'Warning'
  | 'Info';

export interface CalendarEvent extends EventInput {
  extendedProps: {
    calendar: string;
  };
}

export interface CalendarViewOption {
  key: string;
  label: string;
}

export interface EventFormData {
  title: string;
  start: string;
  end: string;
  level: string;
}

export const CALENDAR_EVENT_LEVELS: Record<CalendarEventLevel, string> = {
  Primary: 'primary',
  Secondary: 'secondary',
  Accent: 'accent',
  Highlight: 'highlight',
  Danger: 'danger',
  Success: 'success',
  Warning: 'warning',
  Info: 'info',
};

export const CALENDAR_EVENT_LEVEL_LABELS: Record<CalendarEventLevel, string> = {
  Primary: 'Primario',
  Secondary: 'Secundario',
  Accent: 'Acento',
  Highlight: 'Resaltado',
  Danger: 'Peligro',
  Success: 'Éxito',
  Warning: 'Advertencia',
  Info: 'Info',
};

export const CALENDAR_VIEW_OPTIONS: CalendarViewOption[] = [
  { key: 'multiMonthYear', label: 'Año' },
  { key: 'dayGridMonth', label: 'Mes' },
  { key: 'timeGridWeek', label: 'Semana' },
  { key: 'timeGridDay', label: 'Día' },
];
