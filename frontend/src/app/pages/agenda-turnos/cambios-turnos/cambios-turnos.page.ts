import { Component, computed, inject, signal } from '@angular/core';
import { AuthService } from '../../../core/services/auth.service';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { CancelacionMasivaComponent } from './components/cancelacion-masiva/cancelacion-masiva.component';
import { ReporteCambiosComponent } from './components/reporte-cambios/reporte-cambios.component';
import { CAMBIOS_TEXTS } from './cambios-turnos.models';

type Pestana = 'masiva' | 'reporte';

@Component({
  selector: 'app-cambios-turnos-page',
  imports: [
    AlertComponent,
    ComponentCardComponent,
    PageBreadcrumbComponent,
    CancelacionMasivaComponent,
    ReporteCambiosComponent,
  ],
  templateUrl: './cambios-turnos.page.html',
})
export class CambiosTurnosPageComponent {
  private readonly auth = inject(AuthService);

  readonly texts = CAMBIOS_TEXTS;
  readonly pestana = signal<Pestana>('masiva');

  readonly puedeMasiva = computed(() => this.auth.hasPermission('turno.cancelar_masivo'));
  readonly puedeReporte = computed(() => this.auth.hasPermission('turno.reporte_cambios'));

  constructor() {
    if (!this.puedeMasiva() && this.puedeReporte()) {
      this.pestana.set('reporte');
    }
  }

  cambiarPestana(pestana: Pestana): void {
    if (pestana === 'masiva' && !this.puedeMasiva()) {
      return;
    }

    if (pestana === 'reporte' && !this.puedeReporte()) {
      return;
    }

    this.pestana.set(pestana);
  }
}
