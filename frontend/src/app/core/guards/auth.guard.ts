import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from '../services/auth.service';

export const authGuard: CanActivateFn = (_route, state) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (!auth.isAuthenticated()) {
    return router.createUrlTree(['/login']);
  }

  if (
    auth.mustChangePassword() &&
    !state.url.startsWith('/cambiar-password')
  ) {
    return router.createUrlTree(['/cambiar-password']);
  }

  return true;
};
