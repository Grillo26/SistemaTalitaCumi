import { Component } from '@angular/core';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ModulePlaceholderComponent } from '../../shared/components/common/module-placeholder/module-placeholder.component';

@Component({
  selector: 'app-configuracion',
  imports: [PageBreadcrumbComponent, ModulePlaceholderComponent],
  template: `
    <app-page-breadcrumb pageTitle="Configuración" />
    <app-module-placeholder
      title="Configuración"
      description="Parámetros generales, usuarios y preferencias del sistema."
    />
  `,
})
export class ConfiguracionComponent {}
