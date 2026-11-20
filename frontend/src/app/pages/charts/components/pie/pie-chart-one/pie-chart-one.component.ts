import { Component } from '@angular/core';
import {
  ApexChart,
  ApexDataLabels,
  ApexLegend,
  ApexPlotOptions,
  ApexStroke,
  ApexTooltip,
  NgApexchartsModule,
} from 'ng-apexcharts';

@Component({
  selector: 'app-pie-chart-one',
  standalone: true,
  imports: [NgApexchartsModule],
  template: `
    <div class="flex justify-center">
      <apx-chart
        [series]="series"
        [chart]="chart"
        [colors]="colors"
        [labels]="labels"
        [plotOptions]="plotOptions"
        [dataLabels]="dataLabels"
        [legend]="legend"
        [stroke]="stroke"
        [tooltip]="tooltip"
      ></apx-chart>
    </div>
  `,
})
export class PieChartOneComponent {
  readonly series: number[] = [45, 65, 25];

  readonly chart: ApexChart = {
    fontFamily: 'Outfit, sans-serif',
    type: 'donut',
    width: '100%',
    height: 290,
    animations: { enabled: false },
  };

  readonly colors = ['#671C61', '#A95AA2', '#eed9ee'];
  readonly labels = ['Desktop', 'Mobile', 'Tablet'];

  readonly plotOptions: ApexPlotOptions = {
    pie: {
      donut: {
        size: '65%',
        background: 'transparent',
      },
    },
  };

  readonly dataLabels: ApexDataLabels = { enabled: false };

  readonly legend: ApexLegend = {
    show: true,
    position: 'bottom',
    horizontalAlign: 'center',
    fontFamily: 'Outfit',
    fontSize: '14px',
    fontWeight: 400,
    markers: { size: 4, shape: 'circle', strokeWidth: 0 },
    itemMargin: { horizontal: 10, vertical: 0 },
  };

  readonly stroke: ApexStroke = { show: false, width: 4 };
  readonly tooltip: ApexTooltip = { enabled: false };
}
