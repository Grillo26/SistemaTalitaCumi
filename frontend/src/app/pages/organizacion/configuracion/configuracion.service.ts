import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  Parameter,
  ParameterApiError,
  ParameterDetailResponse,
  ParameterHistoryResponse,
  ParameterListParams,
  ParameterListResponse,
  ParameterRestorePayload,
  ParameterUpdatePayload,
} from './configuracion.models';

@Injectable({ providedIn: 'root' })
export class ConfiguracionService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/organizacion/configuracion`;

  list(params: ParameterListParams): Observable<ParameterListResponse> {
    return this.http
      .get<ParameterListResponse>(`${this.baseUrl}/parametros`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  get(id: number): Observable<ParameterDetailResponse> {
    return this.http
      .get<ParameterDetailResponse>(`${this.baseUrl}/parametros/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  update(id: number, payload: ParameterUpdatePayload): Observable<ParameterDetailResponse> {
    return this.http
      .patch<ParameterDetailResponse>(`${this.baseUrl}/parametros/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  restore(id: number, payload: ParameterRestorePayload): Observable<ParameterDetailResponse> {
    return this.http
      .post<ParameterDetailResponse>(`${this.baseUrl}/parametros/${id}/restaurar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  history(id: number, page = 1, perPage = 10): Observable<ParameterHistoryResponse> {
    const params = new HttpParams()
      .set('page', String(page))
      .set('per_page', String(perPage));

    return this.http
      .get<ParameterHistoryResponse>(`${this.baseUrl}/parametros/${id}/historial`, { params })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  uploadLogo(file: File, version: number, motivo: string | null): Observable<ParameterDetailResponse> {
    const form = new FormData();
    form.append('logo', file);
    form.append('version', String(version));

    if (motivo !== null && motivo.trim() !== '') {
      form.append('motivo', motivo.trim());
    }

    return this.http
      .post<ParameterDetailResponse>(`${this.baseUrl}/logo`, form)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  private buildParams(params: ParameterListParams): HttpParams {
    let httpParams = new HttpParams();

    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== null && value !== '') {
        httpParams = httpParams.set(key, String(value));
      }
    }

    return httpParams;
  }

  private fail(error: unknown): Observable<never> {
    return throwError(() => this.normalizeError(error));
  }

  private normalizeError(error: unknown): ParameterApiError {
    if (error instanceof HttpErrorResponse) {
      const body = error.error;

      if (body && typeof body === 'object') {
        return {
          status: error.status,
          message:
            typeof body.message === 'string' && body.message.trim() !== ''
              ? body.message
              : 'No se pudo completar la operación.',
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
}
