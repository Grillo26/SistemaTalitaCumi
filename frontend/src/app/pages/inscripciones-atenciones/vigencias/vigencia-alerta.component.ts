import { Component, Input } from '@angular/core';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { ResumenVigenciaPaciente, formatDate } from './vigencias.models';

/**
 * [Vigencia y Renovación] RF-015.
 *
 * Componente reutilizable para la ficha del paciente. No integra la pantalla
 * de Pacientes por sí solo.
 */
@Component({
  selector: 'app-vigencia-alerta',
  imports: [BadgeComponent],
  host: { class: 'block' },
  template: `
    @if (resumen; as data) {
      <div
        class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]"
      >
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-sm font-semibold text-gray-800 dark:text-white/90">Alerta de vigencia</p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
              Inscripciones y paquetes del paciente.
            </p>
          </div>
          <div class="flex flex-wrap gap-2">
            <app-badge color="warning" variant="light" size="sm">
              Por vencer: {{ data.conteos.por_vencer }}
            </app-badge>
            <app-badge color="error" variant="light" size="sm">
              Vencidos: {{ data.conteos.vencido }}
            </app-badge>
            <app-badge color="info" variant="light" size="sm">
              Renovados: {{ data.conteos.renovado }}
            </app-badge>
          </div>
        </div>

        @if (data.items.length === 0) {
          <p class="mt-4 text-sm text-gray-500 dark:text-gray-400">
            Sin vencimientos próximos ni entidades vencidas.
          </p>
        } @else {
          <div class="mt-4 divide-y divide-gray-100 dark:divide-gray-800">
            @for (item of data.items; track item.tipo_entidad + '-' + item.id) {
              <div class="flex flex-wrap items-center justify-between gap-2 py-2">
                <div>
                  <p class="text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ item.servicio.nombre }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ item.tipo_entidad_label }} · {{ formatDate(item.fecha_fin) }}
                  </p>
                </div>
                <app-badge
                  [color]="
                    item.estado_vigencia === 'vencido'
                      ? 'error'
                      : item.estado_vigencia === 'por_vencer'
                        ? 'warning'
                        : 'info'
                  "
                  variant="light"
                  size="sm"
                >
                  {{ item.estado_vigencia_label }}
                </app-badge>
              </div>
            }
          </div>
        }
      </div>
    }
  `,
})
export class VigenciaAlertaComponent {
  @Input() resumen: ResumenVigenciaPaciente | null = null;

  protected readonly formatDate = formatDate;
}
