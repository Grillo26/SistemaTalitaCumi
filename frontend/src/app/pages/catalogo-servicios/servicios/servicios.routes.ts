import { Routes } from '@angular/router';
import { CatalogoServiciosPageComponent } from './servicios.page';

export const CATALOGO_SERVICIOS_ROUTES: Routes = [
  {
    path: '',
    component: CatalogoServiciosPageComponent,
    title: 'Catálogo de servicios | TalitaCumi',
  },
];
