import { Component, EventEmitter, Input, Output } from '@angular/core';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { ComprobanteData, formatBs, formatDate } from './salas-grupales.models';

/**
 * Comprobante de inscripción no fiscal (RF-012 / CA-009).
 *
 * La impresión usa el navegador. El PDF real queda pendiente de
 * infraestructura y no se simula.
 */
@Component({
  selector: 'app-inscripcion-comprobante',
  imports: [ButtonComponent],
  host: { class: 'block' },
  template: `
    @if (data; as comprobante) {
      <div
        id="comprobante-print"
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
              Comprobante de inscripción
            </p>
            <p class="mt-1 text-sm font-medium">N.º {{ comprobante.inscripcion.id }}</p>
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
            <p class="mt-1 font-medium">{{ comprobante.inscripcion.paciente.nombre_completo }}</p>
            <p class="text-sm text-gray-500">
              {{ comprobante.inscripcion.paciente.codigo || 'Sin código' }}
            </p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
              Tutor responsable
            </p>
            <p class="mt-1 font-medium">{{ comprobante.inscripcion.tutor.nombre_completo }}</p>
            @if (comprobante.inscripcion.tutor.parentesco) {
              <p class="text-sm text-gray-500">{{ comprobante.inscripcion.tutor.parentesco }}</p>
            }
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Sucursal</p>
            <p class="mt-1 font-medium">{{ comprobante.inscripcion.sucursal.nombre }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Sala / turno</p>
            <p class="mt-1 font-medium">{{ comprobante.inscripcion.sala.nombre }}</p>
            <p class="text-sm text-gray-500">
              {{ comprobante.inscripcion.sala.tipo_sala || 'Sala grupal' }}
              @if (comprobante.inscripcion.sala.turno) {
                · {{ comprobante.inscripcion.sala.turno }}
              }
            </p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
              Servicio / categoría
            </p>
            <p class="mt-1 font-medium">{{ comprobante.inscripcion.servicio.nombre }}</p>
            <p class="text-sm text-gray-500">{{ comprobante.inscripcion.categoria.nombre }}</p>
          </div>
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Período</p>
            <p class="mt-1 font-medium">
              {{ formatDate(comprobante.inscripcion.fecha_inicio) }} —
              {{ formatDate(comprobante.inscripcion.fecha_fin) }}
            </p>
          </div>
        </div>

        <div
          class="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 pt-4 dark:border-gray-800"
        >
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">
              Monto esperado
            </p>
            <p class="mt-1 text-2xl font-semibold text-brand-600 dark:text-brand-400">
              {{ formatBs(comprobante.inscripcion.monto_esperado) }}
            </p>
            <p class="text-xs text-gray-500">
              {{ comprobante.moneda.codigo }} · {{ comprobante.inscripcion.estado_label }}
            </p>
          </div>
          <p class="max-w-xs text-right text-xs text-gray-400">
            Documento de inscripción. No constituye factura ni comprobante fiscal.
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
export class InscripcionComprobanteComponent {
  @Input() data: ComprobanteData | null = null;
  @Output() close = new EventEmitter<void>();

  protected readonly formatBs = formatBs;
  protected readonly formatDate = formatDate;

  print(): void {
    window.print();
  }
}
