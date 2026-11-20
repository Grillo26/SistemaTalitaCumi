import { Component, Input } from '@angular/core';
import { defaultInvoiceData } from './data';
import { InvoiceSummaryData } from './types';

@Component({
  selector: 'app-invoice-summary',
  imports: [],
  host: { class: 'flex w-full flex-col items-end' },
  template: `
    <div class="flex w-55 flex-col gap-4">
      <h4 class="text-start text-sm font-medium text-gray-800 dark:text-white/90">
        Resumen del comprobante
      </h4>
      <div class="flex flex-col gap-2 text-sm">
        <div class="flex items-center justify-between gap-5 leading-5">
          <span class="w-30 text-start text-gray-500 dark:text-gray-400">
            Subtotal
          </span>
          <span class="text-end font-medium text-gray-700 dark:text-gray-300">
            Bs {{ summary.subtotal.toLocaleString('es-BO') }}
          </span>
        </div>
        <div class="flex items-center justify-between gap-5 leading-5">
          <span class="w-30 text-start text-gray-500 dark:text-gray-400">
            IVA ({{ summary.vatRate }}%):
          </span>
          <span class="text-end font-medium text-gray-700 dark:text-gray-300">
            Bs {{ summary.vatAmount.toLocaleString('es-BO') }}
          </span>
        </div>
        <div class="flex items-center justify-between gap-5 pt-1">
          <span class="w-30 text-start text-base font-medium text-gray-700 dark:text-gray-300">
            Total
          </span>
          <span class="text-end text-title-sm font-semibold text-gray-800 dark:text-white/90">
            Bs {{ summary.total.toLocaleString('es-BO') }}
          </span>
        </div>
      </div>
    </div>
  `,
})
export class InvoiceSummaryComponent {
  @Input() summary: InvoiceSummaryData = defaultInvoiceData.summary;
}
