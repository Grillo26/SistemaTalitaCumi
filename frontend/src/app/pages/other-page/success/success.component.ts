import { Component } from '@angular/core';
import { ErrorPageLayoutComponent } from '../../../shared/components/common/error-page-layout/error-page-layout.component';

@Component({
  selector: 'app-success',
  standalone: true,
  imports: [ErrorPageLayoutComponent],
  template: `
    <app-error-page-layout
      title="¡OPERACIÓN EXITOSA!"
      message="La operación se completó correctamente."
      imageLight="/images/error/success.svg"
      imageDark="/images/error/success-dark.svg"
      imageAlt="success"
      imageWrapperClass="mx-auto mb-10 w-full max-w-[100px] sm:max-w-[160px]"
      [titleAbove]="false"
    />
  `,
})
export class SuccessComponent {}
