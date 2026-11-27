import { Component, EventEmitter, Input, Output } from '@angular/core';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import {
  ComprobanteAtencionData,
  formatBs,
  formatDate,
} from './entrevista-diagnostico.models';

/**
 * Comprobante no fiscal de una entrevista o diagnóstico (RF-012 / CA-010).
 */
@Component({
  selector: 'app-atencion-comprobante',
  imports: [ButtonComponent],
  host: { class: 'block' },
  template: `
    @if (data; as comprobante) {
      <div
        id="comprobante-atencion-print"
        class="rounded-2xl border border-gray-200 bg-white p-6 text-gray-800 dark:border-gray-800 dark:bg-gray-900 dark:text-white/90"
      >
        <div
          class="flex flex-wrap items-start justify-between gap-4 border-b border-gray-200 pb-5 dark:border-gray-800"
        >
          <div class="flex items-center gap-4">
            @if (comprobante.centro.logo_url) {
              <img
                [src]="comprobante.centro.logo_url"
                alt="Logo del centro"
                class="h-16 w-16 rounded-xl object-contain"
              />
            }
            <div>
              <p class="text-lg font-semibold">{{ comprobante.centro.nombre }}</p>
              <p class="text-sm text-gray-500">{{ comprobante.centro.direccion }}</p>
              @if (comprobante.centro.telefono) {
                <p class="text-sm text-gray-500">Tel. {{ comprobante.centro.telefono }}</p>
              }
            </div>
          </div>
          <div class="text-right">
            <p class="text-xs font-semibold uppercase tracking-wide text-brand-500">
              Comprobante de {{ comprobante.tipo_label }}
            </p>
            <p class="mt-1 text-sm font-medium">N.º {{ comprobante.atencion.id }}</p>
            <p class="text-xs text-gray-500">
              Emitido: {{ formatDate(comprobante.fecha_emision) }}
            </p>
          </div>
        </div>

        <div
          class="mt-5 rounded-xl border border-warning-200 bg-warning-50 p-3 text-center text-sm font-semibold text-warning-700 dark:border-warning-500/30 dark:bg-warning-500/10 dark:text-warning-400"
        >
          {{ comprobante.leyenda_no_factura }}
        </div>

        <div class="mt-5 grid gap-5 sm:grid-cols-2">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Paciente</p>
            <p class="mt-1 font-medium">{{ comprobante.atencion.paciente.nombre_completo }}</p>
            <p class="text-sm text-gray-500">
              {{ comprobante.atencion.paciente.codigo || 'Sin código' }}
            </p>
          </div>
          @if (comprobante.atencion.tutor; as tutor) {
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                Tutor responsable
              </p>
              <p class="mt-1 font-medium">{{ tutor.nombre_completo }}</p>
              @if (tutor.parentesco) {
                <p class="text-sm text-gray-500">{{ tutor.parentesco }}</p>
              }
            </div>
          }
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Sucursal</p>
            <p class="mt-1 font-medium">{{ comprobante.atencion.sucursal.nombre }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Servicio</p>
            <p class="mt-1 font-medium">{{ comprobante.atencion.servicio.nombre }}</p>
            <p class="text-sm text-gray-500">{{ comprobante.atencion.categoria.nombre }}</p>
          </div>
          @if (comprobante.atencion.tipo_diagnostico; as tipo) {
            <div>
              <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                Tipo de diagnóstico
              </p>
              <p class="mt-1 font-medium">{{ tipo.nombre }}</p>
              <p class="text-sm text-gray-500">{{ tipo.dias_estimados }} día(s) estimado(s)</p>
            </div>
          }
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
              {{ comprobante.tipo === 'entrevista' ? 'Fecha' : 'Período' }}
            </p>
            <p class="mt-1 font-medium">
              @if (comprobante.atencion.fecha) {
                {{ formatDate(comprobante.atencion.fecha) }}
              } @else {
                {{ formatDate(comprobante.atencion.fecha_inicio) }} —
                {{ formatDate(comprobante.atencion.fecha_fin) }}
              }
            </p>
          </div>
          @if (comprobante.atencion.profesionales.length > 0) {
            <div class="sm:col-span-2">
              <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
                Profesionales
              </p>
              <div class="mt-2 flex flex-wrap gap-2">
                @for (profesional of comprobante.atencion.profesionales; track profesional.id) {
                  <span
                    class="rounded-lg border border-gray-200 px-2.5 py-1 text-sm text-gray-700 dark:border-gray-700 dark:text-gray-300"
                  >
                    {{ profesional.nombre_completo }}
                  </span>
                }
              </div>
            </div>
          }
        </div>

        <div
          class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4 dark:border-gray-800"
        >
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
              Monto esperado
            </p>
            <p class="mt-1 text-2xl font-semibold text-brand-600 dark:text-brand-400">
              {{ formatBs(comprobante.atencion.monto_esperado) }}
            </p>
            <p class="text-xs text-gray-500">
              {{ comprobante.moneda.codigo }} · {{ comprobante.atencion.estado_label }}
            </p>
          </div>
          <p class="max-w-xs text-right text-xs text-gray-400">
            Documento de atención. No constituye factura ni comprobante fiscal.
          </p>
        </div>
      </div>

      <div class="mt-4 flex flex-wrap justify-end gap-2 print:hidden">
        <button app-button variant="outline" type="button" (click)="close.emit()">Cerrar</button>
        <button app-button type="button" (click)="print()">Imprimir comprobante</button>
      </div>
    }
  `,
})
export class AtencionComprobanteComponent {
  @Input() data: ComprobanteAtencionData | null = null;
  @Output() close = new EventEmitter<void>();

  protected readonly formatBs = formatBs;
  protected readonly formatDate = formatDate;

  print(): void {
    window.print();
  }
}
