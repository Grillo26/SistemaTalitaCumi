import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  ApiError,
  BranchCreatePayload,
  BranchListParams,
  BranchListResponse,
  BranchResponse,
  BranchStatusPayload,
  BranchUpdatePayload,
} from './sucursales.models';

@Injectable({ providedIn: 'root' })
export class SucursalesService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/organizacion/sucursales`;

  list(params: BranchListParams): Observable<BranchListResponse> {
    let httpParams = new HttpParams();

    if (params.search) {
      httpParams = httpParams.set('search', params.search);
    }
    if (params.estado) {
      httpParams = httpParams.set('estado', params.estado);
    }
    if (params.page) {
      httpParams = httpParams.set('page', String(params.page));
    }
    if (params.per_page) {
      httpParams = httpParams.set('per_page', String(params.per_page));
    }
    if (params.sort) {
      httpParams = httpParams.set('sort', params.sort);
    }
    if (params.direction) {
      httpParams = httpParams.set('direction', params.direction);
    }

    return this.http
      .get<BranchListResponse>(this.baseUrl, { params: httpParams })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  get(id: number): Observable<BranchResponse> {
    return this.http
      .get<BranchResponse>(`${this.baseUrl}/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  create(payload: BranchCreatePayload): Observable<BranchResponse> {
    return this.http
      .post<BranchResponse>(this.baseUrl, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  update(id: number, payload: BranchUpdatePayload): Observable<BranchResponse> {
    return this.http
      .patch<BranchResponse>(`${this.baseUrl}/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activate(id: number, payload: BranchStatusPayload): Observable<BranchResponse> {
    return this.http
      .post<BranchResponse>(`${this.baseUrl}/${id}/activar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivate(id: number, payload: BranchStatusPayload): Observable<BranchResponse> {
    return this.http
      .post<BranchResponse>(`${this.baseUrl}/${id}/desactivar`, payload)
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
    return throwError(() => SucursalesService.normalizeError(error));
  }
}
