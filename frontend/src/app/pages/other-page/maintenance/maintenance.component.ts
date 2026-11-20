import { Component } from '@angular/core';
import { ErrorPageLayoutComponent } from '../../../shared/components/common/error-page-layout/error-page-layout.component';

@Component({
  selector: 'app-maintenance',
  standalone: true,
  imports: [ErrorPageLayoutComponent],
  template: `
    <app-error-page-layout
      title="MANTENIMIENTO"
      message="Estamos realizando tareas de mantenimiento. Volveremos muy pronto. Gracias por tu paciencia."
      imageLight="/images/error/maintenance.svg"
      imageDark="/images/error/maintenance-dark.svg"
      imageAlt="maintenance"
      imageWrapperClass="mx-auto mb-10 w-full max-w-[155px] sm:max-w-[204px]"
      [titleAbove]="false"
    />
  `,
})
export class MaintenanceComponent {}
