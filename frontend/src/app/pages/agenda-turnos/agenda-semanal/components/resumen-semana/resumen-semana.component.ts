import { Component, Input } from '@angular/core';
import { BadgeComponent } from '../../../../../shared/components/ui/badge/badge.component';
import {
  AGENDA_TEXTS,
  ESTADO_BADGES,
  ESTADO_OPTIONS,
} from '../../agenda-semanal.constants';
import type { EstadoTurno, ResumenSemanaData } from '../../agenda-semanal.models';

@Component({
  selector: 'app-resumen-semana',
  imports: [BadgeComponent],
  host: { class: 'block' },
  template: `
    @if (resumen !== null) {
      <div class="rounded-xl border border-gray-200 bg-gray-50/60 p-4 dark:border-gray-800 dark:bg-white/[0.02]">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-white/90">
              {{ texts.summaryTitle }}
            </h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">{{ texts.summaryBase }}</p>
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <app-badge color="dark" variant="light" size="sm">
              {{ texts.summaryTotal }}: {{ resumen.total }}
            </app-badge>

            @for (option of estadoOptions; track option.value) {
              <app-badge [color]="badge(option.value).color" variant="light" size="sm">
                {{ badge(option.value).icon }} {{ option.label }}:
                {{ resumen.por_estado[option.value] }}
              </app-badge>
            }
          </div>
        </div>
      </div>
    }
  `,
})
export class ResumenSemanaComponent {
  @Input() resumen: ResumenSemanaData | null = null;

  readonly texts = AGENDA_TEXTS;
  readonly estadoOptions = ESTADO_OPTIONS;

  badge(estado: EstadoTurno): { color: 'primary' | 'success' | 'error' | 'warning' | 'gray' | 'info'; icon: string } {
    return ESTADO_BADGES[estado];
  }
}
