import { Component } from '@angular/core';
import { GridShapeComponent } from '../../../shared/components/common/grid-shape/grid-shape.component';
import { RouterModule } from '@angular/router';
import { ThemeToggleTwoComponent } from '../../../shared/components/common/theme-toggle-two/theme-toggle-two.component';
import { BrandLogoComponent } from '../../../shared/components/common/brand-logo/brand-logo.component';

@Component({
  selector: 'app-auth-page-layout',
  imports: [
    GridShapeComponent,
    RouterModule,
    ThemeToggleTwoComponent,
    BrandLogoComponent,
  ],
  templateUrl: './auth-page-layout.component.html',
  styles: ``
})
export class AuthPageLayoutComponent {

}
