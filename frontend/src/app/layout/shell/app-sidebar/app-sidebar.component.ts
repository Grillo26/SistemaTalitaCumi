import { CommonModule } from '@angular/common';
import { Component, inject } from '@angular/core';
import { Router, RouterModule } from '@angular/router';
import { combineLatest, map, take } from 'rxjs';
import { SidebarService } from '../../../core/services/sidebar.service';
import { SafeHtmlPipe } from '../../../shared/pipe/safe-html.pipe';
import { BrandLogoComponent } from '../../../shared/components/common/brand-logo/brand-logo.component';

interface NavItem {
  name: string;
  path: string;
  icon: string;
}

@Component({
  selector: 'app-sidebar',
  imports: [CommonModule, RouterModule, SafeHtmlPipe, BrandLogoComponent],
  templateUrl: './app-sidebar.component.html',
})
export class AppSidebarComponent {
  readonly sidebarService = inject(SidebarService);
  private readonly router = inject(Router);

  readonly isExpanded$ = this.sidebarService.isExpanded$;
  readonly isMobileOpen$ = this.sidebarService.isMobileOpen$;
  readonly isHovered$ = this.sidebarService.isHovered$;
  readonly isVisible$ = combineLatest([
    this.isExpanded$,
    this.isHovered$,
    this.isMobileOpen$,
  ]).pipe(map(([expanded, hovered, mobileOpen]) => expanded || hovered || mobileOpen));

  readonly navItems: NavItem[] = [
    {
      name: 'Inicio',
      path: '/',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M3.25 10.5 12 3.25l8.75 7.25"/><path d="M5.25 9.5V20a.75.75 0 0 0 .75.75h4.25V15h3.5v5.75H18a.75.75 0 0 0 .75-.75V9.5"/></svg>',
    },
    {
      name: 'Organización',
      path: '/organizacion/sucursales',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 21V8l8-5 8 5v13"/><path d="M9 21v-6h6v6"/><path d="M4 8h16"/></svg>',
    },
    {
      name: 'Salas y ambientes',
      path: '/organizacion/salas-ambientes',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3.25" y="3.25" width="17.5" height="17.5" rx="2"/><path d="M8.5 3.25v17.5M13 8h4M13 12h4M13 16h4"/></svg>',
    },
    {
      name: 'Agenda',
      path: '/agenda',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3.25" y="4.75" width="17.5" height="16" rx="2"/><path d="M7.5 2.75v4M16.5 2.75v4M3.25 9.25h17.5"/><path d="M7.5 13h2M11 13h2M14.5 13h2M7.5 16.5h2M11 16.5h2"/></svg>',
    },
    {
      name: 'Pacientes',
      path: '/pacientes',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="8" r="3.25"/><path d="M3.25 20.25a5.75 5.75 0 0 1 11.5 0"/><path d="M16.25 5.25a3 3 0 0 1 0 5.75M17.5 14.5a5.25 5.25 0 0 1 3.25 4.85"/></svg>',
    },
    {
      name: 'Inscripciones',
      path: '/inscripciones',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M8 3.25h8a2 2 0 0 1 2 2v14.5H6V5.25a2 2 0 0 1 2-2Z"/><path d="M9.5 8h5M9.5 11.5h5M9.5 15h3"/><path d="M16.5 18.25h3v-5.5l-2.5-2.5"/></svg>',
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
      path: '/catalogo',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3.25" y="3.25" width="7" height="7" rx="1.5"/><rect x="13.75" y="3.25" width="7" height="7" rx="1.5"/><rect x="3.25" y="13.75" width="7" height="7" rx="1.5"/><rect x="13.75" y="13.75" width="7" height="7" rx="1.5"/></svg>',
    },
    {
      name: 'Profesionales',
      path: '/profesionales',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="2.75" y="7.25" width="18.5" height="13" rx="2.25"/><path d="M8.25 7.25V5.5a1.75 1.75 0 0 1 1.75-1.75h4a1.75 1.75 0 0 1 1.75 1.75v1.75M2.75 12.25h18.5M10 12.25v2h4v-2"/></svg>',
    },
    {
      name: 'Inventario',
      path: '/inventario',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m12 2.75 8.25 4.5v9.5L12 21.25l-8.25-4.5v-9.5L12 2.75Z"/><path d="m3.75 7.25 8.25 4.5 8.25-4.5M12 11.75v9.5M7.5 4.75l8.25 4.5"/></svg>',
    },
    {
      name: 'Configuración',
      path: '/organizacion/configuracion',
      icon: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3.25"/><path d="M19.5 13.5a7.8 7.8 0 0 0 0-3l2-1.5-2-3.5-2.35.95a7.6 7.6 0 0 0-2.6-1.5L14.25 2h-4.5l-.3 2.45a7.6 7.6 0 0 0-2.6 1.5L4.5 5 2.5 8.5l2 1.5a7.8 7.8 0 0 0 0 3l-2 1.5 2 3.5 2.35-.95a7.6 7.6 0 0 0 2.6 1.5l.3 2.45h4.5l.3-2.45a7.6 7.6 0 0 0 2.6-1.5l2.35.95 2-3.5-2-1.5Z"/></svg>',
    },
  ];

  isActive(path: string): boolean {
    return path === '/' ? this.router.url === '/' : this.router.url.startsWith(path);
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
}
