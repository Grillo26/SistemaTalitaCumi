import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, from, switchMap, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import type {
  ActualizarEstadoPayload,
  ActualizarEstadoResponse,
  CancelarDiagnosticoResponse,
  DiagnosticosApiError,
  DiagnosticoDetalleResponse,
  DiagnosticosListaResponse,
  DiagnosticosOpcionesResponse,
  ProgramarTurnoPayload,
  ProgramarTurnoResponse,
  WhatsappDiagnosticoResponse,
} from './diagnosticos.models';

@Injectable({ providedIn: 'root' })
export class DiagnosticosService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/agenda-turnos/diagnosticos`;

  listar(params: Record<string, string | number | null | undefined>): Observable<DiagnosticosListaResponse> {
    return this.http
      .get<DiagnosticosListaResponse>(this.baseUrl, { params: this.params(params) })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  opciones(sucursalId?: number | null): Observable<DiagnosticosOpcionesResponse> {
    return this.http
      .get<DiagnosticosOpcionesResponse>(`${this.baseUrl}/opciones`, {
        params: this.params({ sucursal_id: sucursalId ?? null }),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  detalle(id: number): Observable<DiagnosticoDetalleResponse> {
    return this.http
      .get<DiagnosticoDetalleResponse>(`${this.baseUrl}/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  programar(id: number, payload: ProgramarTurnoPayload): Observable<ProgramarTurnoResponse> {
    return this.http
      .post<ProgramarTurnoResponse>(`${this.baseUrl}/${id}/turnos`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  actualizarEstado(id: number, payload: ActualizarEstadoPayload): Observable<ActualizarEstadoResponse> {
    return this.http
      .patch<ActualizarEstadoResponse>(`${this.baseUrl}/${id}/estado-general`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  cancelar(id: number, motivo: string): Observable<CancelarDiagnosticoResponse> {
    return this.http
      .post<CancelarDiagnosticoResponse>(`${this.baseUrl}/${id}/cancelar`, { motivo })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  whatsapp(id: number): Observable<WhatsappDiagnosticoResponse> {
    return this.http
      .get<WhatsappDiagnosticoResponse>(`${this.baseUrl}/${id}/whatsapp`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  exportarCsv(params: Record<string, string | number | null | undefined>): Observable<Blob> {
    return this.http
      .get(`${this.baseUrl}/exportar`, {
        params: this.params({ ...params, formato: 'csv' }),
        responseType: 'blob',
      })
      .pipe(catchError((error: unknown) => this.failBlob(error)));
  }

  private params(params: Record<string, string | number | null | undefined>): HttpParams {
    let httpParams = new HttpParams();

    Object.entries(params).forEach(([key, value]) => {
      if (value === null || value === undefined || value === '') {
        return;
      }

      httpParams = httpParams.set(key, String(value));
    });

    return httpParams;
  }

  private fail(error: unknown): Observable<never> {
    if (error instanceof HttpErrorResponse) {
      const body = (error.error ?? {}) as Partial<DiagnosticosApiError>;

      return throwError(
        () =>
          ({
            status: error.status,
            message: body.message ?? 'No se pudo completar la operación.',
            code: body.code,
            errors: body.errors,
            context: body.context,
          }) satisfies DiagnosticosApiError,
      );
    }

    return throwError(
      () =>
        ({
          status: 0,
          message: 'No se pudo conectar con el servidor.',
        }) satisfies DiagnosticosApiError,
    );
  }

  private failBlob(error: unknown): Observable<never> {
    if (error instanceof HttpErrorResponse && error.error instanceof Blob) {
      return from(error.error.text()).pipe(
        switchMap((text) => {
          let body: Partial<DiagnosticosApiError> = {};

          try {
            body = JSON.parse(text) as Partial<DiagnosticosApiError>;
          } catch {
            body = {};
          }

          return throwError(
            () =>
              ({
                status: error.status,
                message: body.message ?? 'No se pudo exportar el reporte.',
                code: body.code,
                errors: body.errors,
                context: body.context,
              }) satisfies DiagnosticosApiError,
          );
        }),
      );
    }

    return this.fail(error);
  }
}
