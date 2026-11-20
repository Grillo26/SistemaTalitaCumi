import { Component } from '@angular/core';
import { RouterModule } from '@angular/router';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';

interface ReferenceLink {
  title: string;
  description: string;
  path: string;
}

interface ReferenceGroup {
  title: string;
  links: ReferenceLink[];
}

@Component({
  selector: 'app-reference-index',
  imports: [RouterModule, PageBreadcrumbComponent],
  template: `
    <div>
      <app-page-breadcrumb pageTitle="Referencia UI" />

      <div
        class="mb-6 rounded-2xl border border-brand-100 bg-brand-50 px-5 py-4 text-sm text-brand-700 dark:border-brand-500/20 dark:bg-brand-500/10 dark:text-brand-200"
      >
        Páginas de demostración de los componentes reutilizables. No aparecen en
        el menú principal porque son material de apoyo para construir los
        módulos del sistema.
      </div>

      <div class="space-y-8">
        @for (group of groups; track group.title) {
          <section>
            <h2
              class="mb-4 text-base font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400"
            >
              {{ group.title }}
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
              @for (link of group.links; track link.path) {
                <a
                  [routerLink]="link.path"
                  class="group rounded-2xl border border-gray-200 bg-white p-5 transition hover:border-brand-300 hover:shadow-theme-md dark:border-gray-800 dark:bg-white/[0.03] dark:hover:border-brand-500/40"
                >
                  <div class="flex items-start justify-between gap-4">
                    <div>
                      <h3
                        class="text-sm font-semibold text-gray-900 group-hover:text-brand-600 dark:text-white dark:group-hover:text-brand-300"
                      >
                        {{ link.title }}
                      </h3>
                      <p class="mt-1.5 text-sm leading-relaxed text-gray-500 dark:text-gray-400">
                        {{ link.description }}
                      </p>
                    </div>
                    <svg
                      class="mt-1 size-5 shrink-0 text-gray-400 transition-transform group-hover:translate-x-0.5 group-hover:text-brand-500"
                      viewBox="0 0 20 20"
                      fill="none"
                      stroke="currentColor"
                      stroke-width="1.6"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      aria-hidden="true"
                    >
                      <path d="M4 10h12M11 5l5 5-5 5" />
                    </svg>
                  </div>
                  <code class="mt-4 block truncate text-xs text-gray-400 dark:text-gray-500">{{ link.path }}</code>
                </a>
              }
            </div>
          </section>
        }
      </div>
    </div>
  `,
})
export class ReferenceIndexComponent {
  readonly groups: ReferenceGroup[] = [
    {
      title: 'Formularios y tablas',
      links: [
        {
          title: 'Elementos de formulario',
          description: 'Inputs, selects, textareas, checkbox, radio, switch, fecha, hora y estados de error.',
          path: '/shared/form-elements',
        },
        {
          title: 'Tabla básica 2',
          description: 'Tabla con búsqueda, filtro por estado y edición de filas.',
          path: '/shared/basic-tables',
        },
        {
          title: 'Data Tables',
          description: 'Tablas con paginación, badges de estado y ordenamiento de columnas.',
          path: '/shared/data-tables',
        },
      ],
    },
    {
      title: 'Calendario y gráficos',
      links: [
        {
          title: 'Calendario',
          description: 'Base de la agenda semanal con vistas de mes, semana y día.',
          path: '/shared/calendar',
        },
        {
          title: 'Gráfico de líneas',
          description: 'Evolución de ingresos y tendencias.',
          path: '/shared/line-chart',
        },
        {
          title: 'Gráfico de barras',
          description: 'Comparación de ingresos por período o área.',
          path: '/shared/bar-chart',
        },
        {
          title: 'Gráfico circular',
          description: 'Distribución de ingresos por categoría.',
          path: '/shared/pie-chart',
        },
      ],
    },
    {
      title: 'Elementos de interfaz',
      links: [
        {
          title: 'Botones',
          description: 'Variantes, tamaños, iconos y grupos de botones.',
          path: '/shared/buttons',
        },
        {
          title: 'Badges',
          description: 'Estados con variantes sólidas, suaves y con iconos.',
          path: '/shared/badge',
        },
        {
          title: 'Alertas y toasts',
          description: 'Alertas informativas y notificaciones flotantes.',
          path: '/shared/alerts',
        },
      ],
    },
    {
      title: 'Páginas de apoyo',
      links: [
        {
          title: 'Factura',
          description: 'Base del comprobante de pago imprimible.',
          path: '/shared/invoice',
        },
        {
          title: 'Página en blanco',
          description: 'Plantilla vacía para nuevas vistas.',
          path: '/shared/blank',
        },
        {
          title: 'Error 404',
          description: 'Página no encontrada.',
          path: '/error-404',
        },
        {
          title: 'Error 500',
          description: 'Error interno del servidor.',
          path: '/error-500',
        },
        {
          title: 'Error 503',
          description: 'Servicio no disponible.',
          path: '/error-503',
        },
        {
          title: 'Mantenimiento',
          description: 'Aviso de mantenimiento programado.',
          path: '/mantenimiento',
        },
        {
          title: 'Éxito',
          description: 'Confirmación de operación completada.',
          path: '/exito',
        },
      ],
    },
  ];
}
