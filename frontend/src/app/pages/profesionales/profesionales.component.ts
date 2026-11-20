import { Component } from '@angular/core';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ModulePlaceholderComponent } from '../../shared/components/common/module-placeholder/module-placeholder.component';

@Component({
  selector: 'app-profesionales',
  imports: [PageBreadcrumbComponent, ModulePlaceholderComponent],
  template: `
    <app-page-breadcrumb pageTitle="Profesionales" />
    <app-module-placeholder
      title="Profesionales"
      description="Datos, especialidades y disponibilidad del equipo."
    />
  `,
})
export class ProfesionalesComponent {}
