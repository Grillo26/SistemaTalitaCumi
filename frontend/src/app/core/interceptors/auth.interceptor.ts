import {
  HttpErrorResponse,
  HttpInterceptorFn,
} from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import { SessionStore } from '../services/session-store.service';

/**
 * Adjunta el token Sanctum y centraliza 401/403 de sesión (RF-A14).
 *
 * Los servicios de 01/02/03 siguen recibiendo el error original para sus
 * mensajes locales; este interceptor solo agrega la redirección global.
 */
export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const store = inject(SessionStore);
  const router = inject(Router);

  const token = store.token();
  const isApiRequest = request.url.startsWith(environment.apiUrl);
  const isLogin = request.url.endsWith('/auth/login');
  const isMe = request.url.endsWith('/auth/me');

  const authorized =
    token !== null && isApiRequest
      ? request.clone({ setHeaders: { Authorization: `Bearer ${token}` } })
      : request;

  return next(authorized).pipe(
    catchError((error: unknown) => {
      if (error instanceof HttpErrorResponse) {
        if (error.status === 401 && !isLogin && !isMe) {
          store.clear();

          if (!router.url.startsWith('/login')) {
            void router.navigate(['/login'], {
              queryParams: { expirada: 1 },
            });
          }
        }

        if (
          error.status === 403 &&
          (error.error as { code?: string } | null)?.code ===
            'PASSWORD_CHANGE_REQUIRED'
        ) {
          if (!router.url.startsWith('/cambiar-password')) {
            void router.navigate(['/cambiar-password']);
          }
        }
      }

      return throwError(() => error);
    }),
  );
};
