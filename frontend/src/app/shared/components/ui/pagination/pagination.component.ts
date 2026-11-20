import {
  ChangeDetectionStrategy,
  Component,
  EventEmitter,
  Input,
  Output,
} from '@angular/core';
import { CommonModule } from '@angular/common';

export type PaginationVariant = 'default' | 'icon' | 'text' | 'text-icon';

type PageItem = number | 'ellipsis';

/**
 * Reusable pagination control.
 *
 * Usage:
 *   <app-pagination
 *     [currentPage]="page"
 *     [totalPages]="10"
 *     variant="text-icon"
 *     (pageChange)="page = $event"
 *   />
 */
@Component({
  selector: 'app-pagination',
  standalone: true,
  imports: [CommonModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'block' },
  template: `
    <div class="flex items-center justify-between gap-2 px-6 py-4 sm:justify-normal">
      <button
        type="button"
        [disabled]="currentPage === 1"
        (click)="previous()"
        class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 disabled:cursor-not-allowed disabled:opacity-50 sm:px-3.5 sm:py-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
        aria-label="Página anterior"
      >
        @if (showIcon) {
          <svg class="size-5 fill-current rtl:rotate-180" viewBox="0 0 20 20" aria-hidden="true">
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M2.58203 9.99868C2.58174 10.1909 2.6549 10.3833 2.80152 10.53L7.79818 15.5301C8.09097 15.8231 8.56584 15.8233 8.85883 15.5305C9.15183 15.2377 9.152 14.7629 8.85921 14.4699L5.13911 10.7472L16.6665 10.7472C17.0807 10.7472 17.4165 10.4114 17.4165 9.99715C17.4165 9.58294 17.0807 9.24715 16.6665 9.24715L5.14456 9.24715L8.85919 5.53016C9.15199 5.23717 9.15184 4.7623 8.85885 4.4695C8.56587 4.1767 8.09099 4.17685 7.79819 4.46984L2.84069 9.43049C2.68224 9.568 2.58203 9.77087 2.58203 9.99715C2.58203 9.99766 2.58203 9.99817 2.58203 9.99868Z"
            />
          </svg>
        }
        @if (showText) {
          <span class="hidden sm:inline">Anterior</span>
        }
      </button>

      <span class="block text-sm font-medium text-gray-700 sm:hidden dark:text-gray-400">
        Página {{ currentPage }} de {{ totalPages }}
      </span>

      <ul class="hidden items-center gap-0.5 sm:flex">
        @for (page of visiblePages; track $index) {
          <li>
            @if (page === 'ellipsis') {
              <span class="flex size-10 items-center justify-center text-sm font-medium text-gray-700 dark:text-gray-400">
                ...
              </span>
            } @else {
              <button
                type="button"
                (click)="goTo(page)"
                [attr.aria-current]="page === currentPage ? 'page' : null"
                class="flex size-10 items-center justify-center rounded-lg text-sm font-medium transition-colors"
                [class]="
                  page === currentPage
                    ? 'bg-brand-500 text-white hover:bg-brand-600'
                    : 'text-gray-700 hover:bg-brand-500 hover:text-white dark:text-gray-400 dark:hover:text-white'
                "
              >
                {{ page }}
              </button>
            }
          </li>
        }
      </ul>

      <button
        type="button"
        [disabled]="currentPage === totalPages"
        (click)="next()"
        class="flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-2 py-2 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 disabled:cursor-not-allowed disabled:opacity-50 sm:px-3.5 sm:py-2.5 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
        aria-label="Página siguiente"
      >
        @if (showText) {
          <span class="hidden sm:inline">Siguiente</span>
        }
        @if (showIcon) {
          <svg class="size-5 fill-current rtl:rotate-180" viewBox="0 0 20 20" aria-hidden="true">
            <path
              fill-rule="evenodd"
              clip-rule="evenodd"
              d="M17.4165 9.9986C17.4168 10.1909 17.3437 10.3832 17.197 10.53L12.2004 15.5301C11.9076 15.8231 11.4327 15.8233 11.1397 15.5305C10.8467 15.2377 10.8465 14.7629 11.1393 14.4699L14.8594 10.7472L3.33203 10.7472C2.91782 10.7472 2.58203 10.4114 2.58203 9.99715C2.58203 9.58294 2.91782 9.24715 3.33203 9.24715L14.854 9.24715L11.1393 5.53016C10.8465 5.23717 10.8467 4.7623 11.1397 4.4695C11.4327 4.1767 11.9075 4.17685 12.2003 4.46984L17.1578 9.43049C17.3163 9.568 17.4165 9.77087 17.4165 9.99715C17.4165 9.99763 17.4165 9.99812 17.4165 9.9986Z"
            />
          </svg>
        }
      </button>
    </div>
  `,
})
export class PaginationComponent {
  @Input() currentPage = 1;
  @Input() totalPages = 10;
  @Input() variant: PaginationVariant = 'default';

  @Output() pageChange = new EventEmitter<number>();

  get showText(): boolean {
    return this.variant === 'text' || this.variant === 'text-icon';
  }

  get showIcon(): boolean {
    return this.variant === 'icon' || this.variant === 'text-icon' || this.variant === 'default';
  }

  get visiblePages(): PageItem[] {
    const total = this.totalPages;
    const current = this.currentPage;

    if (total <= 7) {
      return Array.from({ length: total }, (_, i) => i + 1);
    }

    if (current <= 3) {
      return [1, 2, 3, 4, 5, 'ellipsis', total];
    }

    if (current >= total - 2) {
      return [1, 'ellipsis', total - 4, total - 3, total - 2, total - 1, total];
    }

    return [1, 'ellipsis', current - 1, current, current + 1, 'ellipsis', total];
  }

  goTo(page: number): void {
    if (page < 1 || page > this.totalPages || page === this.currentPage) {
      return;
    }
    this.pageChange.emit(page);
  }

  previous(): void {
    this.goTo(this.currentPage - 1);
  }

  next(): void {
    this.goTo(this.currentPage + 1);
  }
}
