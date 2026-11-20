import { Component } from '@angular/core';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { PieChartOneComponent } from '../components/pie/pie-chart-one/pie-chart-one.component';
import { PieChartTwoComponent } from '../components/pie/pie-chart-two/pie-chart-two.component';

@Component({
  selector: 'app-pie-chart',
  standalone: true,
  imports: [
    PageBreadcrumbComponent,
    ComponentCardComponent,
    PieChartOneComponent,
    PieChartTwoComponent,
  ],
  template: `
    <div>
      <app-page-breadcrumb pageTitle="Gráfico circular" />

      <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <app-component-card title="Gráfico circular 1">
          <app-pie-chart-one />
        </app-component-card>

        <app-component-card title="Gráfico circular 2">
          <app-pie-chart-two />
        </app-component-card>
      </div>
    </div>
  `,
})
export class PieChartComponent {}
