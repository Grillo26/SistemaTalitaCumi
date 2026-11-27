import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, from, switchMap, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import type {
  AgendaApiError,
  AgendaSemanaParams,
  AgendaSemanalResponse,
  OpcionesFiltrosResponse,
  SucursalesDisponiblesResponse,
  TurnoDetalleResponse,
} from './agenda-semanal.models';

@Injectable({ providedIn: 'root' })
export class AgendaSemanalService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/agenda-turnos/agenda-semanal`;

  getSucursales(): Observable<SucursalesDisponiblesResponse> {
    return this.http
      .get<SucursalesDisponiblesResponse>(`${this.baseUrl}/sucursales`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getSemana(params: AgendaSemanaParams): Observable<AgendaSemanalResponse> {
    return this.http
      .get<AgendaSemanalResponse>(this.baseUrl, { params: this.buildParams(params) })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getOpciones(sucursalId: number): Observable<OpcionesFiltrosResponse> {
    return this.http
      .get<OpcionesFiltrosResponse>(`${this.baseUrl}/opciones`, {
        params: this.buildParams({ sucursal_id: sucursalId }),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getDetalle(id: number): Observable<TurnoDetalleResponse> {
    return this.http
      .get<TurnoDetalleResponse>(`${this.baseUrl}/turnos/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  exportar(params: AgendaSemanaParams): Observable<Blob> {
    return this.http
      .get(`${this.baseUrl}/exportar`, {
        params: this.buildParams(params),
        responseType: 'blob',
      })
      .pipe(catchError((error: unknown) => this.failBlob(error)));
  }

  private buildParams(params: object): HttpParams {
    let httpParams = new HttpParams();

    Object.entries(params).forEach(([key, value]) => {
      if (value === null || value === undefined || value === '' || value === false) {
        return;
      }

      httpParams = httpParams.set(key, String(value));
    });

    return httpParams;
  }

  private fail(error: unknown): Observable<never> {
    if (error instanceof HttpErrorResponse) {
      const body = (error.error ?? {}) as Partial<AgendaApiError>;

      return throwError(
        () =>
          ({
            status: error.status,
            message: body.message ?? 'No se pudo completar la operación.',
            code: body.code,
            errors: body.errors,
            modulo: body.modulo,
          }) satisfies AgendaApiError,
      );
    }

    return throwError(
      () =>
        ({
          status: 0,
          message: 'No se pudo conectar con el servidor.',
        }) satisfies AgendaApiError,
    );
  }

  /**
   * La exportación se pide como blob para el futuro PDF. Mientras el backend
   * responde 503 JSON, hay que leer el cuerpo del blob para conservar el
   * mensaje y el código de error.
   */
  private failBlob(error: unknown): Observable<never> {
    if (error instanceof HttpErrorResponse) {
      if (error.error instanceof Blob) {
        return from(error.error.text()).pipe(
          switchMap((text) => {
            let body: Partial<AgendaApiError> = {};

            try {
              body = JSON.parse(text) as Partial<AgendaApiError>;
            } catch {
              body = {};
            }

            return throwError(
              () =>
                ({
                  status: error.status,
                  message: body.message ?? 'No se pudo exportar la agenda.',
                  code: body.code,
                  errors: body.errors,
                  modulo: body.modulo,
                }) satisfies AgendaApiError,
            );
          }),
        );
      }

      return this.fail(error);
    }

    return throwError(
      () =>
        ({
          status: 0,
          message: 'No se pudo conectar con el servidor.',
        }) satisfies AgendaApiError,
    );
  }
}
