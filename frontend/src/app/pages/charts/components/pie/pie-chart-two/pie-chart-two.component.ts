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
  selector: 'app-pie-chart-two',
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
export class PieChartTwoComponent {
  readonly series: number[] = [45, 65, 25, 25];

  readonly chart: ApexChart = {
    fontFamily: 'Outfit, sans-serif',
    type: 'donut',
    width: '100%',
    height: 290,
    animations: { enabled: false },
  };

  readonly colors = ['#A95AA2', '#fd853a', '#fdb022', '#32d583'];
  readonly labels = ['Downloads', 'Apps', 'Documents', 'Media'];

  readonly plotOptions: ApexPlotOptions = {
    pie: {
      donut: {
        size: '65%',
        background: 'transparent',
      },
      expandOnClick: false,
    },
  };

  readonly dataLabels: ApexDataLabels = { enabled: false };

  readonly legend: ApexLegend = {
    show: true,
    position: 'bottom',
    horizontalAlign: 'left',
    fontFamily: 'Outfit, sans-serif',
    fontSize: '14px',
    fontWeight: 400,
    markers: { size: 6, shape: 'circle', strokeWidth: 0 },
    itemMargin: { horizontal: 10, vertical: 6 },
  };

  readonly stroke: ApexStroke = {
    show: false,
    width: 4,
    colors: ['transparent'],
  };

  readonly tooltip: ApexTooltip = { enabled: false };
}
