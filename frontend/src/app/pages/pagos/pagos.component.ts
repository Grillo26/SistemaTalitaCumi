import { Component } from '@angular/core';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ModulePlaceholderComponent } from '../../shared/components/common/module-placeholder/module-placeholder.component';

@Component({
  selector: 'app-pagos',
  imports: [PageBreadcrumbComponent, ModulePlaceholderComponent],
  template: `
    <app-page-breadcrumb pageTitle="Pagos y caja" />
    <app-module-placeholder
      title="Pagos y caja"
      description="Cobros, caja diaria, comprobantes y medios de pago."
    />
  `,
})
export class PagosComponent {}
