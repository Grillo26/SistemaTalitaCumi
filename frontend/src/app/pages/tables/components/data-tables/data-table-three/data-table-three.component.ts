import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';
import { BadgeComponent } from '../../../../../shared/components/ui/badge/badge.component';
import { PaginationComponent } from '../../../../../shared/components/ui/pagination/pagination.component';
import { ProgressBarComponent } from '../../../../../shared/components/ui/progress-bar/progress-bar.component';

interface Campaign {
  name: string;
  status: 'Activa' | 'Pausada' | 'Completada';
  progress: number;
}

@Component({
  selector: 'app-data-table-three',
  standalone: true,
  imports: [CommonModule, BadgeComponent, PaginationComponent, ProgressBarComponent],
  template: `
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
      <div class="max-w-full overflow-x-auto">
        <table class="w-full min-w-[680px]">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-800">
              <th class="px-6 py-3 text-start text-theme-sm font-medium text-gray-500 dark:text-gray-400">Campaña</th>
              <th class="px-6 py-3 text-start text-theme-sm font-medium text-gray-500 dark:text-gray-400">Estado</th>
              <th class="px-6 py-3 text-start text-theme-sm font-medium text-gray-500 dark:text-gray-400">Avance</th>
              <th class="px-6 py-3 text-center text-theme-sm font-medium text-gray-500 dark:text-gray-400">Acciones</th>
            </tr>
          </thead>
          <tbody>
            @for (campaign of campaigns; track campaign.name) {
              <tr class="border-b border-gray-100 dark:border-gray-800">
                <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-white/90">{{ campaign.name }}</td>
                <td class="px-6 py-4">
                  <app-badge size="sm" [color]="badgeColor(campaign.status)">{{ campaign.status }}</app-badge>
                </td>
                <td class="w-[260px] px-6 py-4">
                  <app-progress-bar [progress]="campaign.progress" size="sm" label="outside" />
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
export class DataTableThreeComponent {
  readonly campaigns: Campaign[] = [
    { name: 'Summer Sale', status: 'Activa', progress: 72 },
    { name: 'Newsletter Launch', status: 'Completada', progress: 100 },
    { name: 'Product Launch', status: 'Activa', progress: 45 },
    { name: 'Black Friday', status: 'Pausada', progress: 28 },
    { name: 'Holiday Promo', status: 'Activa', progress: 61 },
    { name: 'Spring Campaign', status: 'Completada', progress: 100 },
  ];

  page = 1;

  badgeColor(status: Campaign['status']): 'success' | 'warning' | 'info' {
    const colors: Record<Campaign['status'], 'success' | 'warning' | 'info'> = {
      Activa: 'success',
      Pausada: 'warning',
      Completada: 'info',
    };
    return colors[status];
  }
}
