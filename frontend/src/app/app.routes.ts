import { Routes } from '@angular/router';
import { authGuard } from './core/guards/auth.guard';
import { guestGuard } from './core/guards/guest.guard';
import { permissionGuard } from './core/guards/permission.guard';
import { AppLayoutComponent } from './layout/shell/app-layout/app-layout.component';
import { HomeComponent } from './pages/home/home.component';
import { AgendaComponent } from './pages/agenda/agenda.component';
import { PagosComponent } from './pages/pagos/pagos.component';
import { ReportesComponent } from './pages/reportes/reportes.component';
import { InventarioComponent } from './pages/inventario/inventario.component';

export const routes: Routes = [
  {
    path: 'login',
    canActivate: [guestGuard],
    loadComponent: () =>
      import('./pages/auth-pages/sign-in/sign-in.component').then((m) => m.SignInComponent),
    title: 'Iniciar sesión | TalitaCumi',
  },
  {
    path: '',
    component: AppLayoutComponent,
    canActivate: [authGuard],
    children: [
      {
        path: '',
        component: HomeComponent,
        pathMatch: 'full',
        title: 'Inicio | TalitaCumi',
      },
      {
        path: 'agenda',
        component: AgendaComponent,
        title: 'Agenda | TalitaCumi',
      },
      {
        path: 'pacientes',
        canActivate: [permissionGuard],
        data: { permissions: ['paciente.ver', 'tutor.ver', 'ficha_ingreso.ver'] },
        loadChildren: () =>
          import('./pages/pacientes/pacientes.routes').then((m) => m.PACIENTES_ROUTES),
      },
      {
        path: 'inscripciones',
        redirectTo: 'inscripciones-atenciones/salas-grupales',
        pathMatch: 'full',
      },
      {
        path: 'inscripciones-atenciones/salas-grupales',
        canActivate: [permissionGuard],
        data: { permission: 'inscripcion.ver' },
        loadChildren: () =>
          import('./pages/inscripciones-atenciones/salas-grupales/salas-grupales.routes').then(
            (m) => m.SALAS_GRUPALES_ROUTES,
          ),
        title: 'Inscripciones a salas grupales | TalitaCumi',
      },
      {
        path: 'pagos',
        component: PagosComponent,
        title: 'Pagos y caja | TalitaCumi',
      },
      {
        path: 'reportes',
        component: ReportesComponent,
        title: 'Reportes | TalitaCumi',
      },
      {
        path: 'catalogo-servicios/servicios',
        redirectTo: 'catalogo',
        pathMatch: 'full',
      },
      {
        path: 'catalogo-servicios/tarifas',
        canActivate: [permissionGuard],
        data: { permissions: ['tarifas.ver', 'categorias.ver'] },
        loadChildren: () =>
          import('./pages/catalogo-servicios/tarifas/tarifas.routes').then((m) => m.TARIFAS_ROUTES),
        title: 'Tarifas y categorías | TalitaCumi',
      },
      {
        path: 'catalogo-servicios/tipos-diagnostico',
        canActivate: [permissionGuard],
        data: { permission: 'tipos_diagnostico.ver' },
        loadChildren: () =>
          import('./pages/catalogo-servicios/tipos-diagnostico/tipos-diagnostico.routes').then(
            (m) => m.TIPOS_DIAGNOSTICO_ROUTES,
          ),
        title: 'Tipos de diagnóstico | TalitaCumi',
      },
      {
        path: 'catalogo',
        canActivate: [permissionGuard],
        data: { permissions: ['areas.ver', 'servicios.ver'] },
        loadChildren: () =>
          import('./pages/catalogo-servicios/servicios/servicios.routes').then(
            (m) => m.CATALOGO_SERVICIOS_ROUTES,
          ),
        title: 'Catálogo de servicios | TalitaCumi',
      },
      {
        path: 'profesionales',
        canActivate: [permissionGuard],
        data: { permission: 'profesional.ver' },
        loadChildren: () =>
          import('./pages/profesionales/profesionales.routes').then((m) => m.PROFESIONALES_ROUTES),
      },
      {
        path: 'inventario',
        component: InventarioComponent,
        title: 'Inventario | TalitaCumi',
      },
      {
        path: 'configuracion',
        redirectTo: 'organizacion/configuracion',
        pathMatch: 'full',
      },
      {
        path: 'cambiar-password',
        loadChildren: () =>
          import('./pages/seguridad/cambiar-password/cambiar-password.routes').then(
            (m) => m.CAMBIAR_PASSWORD_ROUTES,
          ),
        title: 'Cambiar contraseña | TalitaCumi',
      },
      {
        path: 'seguridad/usuarios',
        canActivate: [permissionGuard],
        data: { permission: 'usuario.ver' },
        loadChildren: () =>
          import('./pages/seguridad/usuarios/usuarios.routes').then((m) => m.USUARIOS_ROUTES),
      },
      {
        path: 'seguridad/roles-permisos',
        canActivate: [permissionGuard],
        data: { permission: 'rol.ver' },
        loadChildren: () =>
          import('./pages/seguridad/roles-permisos/roles-permisos.routes').then(
            (m) => m.ROLES_PERMISOS_ROUTES,
          ),
      },
      {
        path: 'seguridad/bitacora',
        canActivate: [permissionGuard],
        data: { permission: 'bitacora.ver' },
        loadChildren: () =>
          import('./pages/seguridad/bitacora/bitacora.routes').then((m) => m.BITACORA_ROUTES),
      },
      {
        path: 'organizacion/sucursales',
        loadChildren: () =>
          import('./pages/organizacion/sucursales/sucursales.routes').then(
            (m) => m.SUCURSALES_ROUTES,
          ),
      },
      {
        path: 'organizacion/salas-ambientes',
        loadChildren: () =>
          import('./pages/organizacion/salas-ambientes/salas-ambientes.routes').then(
            (m) => m.SALAS_AMBIENTES_ROUTES,
          ),
      },
      {
        path: 'organizacion/configuracion',
        loadChildren: () =>
          import('./pages/organizacion/configuracion/configuracion.routes').then(
            (m) => m.CONFIGURACION_ROUTES,
          ),
      },

      // Páginas de referencia de componentes reutilizables (fuera del menú).
      {
        path: 'shared',
        pathMatch: 'full',
        loadComponent: () =>
          import('./pages/reference/reference-index.component').then(
            (m) => m.ReferenceIndexComponent,
          ),
        title: 'Referencia UI | TalitaCumi',
      },
      {
        path: 'shared/form-elements',
        loadComponent: () =>
          import('./pages/forms/form-elements/form-elements.component').then(
            (m) => m.FormElementsComponent,
          ),
        title: 'Formularios | TalitaCumi',
      },
      {
        path: 'shared/basic-tables',
        loadComponent: () =>
          import('./pages/tables/basic-tables/basic-tables.component').then(
            (m) => m.BasicTablesComponent,
          ),
        title: 'Tabla básica | TalitaCumi',
      },
      {
        path: 'shared/data-tables',
        loadComponent: () =>
          import('./pages/tables/data-tables/data-tables.component').then(
            (m) => m.DataTablesComponent,
          ),
        title: 'Data Tables | TalitaCumi',
      },
      {
        path: 'shared/calendar',
        loadComponent: () =>
          import('./pages/calendar/calendar.component').then((m) => m.CalendarComponent),
        title: 'Calendario | TalitaCumi',
      },
      {
        path: 'shared/line-chart',
        loadComponent: () =>
          import('./pages/charts/line-chart/line-chart.component').then(
            (m) => m.LineChartComponent,
          ),
        title: 'Gráfico de líneas | TalitaCumi',
      },
      {
        path: 'shared/bar-chart',
        loadComponent: () =>
          import('./pages/charts/bar-chart/bar-chart.component').then((m) => m.BarChartComponent),
        title: 'Gráfico de barras | TalitaCumi',
      },
      {
        path: 'shared/pie-chart',
        loadComponent: () =>
          import('./pages/charts/pie-chart/pie-chart.component').then((m) => m.PieChartComponent),
        title: 'Gráfico circular | TalitaCumi',
      },
      {
        path: 'shared/buttons',
        loadComponent: () =>
          import('./pages/ui-elements/buttons/buttons.component').then((m) => m.ButtonsComponent),
        title: 'Botones | TalitaCumi',
      },
      {
        path: 'shared/badge',
        loadComponent: () =>
          import('./pages/ui-elements/badge/badge.component').then((m) => m.BadgesComponent),
        title: 'Badges | TalitaCumi',
      },
      {
        path: 'shared/alerts',
        loadComponent: () =>
          import('./pages/ui-elements/alerts/alerts.component').then((m) => m.AlertsComponent),
        title: 'Alertas y toasts | TalitaCumi',
      },
      {
        path: 'shared/invoice',
        loadComponent: () =>
          import('./pages/ecommerce/invoice/invoice.component').then((m) => m.InvoiceComponent),
        title: 'Comprobante de pago | TalitaCumi',
      },
      {
        path: 'shared/blank',
        loadComponent: () => import('./pages/blank/blank.component').then((m) => m.BlankComponent),
        title: 'Página en blanco | TalitaCumi',
      },
    ],
  },

  // Páginas de error a pantalla completa.
  {
    path: 'error-404',
    loadComponent: () =>
      import('./pages/other-page/not-found/not-found.component').then((m) => m.NotFoundComponent),
    title: 'Página no encontrada | TalitaCumi',
  },
  {
    path: 'error-403',
    loadComponent: () =>
      import('./pages/other-page/forbidden/forbidden.component').then((m) => m.ForbiddenComponent),
    title: 'Acceso denegado | TalitaCumi',
  },
  {
    path: 'error-500',
    loadComponent: () =>
      import('./pages/other-page/error-500/error-500.component').then((m) => m.Error500Component),
    title: 'Error del servidor | TalitaCumi',
  },
  {
    path: 'error-503',
    loadComponent: () =>
      import('./pages/other-page/error-503/error-503.component').then((m) => m.Error503Component),
    title: 'Servicio no disponible | TalitaCumi',
  },
  {
    path: 'mantenimiento',
    loadComponent: () =>
      import('./pages/other-page/maintenance/maintenance.component').then(
        (m) => m.MaintenanceComponent,
      ),
    title: 'Mantenimiento | TalitaCumi',
  },
  {
    path: 'exito',
    loadComponent: () =>
      import('./pages/other-page/success/success.component').then((m) => m.SuccessComponent),
    title: 'Operación completada | TalitaCumi',
  },
  {
    path: '**',
    loadComponent: () =>
      import('./pages/other-page/not-found/not-found.component').then((m) => m.NotFoundComponent),
    title: 'Página no encontrada | TalitaCumi',
  },
];
