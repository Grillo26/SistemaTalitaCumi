import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  ApiError,
  RoleMatrixHistoryResponse,
  RoleMatrixListResponse,
  RoleMatrixResponse,
  RoleMatrixUpdatePayload,
} from './roles-permisos.models';

@Injectable({ providedIn: 'root' })
export class RolesPermisosService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/seguridad/roles`;

  list(): Observable<RoleMatrixListResponse> {
    return this.http
      .get<RoleMatrixListResponse>(this.baseUrl)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  show(id: number): Observable<RoleMatrixResponse> {
    return this.http
      .get<RoleMatrixResponse>(`${this.baseUrl}/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  update(id: number, payload: RoleMatrixUpdatePayload): Observable<RoleMatrixResponse> {
    return this.http
      .put<RoleMatrixResponse>(`${this.baseUrl}/${id}/permisos`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  history(id: number, fecha: string): Observable<RoleMatrixHistoryResponse> {
    const params = new HttpParams().set('fecha', fecha);

    return this.http
      .get<RoleMatrixHistoryResponse>(`${this.baseUrl}/${id}/historial`, { params })
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
    return throwError(() => RolesPermisosService.normalizeError(error));
  }
}
