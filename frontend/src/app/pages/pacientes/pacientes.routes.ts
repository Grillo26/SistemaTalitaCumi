import { Routes } from '@angular/router';
import { permissionGuard } from '../../core/guards/permission.guard';
import { PacientesPageComponent } from './pacientes.page';
import { PacienteFichaPageComponent } from './paciente-ficha.page';
import { TutoresPageComponent } from './tutores.page';
import { TutorFichaPageComponent } from './tutor-ficha.page';
import { PacienteFichaIngresoPageComponent } from './paciente-ficha-ingreso.page';
import { PacienteResumenPageComponent } from './paciente-resumen.page';

export const PACIENTES_ROUTES: Routes = [
  {
    path: '',
    component: PacientesPageComponent,
    canActivate: [permissionGuard],
    data: { permission: 'paciente.ver' },
    title: 'Pacientes | TalitaCumi',
  },
  {
    path: 'tutores',
    component: TutoresPageComponent,
    canActivate: [permissionGuard],
    data: { permission: 'tutor.ver' },
    title: 'Tutores | TalitaCumi',
  },
  {
    path: 'tutores/:id',
    component: TutorFichaPageComponent,
    canActivate: [permissionGuard],
    data: { permission: 'tutor.ver' },
    title: 'Ficha de tutor | TalitaCumi',
  },
  {
    path: ':id/resumen',
    component: PacienteResumenPageComponent,
    canActivate: [permissionGuard],
    data: { permission: 'paciente.ver' },
    title: 'Ficha resumen | TalitaCumi',
  },
  {
    path: ':id/ficha-ingreso',
    component: PacienteFichaIngresoPageComponent,
    canActivate: [permissionGuard],
    data: { permission: 'ficha_ingreso.ver' },
    title: 'Ficha de ingreso | TalitaCumi',
  },
  {
    path: ':id',
    component: PacienteFichaPageComponent,
    canActivate: [permissionGuard],
    data: { permission: 'paciente.ver' },
    title: 'Ficha de paciente | TalitaCumi',
  },
];
