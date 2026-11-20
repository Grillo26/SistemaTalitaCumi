import { Component } from '@angular/core';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ModulePlaceholderComponent } from '../../shared/components/common/module-placeholder/module-placeholder.component';

@Component({
  selector: 'app-agenda',
  imports: [PageBreadcrumbComponent, ModulePlaceholderComponent],
  template: `
    <app-page-breadcrumb pageTitle="Agenda" />
    <app-module-placeholder
      title="Agenda"
      description="Aquí se construirá la agenda semanal con la grilla de turnos y colores por área."
    />
  `,
})
export class AgendaComponent {}
