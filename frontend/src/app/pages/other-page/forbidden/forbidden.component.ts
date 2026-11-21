import { Component } from '@angular/core';
import { RouterModule } from '@angular/router';
import { GridShapeComponent } from '../../../shared/components/common/grid-shape/grid-shape.component';

/**
 * Página 403 para el guard de permisos (RF-R06).
 */
@Component({
  selector: 'app-forbidden',
  imports: [RouterModule, GridShapeComponent],
  host: { class: 'block w-full' },
  template: `
    <div class="relative z-1 flex min-h-screen flex-col items-center justify-center overflow-hidden p-6">
      <app-grid-shape />

      <div class="mx-auto w-full max-w-lg text-center">
        <div class="mx-auto mb-6 grid size-20 place-items-center rounded-full bg-error-50 text-error-500 dark:bg-error-500/10 dark:text-error-400">
          <svg
            width="40"
            height="40"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="1.6"
            stroke-linecap="round"
            stroke-linejoin="round"
            aria-hidden="true"
          >
            <rect x="4" y="10" width="16" height="10" rx="2" />
            <path d="M8 10V7a4 4 0 0 1 8 0v3" />
          </svg>
        </div>

        <h1 class="text-title-md font-bold text-gray-800 xl:text-title-2xl dark:text-white/90">
          Acceso denegado
        </h1>
        <p class="mb-8 mt-4 text-base text-gray-600 dark:text-gray-400">
          No tenés permisos para acceder a esta sección. Si creés que es un error,
          contactá al Superadmin.
        </p>

        <a
          routerLink="/"
          class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-5 py-3.5 text-sm font-medium text-white transition-colors hover:bg-brand-600"
        >
          Volver al inicio
        </a>
      </div>

      <p class="absolute inset-x-0 bottom-6 text-center text-sm text-gray-500 dark:text-gray-400">
        &copy; {{ year }} - TalitaCumi
      </p>
    </div>
  `,
})
export class ForbiddenComponent {
  readonly year = new Date().getFullYear();
}
