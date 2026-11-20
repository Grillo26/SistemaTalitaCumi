import { Component, input } from '@angular/core';

/**
 * Marca de TalitaCumi. Se usa en el sidebar, header móvil y pantalla de login.
 *
 * <app-brand-logo />
 * <app-brand-logo variant="light" [compact]="true" />
 */
@Component({
  selector: 'app-brand-logo',
  host: { class: 'inline-flex shrink-0' },
  template: `
    <span class="inline-flex items-center gap-2.5">
      <span
        class="grid shrink-0 place-items-center rounded-xl shadow-theme-xs"
        [class]="markClass()"
      >
        <svg
          viewBox="0 0 32 32"
          fill="none"
          xmlns="http://www.w3.org/2000/svg"
          aria-hidden="true"
          [class]="size() === 'sm' ? 'size-6' : 'size-7'"
        >
          <circle cx="16" cy="8.5" r="5" [class]="petalClass()" />
          <circle cx="23" cy="14" r="5" [class]="petalClass()" />
          <circle cx="20.5" cy="22.5" r="5" [class]="petalClass()" />
          <circle cx="11.5" cy="22.5" r="5" [class]="petalClass()" />
          <circle cx="9" cy="14" r="5" [class]="petalClass()" />
          <circle cx="16" cy="16" r="4" [class]="centerClass()" />
        </svg>
      </span>

      @if (!compact()) {
        <span class="flex min-w-0 flex-col">
          <span
            class="truncate font-semibold leading-none tracking-tight"
            [class]="size() === 'sm' ? 'text-base' : 'text-xl'"
            [class.text-white]="variant() === 'light'"
            [class.text-gray-900]="variant() !== 'light'"
            [class.dark:text-white]="variant() !== 'light'"
          >
            TalitaCumi
          </span>
          @if (showTagline()) {
            <span
              class="mt-1 truncate text-[9px] font-medium uppercase tracking-[0.18em]"
              [class.text-white/60]="variant() === 'light'"
              [class.text-gray-500]="variant() !== 'light'"
              [class.dark:text-gray-400]="variant() !== 'light'"
            >
              Sistema de Gestión
            </span>
          }
        </span>
      }
    </span>
  `,
  styles: `
    :host {
      display: inline-flex;
    }
  `,
})
export class BrandLogoComponent {
  readonly variant = input<'default' | 'light'>('default');
  readonly compact = input(false);
  readonly showTagline = input(true);
  readonly size = input<'sm' | 'md'>('md');

  markClass(): string {
    return this.variant() === 'light'
      ? 'size-10 bg-white text-brand-500'
      : 'size-10 bg-brand-500 text-white';
  }

  petalClass(): string {
    return this.variant() === 'light' ? 'fill-brand-500' : 'fill-white';
  }

  centerClass(): string {
    return this.variant() === 'light' ? 'fill-accent-500' : 'fill-highlight-500';
  }
}
