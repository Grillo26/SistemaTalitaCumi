import { Component } from '@angular/core';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ModulePlaceholderComponent } from '../../shared/components/common/module-placeholder/module-placeholder.component';

@Component({
  selector: 'app-pacientes',
  imports: [PageBreadcrumbComponent, ModulePlaceholderComponent],
  template: `
    <app-page-breadcrumb pageTitle="Pacientes" />
    <app-module-placeholder
      title="Pacientes"
      description="Ficha, búsqueda y seguimiento de los niños y niñas del centro."
    />
  `,
})
export class PacientesComponent {}
