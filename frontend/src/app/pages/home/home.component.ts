import { Component } from '@angular/core';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';

@Component({
  selector: 'app-home',
  imports: [PageBreadcrumbComponent],
  template: `
    <div>
      <app-page-breadcrumb pageTitle="Inicio" />

      <div
        class="flex min-h-[420px] flex-col items-center justify-center rounded-2xl border border-gray-200 bg-white px-6 py-12 text-center dark:border-gray-800 dark:bg-white/[0.03]"
      >
        <div
          class="mb-5 grid size-16 place-items-center rounded-2xl bg-brand-500 text-white shadow-theme-md"
        >
          <svg
            class="size-8"
            viewBox="0 0 32 32"
            fill="none"
            aria-hidden="true"
          >
            <circle cx="16" cy="8.5" r="5" fill="currentColor" />
            <circle cx="23" cy="14" r="5" fill="currentColor" />
            <circle cx="20.5" cy="22.5" r="5" fill="currentColor" />
            <circle cx="11.5" cy="22.5" r="5" fill="currentColor" />
            <circle cx="9" cy="14" r="5" fill="currentColor" />
            <circle cx="16" cy="16" r="4" fill="#ffbd5d" />
          </svg>
        </div>

        <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">
          Bienvenido a TalitaCumi
        </h1>
        <p class="mt-2 max-w-lg text-sm leading-relaxed text-gray-500 dark:text-gray-400">
          Selecciona un módulo del menú lateral para comenzar. Los módulos se
          habilitarán progresivamente.
        </p>
      </div>
    </div>
  `,
})
export class HomeComponent {}
