import { Component } from '@angular/core';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { TableTwoComponent } from '../components/basic-tables/table-two';

@Component({
  selector: 'app-basic-tables',
  imports: [PageBreadcrumbComponent, ComponentCardComponent, TableTwoComponent],
  host: {
    class: 'block',
  },
  template: `
    <div>
      <app-page-breadcrumb pageTitle="Tabla básica" />
      <div class="space-y-6">
        <app-component-card title="Tabla básica 2 — búsqueda y filtro">
          <app-table-two />
        </app-component-card>
      </div>
    </div>
  `,
})
export class BasicTablesComponent {}
