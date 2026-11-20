import { CommonModule } from '@angular/common';
import { Component } from '@angular/core';

interface CategoryItem {
  name: string;
  color: string;
}

@Component({
  selector: 'app-bar-chart-seven',
  imports: [CommonModule],
  templateUrl: './bar-chart-seven.component.html',
})
export class BarChartSevenComponent {
  categories: CategoryItem[] = [
    { name: 'Activity', color: 'bg-[#A95AA2]' },
    { name: 'Online Purchases', color: 'bg-[#F9733F]' },
    { name: 'Groceries', color: 'bg-[#FFBD5D]' },
    { name: 'Digital Goods', color: 'bg-[#D27EBA]' },
    { name: 'Stationery', color: 'bg-[#6FEAA6]' },
    { name: 'Others', color: 'bg-[#D0D5DD] dark:bg-gray-700' },
  ];
}
