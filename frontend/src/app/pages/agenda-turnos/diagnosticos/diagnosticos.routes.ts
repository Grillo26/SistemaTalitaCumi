import { Routes } from '@angular/router';
import { DiagnosticosPageComponent } from './diagnosticos.page';
import { DiagnosticoCoordinacionPageComponent } from './diagnostico-coordinacion/diagnostico-coordinacion.page';

export const DIAGNOSTICOS_ROUTES: Routes = [
  {
    path: '',
    component: DiagnosticosPageComponent,
    title: 'Diagnósticos | TalitaCumi',
  },
  {
    path: ':id',
    component: DiagnosticoCoordinacionPageComponent,
    title: 'Coordinación del diagnóstico | TalitaCumi',
  },
];
