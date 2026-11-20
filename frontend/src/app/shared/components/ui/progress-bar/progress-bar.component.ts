import { ChangeDetectionStrategy, Component, Input } from '@angular/core';

export type ProgressBarSize = 'sm' | 'md' | 'lg' | 'xl';
export type ProgressBarLabel = 'none' | 'outside' | 'inside';

/**
 * Reusable progress bar.
 *
 * Usage:
 *   <app-progress-bar [progress]="55" size="sm" label="none" />
 */
@Component({
  selector: 'app-progress-bar',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  host: { class: 'block' },
  template: `
    <div class="flex items-center" [class]="className">
      <div
        class="relative w-full rounded-full bg-gray-200 dark:bg-gray-800"
        [class]="sizeClass"
      >
        <div
          class="absolute start-0 h-full rounded-full bg-brand-500"
          [style.width.%]="progress"
          [class.flex]="label === 'inside'"
          [class.items-center]="label === 'inside'"
          [class.justify-center]="label === 'inside'"
        >
          @if (label === 'inside') {
            <span class="text-[10px] font-medium leading-tight text-white">
              {{ progress }}%
            </span>
          }
        </div>
      </div>

      @if (label === 'outside') {
        <span class="ms-3 text-sm font-medium text-gray-700 dark:text-gray-400">
          {{ progress }}%
        </span>
      }
    </div>
  `,
})
export class ProgressBarComponent {
  @Input() progress = 0;
  @Input() size: ProgressBarSize = 'sm';
  @Input() label: ProgressBarLabel = 'none';
  @Input() className = '';

  get sizeClass(): string {
    return {
      sm: 'h-2',
      md: 'h-3',
      lg: 'h-4',
      xl: 'h-5',
    }[this.size];
  }
}
