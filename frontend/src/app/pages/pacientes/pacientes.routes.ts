import { Routes } from '@angular/router';
import { PacientesPageComponent } from './pacientes.page';
import { PacienteFichaPageComponent } from './paciente-ficha.page';

export const PACIENTES_ROUTES: Routes = [
  {
    path: '',
    component: PacientesPageComponent,
    title: 'Pacientes | TalitaCumi',
  },
  {
    path: ':id',
    component: PacienteFichaPageComponent,
    title: 'Ficha de paciente | TalitaCumi',
  },
];
