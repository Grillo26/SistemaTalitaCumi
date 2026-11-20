import { Component } from '@angular/core';
import { ErrorPageLayoutComponent } from '../../../shared/components/common/error-page-layout/error-page-layout.component';

@Component({
  selector: 'app-error-503',
  standalone: true,
  imports: [ErrorPageLayoutComponent],
  template: `
    <app-error-page-layout
      title="SERVICIO NO DISPONIBLE"
      message="El servicio no está disponible temporalmente. Intenta más tarde."
      imageLight="/images/error/503.svg"
      imageDark="/images/error/503-dark.svg"
      imageAlt="503"
    />
  `,
})
export class Error503Component {}
