import { A11yModule } from '@angular/cdk/a11y';
import { Component, EventEmitter, Input, Output } from '@angular/core';
import { AlertComponent } from '../../../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import {
  AGENDA_TEXTS,
  ESTADO_BADGES,
} from '../../agenda-semanal.constants';
import type { TurnoDetalleVista } from '../../agenda-semanal.models';
import {
  formatLongDate,
  formatTimeRange,
  pacienteLabel,
  profesionalLabel,
  salaLabel,
  sanitizeHexColor,
  servicioLabel,
} from '../../agenda-semanal.utils';

@Component({
  selector: 'app-turno-detalle-panel',
  imports: [A11yModule, AlertComponent, BadgeComponent, ButtonComponent],
  host: { class: 'contents' },
  template: `
    @if (open) {
      <div
        class="fixed inset-0 z-[99999] flex justify-end bg-gray-900/50"
        role="presentation"
        (click)="close.emit()"
      >
        <aside
          class="flex h-full w-full max-w-xl flex-col overflow-hidden bg-white shadow-2xl dark:bg-gray-900"
          role="dialog"
          aria-modal="true"
          aria-labelledby="agenda-detalle-title"
          cdkTrapFocus
          [cdkTrapFocusAutoCapture]="true"
          (click)="$event.stopPropagation()"
        >
          <header class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 p-5 dark:border-gray-800">
            <div>
              <h2 id="agenda-detalle-title" class="text-lg font-semibold text-gray-800 dark:text-white/90">
                {{ texts.detailTitle }}
              </h2>
              @if (detalle !== null) {
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                  {{ formatLongDate(detalle.turno.fecha) }} ·
                  {{ formatTimeRange(detalle.turno.hora_inicio, detalle.turno.hora_fin) }}
                </p>
              }
            </div>

            <button
              app-button
              variant="ghost"
              size="icon-sm"
              type="button"
              [ariaLabel]="texts.close"
              (click)="close.emit()"
            >
              ✕
            </button>
          </header>

          <div class="min-h-0 flex-1 overflow-y-auto p-5">
            @if (loading) {
              <p class="text-sm text-gray-500 dark:text-gray-400">{{ texts.detailLoading }}</p>
            } @else if (error !== null) {
              <app-alert variant="error" [title]="texts.detailError" [message]="error" />
            } @else if (detalle !== null) {
              <div class="space-y-5">
                <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                  <div class="flex items-start gap-3">
                    <span
                      class="mt-1 size-3 shrink-0 rounded-full"
                      [style.background-color]="areaColor()"
                      aria-hidden="true"
                    ></span>
                    <div class="min-w-0">
                      <p class="text-lg font-semibold text-gray-800 dark:text-white/90">
                        {{ pacienteLabel(detalle.turno) }}
                      </p>
                      <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ servicioLabel(detalle.turno) }}
                        @if (detalle.turno.servicio.area_nombre !== null) {
                          · {{ detalle.turno.servicio.area_nombre }}
                        }
                      </p>
                    </div>
                  </div>

                  <div class="mt-4 flex flex-wrap items-center gap-2">
                    <app-badge [color]="estadoBadge.color" variant="light" size="sm">
                      {{ estadoBadge.icon }} {{ detalle.turno.estado_label }}
                    </app-badge>
                    @if (detalle.turno.sin_asignar) {
                      <app-badge color="warning" variant="light" size="sm">
                        {{ texts.unassigned }}
                      </app-badge>
                    }
                  </div>
                </div>

                <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                  <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">
                      {{ texts.patient }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                      {{ pacienteLabel(detalle.turno) }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">
                      {{ texts.service }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                      {{ servicioLabel(detalle.turno) }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">
                      {{ texts.room }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                      {{ salaLabel(detalle.turno) }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">
                      {{ texts.professional }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                      {{ profesionalLabel(detalle.turno) }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">
                      {{ texts.date }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                      {{ formatLongDate(detalle.turno.fecha) }}
                    </dd>
                  </div>

                  <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">
                      {{ texts.time }}
                    </dt>
                    <dd class="mt-1 text-sm text-gray-800 dark:text-white/90">
                      {{ formatTimeRange(detalle.turno.hora_inicio, detalle.turno.hora_fin) }}
                    </dd>
                  </div>
                </dl>

                <section>
                  <h3 class="mb-2 text-sm font-semibold text-gray-800 dark:text-white/90">
                    {{ texts.actions }}
                  </h3>

                  @if (detalle.acciones.length > 0) {
                    <div class="space-y-2">
                      @for (accion of detalle.acciones; track accion.codigo) {
                        <button
                          app-button
                          variant="outline"
                          type="button"
                          class="w-full"
                          (click)="accionSeleccionada.emit(accion.codigo)"
                        >
                          {{ accion.etiqueta }}
                        </button>
                      }
                    </div>
                  }

                  @if (detalle.mensaje !== null) {
                    <app-alert variant="info" [message]="detalle.mensaje" />
                  }
                </section>
              </div>
            }
          </div>
        </aside>
      </div>
    }
  `,
})
export class TurnoDetallePanelComponent {
  @Input() open = false;
  @Input() detalle: TurnoDetalleVista | null = null;
  @Input() loading = false;
  @Input() error: string | null = null;

  @Output() close = new EventEmitter<void>();
  @Output() accionSeleccionada = new EventEmitter<string>();

  readonly texts = AGENDA_TEXTS;
  readonly formatLongDate = formatLongDate;
  readonly formatTimeRange = formatTimeRange;
  readonly pacienteLabel = pacienteLabel;
  readonly servicioLabel = servicioLabel;
  readonly salaLabel = salaLabel;
  readonly profesionalLabel = profesionalLabel;

  get estadoBadge(): { color: 'primary' | 'success' | 'error' | 'warning' | 'gray' | 'info'; icon: string } {
    return this.detalle === null
      ? { color: 'info', icon: '•' }
      : ESTADO_BADGES[this.detalle.turno.estado];
  }

  areaColor(): string {
    return sanitizeHexColor(this.detalle?.turno.servicio.area_color) ?? '#D0D5DD';
  }
}
