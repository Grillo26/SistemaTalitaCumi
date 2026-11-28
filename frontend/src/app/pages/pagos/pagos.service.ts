import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import {
  PagoApiError,
  PagoEntidadResumen,
  PagoHistorial,
  PagoListParams,
  PagoMedio,
  PagoRegistroPayload,
  PagoRegistroResultado,
  PagoRow,
  PaginatedPagos,
} from './pagos.models';

@Injectable({ providedIn: 'root' })
export class PagosService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/pagos`;

  list(params: PagoListParams = {}): Observable<PaginatedPagos> {
    return this.http
      .get<PaginatedPagos>(this.baseUrl, { params: this.buildParams(params) })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  show(id: number): Observable<{ data: PagoRow }> {
    return this.http
      .get<{ data: PagoRow }>(`${this.baseUrl}/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  opciones(): Observable<{ data: { medios: PagoMedio[] } }> {
    return this.http
      .get<{ data: { medios: PagoMedio[] } }>(`${this.baseUrl}/opciones`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  buscarEntidades(q: string): Observable<{ data: PagoEntidadResumen[] }> {
    return this.http
      .get<{ data: PagoEntidadResumen[] }>(`${this.baseUrl}/buscar-entidades`, {
        params: new HttpParams().set('q', q),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  historial(tipo: string, id: number): Observable<{ data: PagoHistorial }> {
    return this.http
      .get<{ data: PagoHistorial }>(`${this.baseUrl}/entidades/${tipo}/${id}/historial`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  registrar(payload: PagoRegistroPayload): Observable<{ data: PagoRegistroResultado }> {
    return this.http
      .post<{ data: PagoRegistroResultado }>(this.baseUrl, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  exportarCsv(params: PagoListParams = {}): Observable<Blob> {
    return this.http
      .get(`${this.baseUrl}/exportar`, {
        params: this.buildParams(params),
        responseType: 'blob',
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  private buildParams(params: object): HttpParams {
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
      const body = (error.error ?? {}) as Partial<PagoApiError> & { message?: string };

      return throwError(
        () =>
          ({
            status: error.status,
            message: body.message ?? 'No se pudo completar la operación.',
            code: body.code,
            errors: body.errors,
            saldo_maximo: body.saldo_maximo,
          }) satisfies PagoApiError,
      );
    }

    return throwError(
      () => ({ status: 0, message: 'No se pudo conectar con el servidor.' }) satisfies PagoApiError,
    );
  }
}
