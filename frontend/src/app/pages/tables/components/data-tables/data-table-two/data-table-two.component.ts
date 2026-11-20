import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { BadgeComponent } from '../../../../../shared/components/ui/badge/badge.component';
import { PaginationComponent } from '../../../../../shared/components/ui/pagination/pagination.component';

interface OrderRow {
  id: string;
  customer: string;
  product: string;
  price: string;
  status: 'Pagado' | 'Pendiente' | 'Anulado';
}

@Component({
  selector: 'app-data-table-two',
  standalone: true,
  imports: [CommonModule, BadgeComponent, PaginationComponent],
  template: `
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="max-w-full overflow-x-auto">
        <table class="w-full min-w-[760px]">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-800">
              <th class="px-6 py-3 text-start text-theme-sm font-medium text-gray-500 dark:text-gray-400">N.º de pago</th>
              <th class="px-6 py-3 text-start text-theme-sm font-medium text-gray-500 dark:text-gray-400">Paciente</th>
              <th class="px-6 py-3 text-start text-theme-sm font-medium text-gray-500 dark:text-gray-400">Concepto</th>
              <th class="px-6 py-3 text-start text-theme-sm font-medium text-gray-500 dark:text-gray-400">Importe</th>
              <th class="px-6 py-3 text-start text-theme-sm font-medium text-gray-500 dark:text-gray-400">Estado</th>
              <th class="px-6 py-3 text-center text-theme-sm font-medium text-gray-500 dark:text-gray-400">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @for (order of orders; track order.id) {
              <tr class="border-b border-gray-100 dark:border-gray-800">
                <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-white/90">{{ order.id }}</td>
                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400">{{ order.customer }}</td>
                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400">{{ order.product }}</td>
                <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-white/90">{{ order.price }}</td>
                <td class="px-6 py-4">
                  <app-badge size="sm" [color]="badgeColor(order.status)">{{ order.status }}</app-badge>
                </td>
                <td class="px-6 py-4 text-center">
                  <button type="button" class="text-gray-500 hover:text-brand-500" aria-label="Open actions">
                    <svg class="size-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                      <circle cx="4" cy="10" r="1.5" /><circle cx="10" cy="10" r="1.5" /><circle cx="16" cy="10" r="1.5" />
                    </svg>
                  </button>
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>

      <app-pagination
        [currentPage]="page"
        [totalPages]="5"
        variant="icon"
        (pageChange)="page = $event"
      />
    </div>
  `,
})
export class DataTableTwoComponent {
  readonly orders: OrderRow[] = [
    { id: '#ORD-001', customer: 'Lindsey Curtis', product: 'Wireless Headphones', price: 'Bs 120.00', status: 'Pagado' },
    { id: '#ORD-002', customer: 'Kaiya George', product: 'Smart Watch', price: 'Bs 250.00', status: 'Pendiente' },
    { id: '#ORD-003', customer: 'Zain Geidt', product: 'Mechanical Keyboard', price: 'Bs 89.00', status: 'Pagado' },
    { id: '#ORD-004', customer: 'Abram Schleifer', product: 'USB-C Hub', price: 'Bs 45.00', status: 'Anulado' },
    { id: '#ORD-005', customer: 'Charlotte Anderson', product: 'Monitor Stand', price: 'Bs 75.00', status: 'Pendiente' },
    { id: '#ORD-006', customer: 'Ethan Brown', product: 'Laptop Sleeve', price: 'Bs 35.00', status: 'Pagado' },
  ];

  page = 1;

  badgeColor(status: OrderRow['status']): 'success' | 'warning' | 'error' {
    const colors: Record<OrderRow['status'], 'success' | 'warning' | 'error'> = {
      Pagado: 'success',
      Pendiente: 'warning',
      Anulado: 'error',
    };
    return colors[status];
  }
}
