import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, from, switchMap, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import type {
  AsistenciaApiError,
  AsistenciaIndicadoresResponse,
  AsistenciaResumenDiarioResponse,
  AsistenciaResultadoResponse,
  AsistenciaTurnoResponse,
  EstadoAsistencia,
} from './asistencia-turno.models';

@Injectable({ providedIn: 'root' })
export class AsistenciaTurnoService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/agenda-turnos/asistencia`;

  getTurno(id: number): Observable<AsistenciaTurnoResponse> {
    return this.http
      .get<AsistenciaTurnoResponse>(`${this.baseUrl}/turnos/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  registrar(
    id: number,
    estado: EstadoAsistencia,
    version?: number,
  ): Observable<AsistenciaResultadoResponse> {
    return this.http
      .post<AsistenciaResultadoResponse>(`${this.baseUrl}/turnos/${id}/registrar`, {
        estado,
        ...(version === undefined ? {} : { version }),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  corregir(
    id: number,
    estado: EstadoAsistencia,
    version: number,
    motivo: string | null,
  ): Observable<AsistenciaResultadoResponse> {
    return this.http
      .post<AsistenciaResultadoResponse>(`${this.baseUrl}/turnos/${id}/corregir`, {
        estado,
        version,
        motivo,
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getIndicadores(params: Record<string, string | number | null | undefined>): Observable<AsistenciaIndicadoresResponse> {
    return this.http
      .get<AsistenciaIndicadoresResponse>(`${this.baseUrl}/indicadores`, {
        params: this.params(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getResumenDiario(params: Record<string, string | number | null | undefined>): Observable<AsistenciaResumenDiarioResponse> {
    return this.http
      .get<AsistenciaResumenDiarioResponse>(`${this.baseUrl}/resumen-diario`, {
        params: this.params(params),
      })
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
      const body = (error.error ?? {}) as Partial<AsistenciaApiError>;

      return throwError(
        () =>
          ({
            status: error.status,
            message: body.message ?? 'No se pudo completar la operación.',
            code: body.code,
            errors: body.errors,
            context: body.context,
          }) satisfies AsistenciaApiError,
      );
    }

    return throwError(
      () =>
        ({
          status: 0,
          message: 'No se pudo conectar con el servidor.',
        }) satisfies AsistenciaApiError,
    );
  }

  private failBlob(error: unknown): Observable<never> {
    if (error instanceof HttpErrorResponse && error.error instanceof Blob) {
      return from(error.error.text()).pipe(
        switchMap((text) => {
          let body: Partial<AsistenciaApiError> = {};

          try {
            body = JSON.parse(text) as Partial<AsistenciaApiError>;
          } catch {
            body = {};
          }

          return throwError(
            () =>
              ({
                status: error.status,
                message: body.message ?? 'No se pudo exportar la asistencia.',
                code: body.code,
                errors: body.errors,
                context: body.context,
              }) satisfies AsistenciaApiError,
          );
        }),
      );
    }

    return this.fail(error);
  }
}
