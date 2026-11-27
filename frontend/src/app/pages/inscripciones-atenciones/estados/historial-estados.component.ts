import { Component, Input } from '@angular/core';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import {
  ESTADOS_TEXTS,
  HistorialEstadoItem,
  formatDateTime,
} from './estados.models';

@Component({
  selector: 'app-historial-estados',
  imports: [BadgeComponent],
  host: { class: 'block' },
  template: `
    @if (items.length === 0) {
      <p class="text-sm text-gray-500 dark:text-gray-400">{{ texts.noHistory }}</p>
    } @else {
      <ol class="relative space-y-4 border-s border-gray-200 ps-5 dark:border-gray-800">
        @for (item of items; track item.fecha + '-' + item.accion + '-' + $index) {
          <li class="relative">
            <span
              class="absolute -start-[1.65rem] top-1 size-3 rounded-full border-2 border-white bg-brand-500 dark:border-gray-900"
            ></span>
            <div class="flex flex-wrap items-center gap-2">
              <app-badge color="gray" variant="light" size="sm">{{ item.accion }}</app-badge>
              @if (item.estado_anterior_label) {
                <span class="text-xs text-gray-500">{{ item.estado_anterior_label }}</span>
                <span class="text-xs text-gray-400">→</span>
              }
              <span class="text-sm font-medium text-gray-800 dark:text-white/90">
                {{ item.estado_nuevo_label || '—' }}
              </span>
              @if (item.origen === 'sistema') {
                <app-badge color="info" variant="light" size="sm">Sistema</app-badge>
              }
            </div>
            @if (item.motivo_label) {
              <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">{{ item.motivo_label }}</p>
            }
            <p class="mt-1 text-xs text-gray-500">
              {{ formatDateTime(item.fecha) }}
              @if (item.usuario) {
                · {{ item.usuario }}
              }
            </p>
          </li>
        }
      </ol>
    }
  `,
})
export class HistorialEstadosComponent {
  @Input() items: HistorialEstadoItem[] = [];

  readonly texts = ESTADOS_TEXTS;
  readonly formatDateTime = formatDateTime;
}
