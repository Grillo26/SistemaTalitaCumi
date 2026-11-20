import { Component } from '@angular/core';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { DataTableOneComponent } from '../components/data-tables/data-table-one/data-table-one.component';
import { DataTableTwoComponent } from '../components/data-tables/data-table-two/data-table-two.component';
import { DataTableThreeComponent } from '../components/data-tables/data-table-three/data-table-three.component';

@Component({
  selector: 'app-data-tables',
  standalone: true,
  imports: [
    PageBreadcrumbComponent,
    ComponentCardComponent,
    DataTableOneComponent,
    DataTableTwoComponent,
    DataTableThreeComponent,
  ],
  template: `
    <div>
      <app-page-breadcrumb pageTitle="Data Tables" />

      <div class="space-y-5 sm:space-y-6">
        <app-component-card title="Data Table 1 — ordenamiento y paginación">
          <app-data-table-one />
        </app-component-card>

        <app-component-card title="Data Table 2">
          <app-data-table-two />
        </app-component-card>

        <app-component-card title="Data Table 3">
          <app-data-table-three />
        </app-component-card>
      </div>
    </div>
  `,
})
export class DataTablesComponent {}
