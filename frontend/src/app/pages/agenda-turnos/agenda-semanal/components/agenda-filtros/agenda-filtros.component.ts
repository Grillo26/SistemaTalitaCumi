import { Component, EventEmitter, Input, Output } from '@angular/core';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { SelectFieldComponent } from '../../../../../shared/components/ui/select/select-field.component';
import { AGENDA_TEXTS, ESTADO_OPTIONS } from '../../agenda-semanal.constants';
import type {
  AgendaFiltros,
  OpcionesFiltrosData,
} from '../../agenda-semanal.models';

type FiltroKey = keyof AgendaFiltros;

@Component({
  selector: 'app-agenda-filtros',
  imports: [ButtonComponent, SelectFieldComponent],
  host: { class: 'block' },
  template: `
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-5">
      <div>
        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">
          {{ texts.area }}
        </label>
        <app-select-field
          [options]="areaOptions"
          [value]="filtros.area_id"
          [placeholder]="texts.allAreas"
          [disabled]="disabled"
          ariaLabel="Filtrar por área"
          (valueChange)="onChange('area_id', $event)"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">
          {{ texts.room }}
        </label>
        <app-select-field
          [options]="salaOptions"
          [value]="filtros.sala_id"
          [placeholder]="texts.allRooms"
          [disabled]="disabled"
          ariaLabel="Filtrar por sala"
          (valueChange)="onChange('sala_id', $event)"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">
          {{ texts.professional }}
        </label>
        <app-select-field
          [options]="profesionalOptions"
          [value]="filtros.profesional_id"
          [placeholder]="texts.allProfessionals"
          [disabled]="disabled"
          ariaLabel="Filtrar por profesional"
          (valueChange)="onChange('profesional_id', $event)"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">
          {{ texts.service }}
        </label>
        <app-select-field
          [options]="servicioOptions"
          [value]="filtros.servicio_id"
          [placeholder]="texts.allServices"
          [disabled]="disabled"
          ariaLabel="Filtrar por servicio"
          (valueChange)="onChange('servicio_id', $event)"
        />
      </div>

      <div>
        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">
          {{ texts.state }}
        </label>
        <app-select-field
          [options]="estadoOptions"
          [value]="filtros.estado"
          [placeholder]="texts.allStates"
          [disabled]="disabled"
          ariaLabel="Filtrar por estado"
          (valueChange)="onChange('estado', $event)"
        />
      </div>
    </div>

    <div class="flex justify-end">
      <button app-button variant="ghost" size="sm" type="button" [disabled]="disabled" (click)="limpiar.emit()">
        {{ texts.clearFilters }}
      </button>
    </div>
  `,
})
export class AgendaFiltrosComponent {
  @Input() opciones: OpcionesFiltrosData | null = null;
  @Input() filtros: AgendaFiltros = {
    area_id: '',
    sala_id: '',
    profesional_id: '',
    servicio_id: '',
    estado: '',
  };
  @Input() disabled = false;

  @Output() filtrosChange = new EventEmitter<AgendaFiltros>();
  @Output() limpiar = new EventEmitter<void>();

  readonly texts = AGENDA_TEXTS;
  readonly estadoOptions = [{ value: '', label: AGENDA_TEXTS.allStates }, ...ESTADO_OPTIONS];

  get areaOptions(): { value: string; label: string }[] {
    return [
      { value: '', label: AGENDA_TEXTS.allAreas },
      ...(this.opciones?.areas ?? []).map((area) => ({
        value: String(area.id),
        label: area.nombre,
      })),
    ];
  }

  get salaOptions(): { value: string; label: string }[] {
    return [
      { value: '', label: AGENDA_TEXTS.allRooms },
      { value: 'sin_asignar', label: AGENDA_TEXTS.noRoom },
      ...(this.opciones?.salas ?? []).map((sala) => ({
        value: String(sala.id),
        label: sala.nombre,
      })),
    ];
  }

  get profesionalOptions(): { value: string; label: string }[] {
    return [
      { value: '', label: AGENDA_TEXTS.allProfessionals },
      { value: 'sin_asignar', label: AGENDA_TEXTS.unassigned },
      ...(this.opciones?.profesionales ?? []).map((profesional) => ({
        value: String(profesional.id),
        label: profesional.nombre_completo,
      })),
    ];
  }

  get servicioOptions(): { value: string; label: string }[] {
    const servicios =
      this.filtros.area_id === ''
        ? (this.opciones?.servicios ?? [])
        : (this.opciones?.servicios ?? []).filter(
            (servicio) => String(servicio.area_id) === this.filtros.area_id,
          );

    return [
      { value: '', label: AGENDA_TEXTS.allServices },
      ...servicios.map((servicio) => ({
        value: String(servicio.id),
        label: servicio.nombre,
      })),
    ];
  }

  onChange(key: FiltroKey, value: string): void {
    const next: AgendaFiltros = { ...this.filtros, [key]: value };

    if (key === 'area_id') {
      const services = this.opciones?.servicios ?? [];
      const stillValid = services.some(
        (servicio) =>
          String(servicio.id) === next.servicio_id &&
          (value === '' || String(servicio.area_id) === value),
      );

      if (!stillValid) {
        next.servicio_id = '';
      }
    }

    this.filtrosChange.emit(next);
  }
}
