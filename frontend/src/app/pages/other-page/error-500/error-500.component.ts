import { Component } from '@angular/core';
import { ErrorPageLayoutComponent } from '../../../shared/components/common/error-page-layout/error-page-layout.component';

@Component({
  selector: 'app-error-500',
  standalone: true,
  imports: [ErrorPageLayoutComponent],
  template: `
    <app-error-page-layout
      title="ERROR 500"
      message="Ocurrió un error interno. Intenta nuevamente en unos minutos."
      imageLight="/images/error/500.svg"
      imageDark="/images/error/500-dark.svg"
      imageAlt="500"
    />
  `,
})
export class Error500Component {}
