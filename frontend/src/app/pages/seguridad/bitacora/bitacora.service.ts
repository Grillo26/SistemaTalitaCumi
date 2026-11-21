import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  ApiError,
  AuditEntryResponse,
  AuditListParams,
  AuditListResponse,
  AuditOptionsResponse,
  AuditReconstructionResponse,
} from './bitacora.models';

@Injectable({ providedIn: 'root' })
export class BitacoraService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/seguridad/bitacora`;

  list(params: AuditListParams): Observable<AuditListResponse> {
    let httpParams = new HttpParams();

    if (params.fecha_desde) httpParams = httpParams.set('fecha_desde', params.fecha_desde);
    if (params.fecha_hasta) httpParams = httpParams.set('fecha_hasta', params.fecha_hasta);
    if (params.usuario_id) httpParams = httpParams.set('usuario_id', String(params.usuario_id));
    if (params.modulo) httpParams = httpParams.set('modulo', params.modulo);
    if (params.entidad) httpParams = httpParams.set('entidad', params.entidad);
    if (params.entidad_id) httpParams = httpParams.set('entidad_id', String(params.entidad_id));
    if (params.accion) httpParams = httpParams.set('accion', params.accion);
    if (params.resultado) httpParams = httpParams.set('resultado', params.resultado);
    if (params.critico !== undefined) {
      httpParams = httpParams.set('critico', params.critico ? '1' : '0');
    }
    if (params.sucursal_id) {
      httpParams = httpParams.set('sucursal_id', String(params.sucursal_id));
    }
    if (params.page) httpParams = httpParams.set('page', String(params.page));
    if (params.per_page) {
      httpParams = httpParams.set('per_page', String(params.per_page));
    }

    return this.http
      .get<AuditListResponse>(this.baseUrl, { params: httpParams })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  get(id: number): Observable<AuditEntryResponse> {
    return this.http
      .get<AuditEntryResponse>(`${this.baseUrl}/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  options(): Observable<AuditOptionsResponse> {
    return this.http
      .get<AuditOptionsResponse>(`${this.baseUrl}/opciones`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  reconstruct(entidad: string, entidadId: number, fecha: string): Observable<AuditReconstructionResponse> {
    const params = new HttpParams()
      .set('entidad', entidad)
      .set('entidad_id', String(entidadId))
      .set('fecha', fecha);

    return this.http
      .get<AuditReconstructionResponse>(`${this.baseUrl}/reconstruir`, { params })
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
    return throwError(() => BitacoraService.normalizeError(error));
  }
}
