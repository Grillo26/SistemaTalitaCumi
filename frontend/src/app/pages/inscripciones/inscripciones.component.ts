import { Component } from '@angular/core';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ModulePlaceholderComponent } from '../../shared/components/common/module-placeholder/module-placeholder.component';

@Component({
  selector: 'app-inscripciones',
  imports: [PageBreadcrumbComponent, ModulePlaceholderComponent],
  template: `
    <app-page-breadcrumb pageTitle="Inscripciones" />
    <app-module-placeholder
      title="Inscripciones"
      description="Altas, renovaciones y documentación de inscripción."
    />
  `,
})
export class InscripcionesComponent {}
