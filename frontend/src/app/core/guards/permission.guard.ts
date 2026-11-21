import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

/**
 * Guard mínimo de permisos para rutas (RF-R06).
 *
 * La ruta declara `data: { permission: 'usuario.ver' }`. El backend sigue
 * siendo la fuente de verdad; esto solo evita abrir pantallas sin permiso.
 */
export const permissionGuard: CanActivateFn = (route) => {
  const auth = inject(AuthService);
  const router = inject(Router);
  const permission = route.data?.['permission'] as string | undefined;

  if (permission === undefined || auth.hasPermission(permission)) {
    return true;
  }

  return router.createUrlTree(['/error-403']);
};
