import { Component, computed, inject, signal } from '@angular/core';
import { AuthService } from '../../../core/services/auth.service';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { IndicadoresAsistenciaComponent } from './components/indicadores-asistencia/indicadores-asistencia.component';
import { ResumenDiarioAsistenciaComponent } from './components/resumen-diario-asistencia/resumen-diario-asistencia.component';

type Pestana = 'indicadores' | 'resumen';

@Component({
  selector: 'app-asistencia-page',
  imports: [
    AlertComponent,
    ComponentCardComponent,
    PageBreadcrumbComponent,
    IndicadoresAsistenciaComponent,
    ResumenDiarioAsistenciaComponent,
  ],
  templateUrl: './asistencia.page.html',
})
export class AsistenciaPageComponent {
  private readonly auth = inject(AuthService);

  readonly pestana = signal<Pestana>('indicadores');
  readonly puedeIndicadores = computed(() => this.auth.hasPermission('asistencia.ver_indicadores'));
  readonly puedeResumen = computed(() => this.auth.hasPermission('asistencia.resumen_diario'));

  constructor() {
    if (!this.puedeIndicadores() && this.puedeResumen()) {
      this.pestana.set('resumen');
    }
  }

  cambiarPestana(pestana: Pestana): void {
    if (pestana === 'indicadores' && !this.puedeIndicadores()) {
      return;
    }

    if (pestana === 'resumen' && !this.puedeResumen()) {
      return;
    }

    this.pestana.set(pestana);
  }
}
