import { CommonModule } from '@angular/common';
import { Component, DestroyRef, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { NavigationEnd, Router, RouterModule } from '@angular/router';
import { combineLatest, filter, map, take } from 'rxjs';
import { SidebarService } from '../../../core/services/sidebar.service';
import { AuthService } from '../../../core/services/auth.service';
import { SafeHtmlPipe } from '../../../shared/pipe/safe-html.pipe';
import { BrandLogoComponent } from '../../../shared/components/common/brand-logo/brand-logo.component';

interface NavChild {
  name: string;
  path: string;
  permission?: string;
}

interface NavItem {
  name: string;
  path: string;
  icon: string;
  permission?: string;
  children?: NavChild[];
}

// TEMPORAL: icono usado por el grupo de referencia. Eliminar junto con referenceNavItems.
const REFERENCE_ICON =
  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9.75 3.25h4.5M12 3.25v3.5"/><path d="M4.25 9.75h15.5v10.5H4.25z"/><path d="M8 13.5h8M8 16.5h5"/></svg>';

@Component({
  selector: 'app-sidebar',
  imports: [CommonModule, RouterModule, SafeHtmlPipe, BrandLogoComponent],
  templateUrl: './app-sidebar.component.html',
})
export class AppSidebarComponent {
  readonly sidebarService = inject(SidebarService);
  private readonly router = inject(Router);
  private readonly auth = inject(AuthService);
  private readonly destroyRef = inject(DestroyRef);

  readonly isExpanded$ = this.sidebarService.isExpanded$;
  readonly isMobileOpen$ = this.sidebarService.isMobileOpen$;
  readonly isHovered$ = this.sidebarService.isHovered$;
  readonly isVisible$ = combineLatest([this.isExpanded$, this.isHovered$, this.isMobileOpen$]).pipe(
    map(([expanded, hovered, mobileOpen]) => expanded || hovered || mobileOpen),
  );

  readonly expandedGroups = signal<ReadonlySet<string>>(new Set());

  constructor() {
    this.openActiveGroups();

    this.router.events
      .pipe(
        filter((event): event is NavigationEnd => event instanceof NavigationEnd),
        takeUntilDestroyed(this.destroyRef),
      )
      .subscribe(() => this.openActiveGroups());
  }

  readonly allNavItems: NavItem[] = [
    {
      name: 'Inicio',
      path: '/',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3.25 10.5 12 3.25l8.75 7.25"/><path d="M5.25 9.5V20a.75.75 0 0 0 .75.75h4.25V15h3.5v5.75H18a.75.75 0 0 0 .75-.75V9.5"/></svg>',
    },
    {
      name: 'Organización',
      path: '',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V8l8-5 8 5v13"/><path d="M9 21v-6h6v6"/><path d="M4 8h16"/></svg>',
      children: [
        {
          name: 'Sucursales',
          path: '/organizacion/sucursales',
        },
        {
          name: 'Salas y ambientes',
          path: '/organizacion/salas-ambientes',
        },
        {
          name: 'Configuración',
          path: '/organizacion/configuracion',
        },
      ],
    },
    {
      name: 'Agenda',
      path: '/agenda-turnos/agenda-semanal',
      permission: 'turno.ver',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3.25" y="4.75" width="17.5" height="16" rx="2"/><path d="M7.5 2.75v4M16.5 2.75v4M3.25 9.25h17.5"/><path d="M7.5 13h2M11 13h2M14.5 13h2M7.5 16.5h2M11 16.5h2"/></svg>',
    },
    {
      name: 'Programación',
      path: '/agenda-turnos/programacion-turnos',
      permission: 'turno.ver',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3.25" y="4.75" width="17.5" height="16" rx="2"/><path d="M7.5 2.75v4M16.5 2.75v4M3.25 9.25h17.5"/><path d="m9 15 2 2 4-4"/></svg>',
    },
    {
      name: 'Cambios de turnos',
      path: '/agenda-turnos/cambios-turnos',
      permission: 'turno.cancelar_masivo',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 7h11M4 12h7M4 17h11"/><path d="m16 10 4 4-4 4"/><path d="M20 14h-5"/></svg>',
    },
    {
      name: 'Pacientes',
      path: '/pacientes',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.25"/><path d="M3.25 20.25a5.75 5.75 0 0 1 11.5 0"/><path d="M16.25 5.25a3 3 0 0 1 0 5.75M17.5 14.5a5.25 5.25 0 0 1 3.25 4.85"/></svg>',
      permission: 'paciente.ver',
    },
    {
      name: 'Tutores',
      path: '/pacientes/tutores',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="8.25" cy="8" r="3.25"/><path d="M2.75 20.25a5.5 5.5 0 0 1 11 0"/><path d="M15.25 6.25h5.5M15.25 10h5.5M15.25 13.75h3.5"/></svg>',
      permission: 'tutor.ver',
    },
    {
      name: 'Inscripciones',
      path: '',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3.25h8a2 2 0 0 1 2 2v14.5H6V5.25a2 2 0 0 1 2-2Z"/><path d="M9.5 8h5M9.5 11.5h5M9.5 15h3"/><path d="M16.5 18.25h3v-5.5l-2.5-2.5"/></svg>',
      children: [
        {
          name: 'Salas grupales',
          path: '/inscripciones-atenciones/salas-grupales',
          permission: 'inscripcion.ver',
        },
        {
          name: 'Paquetes de sesiones',
          path: '/inscripciones-atenciones/paquetes-sesiones',
          permission: 'paquete.ver',
        },
        {
          name: 'Entrevistas y diagnósticos',
          path: '/inscripciones-atenciones/entrevista-diagnostico',
          permission: 'atencion.ver',
        },
        {
          name: 'Vigencias y renovaciones',
          path: '/inscripciones-atenciones/vigencias',
          permission: 'vigencia.ver',
        },
        {
          name: 'Estados',
          path: '/inscripciones-atenciones/estados',
          permission: 'estado.ver',
        },
      ],
    },
    {
      name: 'Pagos y caja',
      path: '/pagos',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="2.75" y="5.25" width="18.5" height="13.5" rx="2.25"/><path d="M2.75 9.25h18.5M6.5 14.5h4"/></svg>',
    },
    {
      name: 'Reportes',
      path: '/reportes',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20.25V13.5M9.33 20.25V8.75M14.67 20.25V11M20 20.25V4.25"/><path d="M2.75 20.25h18.5"/></svg>',
    },
    {
      name: 'Catálogo',
      path: '',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3.25" y="3.25" width="7" height="7" rx="1.5"/><rect x="13.75" y="3.25" width="7" height="7" rx="1.5"/><rect x="3.25" y="13.75" width="7" height="7" rx="1.5"/><rect x="13.75" y="13.75" width="7" height="7" rx="1.5"/></svg>',
      children: [
        {
          name: 'Áreas y servicios',
          path: '/catalogo',
          permission: 'servicios.ver',
        },
        {
          name: 'Tarifas y categorías',
          path: '/catalogo-servicios/tarifas',
          permission: 'tarifas.ver',
        },
        {
          name: 'Tipos de diagnóstico',
          path: '/catalogo-servicios/tipos-diagnostico',
          permission: 'tipos_diagnostico.ver',
        },
      ],
    },
    {
      name: 'Profesionales',
      path: '/profesionales',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="2.75" y="7.25" width="18.5" height="13" rx="2.25"/><path d="M8.25 7.25V5.5a1.75 1.75 0 0 1 1.75-1.75h4a1.75 1.75 0 0 1 1.75 1.75v1.75M2.75 12.25h18.5M10 12.25v2h4v-2"/></svg>',
      permission: 'profesional.ver',
    },
    {
      name: 'Inventario',
      path: '/inventario',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2.75 8.25 4.5v9.5L12 21.25l-8.25-4.5v-9.5L12 2.75Z"/><path d="m3.75 7.25 8.25 4.5 8.25-4.5M12 11.75v9.5M7.5 4.75l8.25 4.5"/></svg>',
    },
  ];

  get navItems(): NavItem[] {
    return this.allNavItems
      .map((item) => {
        if (item.children?.length) {
          const children = item.children.filter((child) => this.canAccess(child));

          return children.length > 0 ? { ...item, children } : null;
        }

        return this.canAccess(item) ? item : null;
      })
      .filter((item): item is NavItem => item !== null);
  }

  readonly allSecurityNavItems: NavItem[] = [
    {
      name: 'Usuarios',
      path: '/seguridad/usuarios',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.25"/><path d="M3.25 20.25a5.75 5.75 0 0 1 11.5 0"/><path d="M16.25 5.25a3 3 0 0 1 0 5.75M17.5 14.5a5.25 5.25 0 0 1 3.25 4.85"/></svg>',
      permission: 'usuario.ver',
    },
    {
      name: 'Roles y permisos',
      path: '/seguridad/roles-permisos',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="8.25" cy="15.75" r="3.25"/><path d="m10.5 13.5 7.25-7.25M15.5 8.5l2 2M17.5 6.5l2 2"/><path d="M5 8.25h4M7 6.25v4"/></svg>',
      permission: 'rol.ver',
    },
    {
      name: 'Bitácora',
      path: '/seguridad/bitacora',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M6.25 3.25h9.5l3 3v14.5H6.25V3.25Z"/><path d="M15.25 3.25v3.5h3.5M9 11h6M9 14.5h6M9 18h3"/></svg>',
      permission: 'bitacora.ver',
    },
  ];

  get securityNavItems(): NavItem[] {
    return this.allSecurityNavItems.filter((item) => this.canAccess(item));
  }

  // TEMPORAL: referencia de componentes compartidos. Eliminar este bloque al terminar.
  readonly referenceNavItems: NavItem[] = [
    { name: 'Elementos de formulario', path: '/shared/form-elements', icon: REFERENCE_ICON },
    { name: 'Tabla básica', path: '/shared/basic-tables', icon: REFERENCE_ICON },
    { name: 'Data Tables', path: '/shared/data-tables', icon: REFERENCE_ICON },
    { name: 'Calendario', path: '/shared/calendar', icon: REFERENCE_ICON },
    { name: 'Gráfico de líneas', path: '/shared/line-chart', icon: REFERENCE_ICON },
    { name: 'Gráfico de barras', path: '/shared/bar-chart', icon: REFERENCE_ICON },
    { name: 'Gráfico circular', path: '/shared/pie-chart', icon: REFERENCE_ICON },
    { name: 'Botones', path: '/shared/buttons', icon: REFERENCE_ICON },
    { name: 'Badges', path: '/shared/badge', icon: REFERENCE_ICON },
    { name: 'Alertas y toasts', path: '/shared/alerts', icon: REFERENCE_ICON },
    { name: 'Factura', path: '/shared/invoice', icon: REFERENCE_ICON },
    { name: 'Página en blanco', path: '/shared/blank', icon: REFERENCE_ICON },
    { name: 'Error 404', path: '/error-404', icon: REFERENCE_ICON },
    { name: 'Error 500', path: '/error-500', icon: REFERENCE_ICON },
    { name: 'Error 503', path: '/error-503', icon: REFERENCE_ICON },
    { name: 'Mantenimiento', path: '/mantenimiento', icon: REFERENCE_ICON },
    { name: 'Éxito', path: '/exito', icon: REFERENCE_ICON },
  ];

  isActive(path: string): boolean {
    const url = this.router.url.split('?')[0].split('#')[0];

    if (path === '/') {
      return url === '/';
    }

    if (path === '/pacientes') {
      return (
        url === '/pacientes' ||
        (url.startsWith('/pacientes/') && !url.startsWith('/pacientes/tutores'))
      );
    }

    if (path === '/pacientes/tutores') {
      return url === '/pacientes/tutores' || url.startsWith('/pacientes/tutores/');
    }

    return url === path || url.startsWith(`${path}/`);
  }

  isGroupActive(item: NavItem): boolean {
    return item.children?.some((child) => this.isActive(child.path)) ?? false;
  }

  isGroupOpen(item: NavItem): boolean {
    return this.expandedGroups().has(item.name);
  }

  toggleGroup(item: NavItem): void {
    const next = new Set(this.expandedGroups());

    if (next.has(item.name)) {
      next.delete(item.name);
    } else {
      next.add(item.name);
    }

    this.expandedGroups.set(next);
  }

  onSidebarMouseEnter(): void {
    this.isExpanded$.pipe(take(1)).subscribe((expanded) => {
      if (!expanded) this.sidebarService.setHovered(true);
    });
  }

  closeMobile(): void {
    if (typeof window !== 'undefined' && window.innerWidth < 1280) {
      this.sidebarService.setMobileOpen(false);
    }
  }

  private openActiveGroups(): void {
    const activeGroups = this.allNavItems.filter(
      (item) => item.children?.length && this.isGroupActive(item),
    );

    if (activeGroups.length === 0) {
      return;
    }

    this.expandedGroups.update((groups) => {
      if (activeGroups.every((group) => groups.has(group.name))) {
        return groups;
      }

      const next = new Set(groups);

      for (const group of activeGroups) {
        next.add(group.name);
      }

      return next;
    });
  }

  private canAccess(item: { permission?: string }): boolean {
    return item.permission === undefined || this.auth.hasPermission(item.permission);
  }
}
