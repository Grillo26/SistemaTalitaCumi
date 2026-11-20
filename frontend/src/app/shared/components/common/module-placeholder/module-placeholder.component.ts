import { Component, input } from '@angular/core';

/**
 * Estado vacío para los módulos que todavía no tienen implementación.
 */
@Component({
  selector: 'app-module-placeholder',
  host: { class: 'block' },
  template: `
    <div
      class="flex min-h-[420px] flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-12 text-center dark:border-gray-700 dark:bg-white/[0.03]"
    >
      <div
        class="mb-5 flex size-14 items-center justify-center rounded-2xl bg-brand-50 text-brand-500 dark:bg-brand-500/15 dark:text-brand-300"
      >
        <svg
          class="size-7"
          viewBox="0 0 24 24"
          fill="none"
          stroke="currentColor"
          stroke-width="1.6"
          stroke-linecap="round"
          stroke-linejoin="round"
          aria-hidden="true"
        >
          <path d="M4 5.25A1.25 1.25 0 0 1 5.25 4h5.5A1.25 1.25 0 0 1 12 5.25v14.5a2.25 2.25 0 0 0-2.25-2.25h-5.5A1.25 1.25 0 0 1 3 16.25v-8.5" />
          <path d="M20 5.25A1.25 1.25 0 0 0 18.75 4h-5.5A1.25 1.25 0 0 0 12 5.25v14.5a2.25 2.25 0 0 1 2.25-2.25h5.5A1.25 1.25 0 0 0 21 16.25v-8.5" />
        </svg>
      </div>

      <h2 class="text-xl font-semibold text-gray-900 dark:text-white">
        {{ title() }}
      </h2>
      <p class="mt-2 max-w-lg text-sm leading-relaxed text-gray-500 dark:text-gray-400">
        {{ description() }}
      </p>
      <span
        class="mt-6 inline-flex items-center gap-2 rounded-full bg-gray-100 px-3 py-1.5 text-xs font-medium text-gray-600 dark:bg-white/5 dark:text-gray-300"
      >
        <span class="size-1.5 rounded-full bg-highlight-500"></span>
        Módulo pendiente de implementación
      </span>
    </div>
  `,
})
export class ModulePlaceholderComponent {
  readonly title = input('Módulo');
  readonly description = input('Este módulo se implementará en una siguiente fase.');
}
