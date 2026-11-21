import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  ApiError,
  UserCloseSessionsPayload,
  UserCloseSessionsResponse,
  UserCreatePayload,
  UserCreateResponse,
  UserHistoryResponse,
  UserListParams,
  UserListResponse,
  UserOptionsResponse,
  UserResetResponse,
  UserResponse,
  UserStatusPayload,
  UserUnlockPayload,
  UserUpdatePayload,
} from './usuarios.models';

@Injectable({ providedIn: 'root' })
export class UsuariosService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/seguridad/usuarios`;

  list(params: UserListParams): Observable<UserListResponse> {
    let httpParams = new HttpParams();

    if (params.search) httpParams = httpParams.set('search', params.search);
    if (params.rol_id) httpParams = httpParams.set('rol_id', String(params.rol_id));
    if (params.sucursal_id) {
      httpParams = httpParams.set('sucursal_id', String(params.sucursal_id));
    }
    if (params.estado) httpParams = httpParams.set('estado', params.estado);
    if (params.page) httpParams = httpParams.set('page', String(params.page));
    if (params.per_page) {
      httpParams = httpParams.set('per_page', String(params.per_page));
    }
    if (params.sort) httpParams = httpParams.set('sort', params.sort);
    if (params.direction) {
      httpParams = httpParams.set('direction', params.direction);
    }

    return this.http
      .get<UserListResponse>(this.baseUrl, { params: httpParams })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  get(id: number): Observable<UserResponse> {
    return this.http
      .get<UserResponse>(`${this.baseUrl}/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  options(): Observable<UserOptionsResponse> {
    return this.http
      .get<UserOptionsResponse>(`${this.baseUrl}/opciones`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  create(payload: UserCreatePayload): Observable<UserCreateResponse> {
    return this.http
      .post<UserCreateResponse>(this.baseUrl, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  update(id: number, payload: UserUpdatePayload): Observable<UserResponse> {
    return this.http
      .patch<UserResponse>(`${this.baseUrl}/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activate(id: number, payload: UserStatusPayload): Observable<UserResponse> {
    return this.http
      .post<UserResponse>(`${this.baseUrl}/${id}/activar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivate(id: number, payload: UserStatusPayload): Observable<UserResponse> {
    return this.http
      .post<UserResponse>(`${this.baseUrl}/${id}/desactivar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  resetPassword(id: number): Observable<UserResetResponse> {
    return this.http
      .post<UserResetResponse>(`${this.baseUrl}/${id}/resetear-password`, {})
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  unlock(id: number, payload: UserUnlockPayload): Observable<UserResponse> {
    return this.http
      .post<UserResponse>(`${this.baseUrl}/${id}/desbloquear`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  closeSessions(
    id: number,
    payload: UserCloseSessionsPayload,
  ): Observable<UserCloseSessionsResponse> {
    return this.http
      .post<UserCloseSessionsResponse>(`${this.baseUrl}/${id}/cerrar-sesiones`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  history(
    id: number,
    page = 1,
    perPage = 10,
  ): Observable<UserHistoryResponse> {
    const params = new HttpParams()
      .set('page', String(page))
      .set('per_page', String(perPage));

    return this.http
      .get<UserHistoryResponse>(`${this.baseUrl}/${id}/historial`, { params })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  static normalizeError(error: unknown): ApiError {
    if (error instanceof HttpErrorResponse) {
      const body = error.error;

      if (body && typeof body === 'object') {
        const message =
          typeof body.message === 'string' && body.message.trim() !== ''
            ? body.message
            : 'No se pudo completar la operación.';

        return {
          status: error.status,
          message,
          code: typeof body.code === 'string' ? body.code : undefined,
          errors:
            body.errors && typeof body.errors === 'object'
              ? (body.errors as Record<string, string[]>)
              : undefined,
        };
      }

      if (error.status === 0) {
        return {
          status: 0,
          message: 'No se pudo conectar con el servidor. Reintentá la operación.',
        };
      }

      return { status: error.status, message: error.message };
    }

    return { status: 0, message: 'Ocurrió un error inesperado.' };
  }

  private fail(error: unknown): Observable<never> {
    return throwError(() => UsuariosService.normalizeError(error));
  }
}
