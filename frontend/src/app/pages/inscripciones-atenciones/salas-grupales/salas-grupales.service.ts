import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  AsistenciaResumen,
  BajaPayload,
  ComprobanteData,
  DetailResponse,
  InscripcionApiError,
  InscripcionDetalle,
  InscripcionListParams,
  InscripcionPayload,
  InscripcionRow,
  InscripcionUpdatePayload,
  PaginatedResponse,
  PagoEstado,
  PreviewMonto,
  PreviewMontoResponse,
  RenovacionPayload,
  RenovacionPreview,
  TurnosFuturosResumen,
} from './salas-grupales.models';

@Injectable({ providedIn: 'root' })
export class SalasGrupalesService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/inscripciones-atenciones/salas-grupales`;

  list(params: InscripcionListParams = {}): Observable<PaginatedResponse<InscripcionRow>> {
    return this.http
      .get<PaginatedResponse<InscripcionRow>>(this.baseUrl, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  get(id: number): Observable<DetailResponse<InscripcionDetalle>> {
    return this.http
      .get<DetailResponse<InscripcionDetalle>>(`${this.baseUrl}/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  previewMonto(params: {
    servicio_id: number;
    sucursal_id: number;
    categoria_tarifa_id: number;
    fecha_inicio: string;
    tarifa_id_esperada?: number | null;
  }): Observable<PreviewMontoResponse> {
    return this.http
      .get<PreviewMontoResponse>(`${this.baseUrl}/monto-esperado`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getRenovacion(id: number): Observable<DetailResponse<RenovacionPreview>> {
    return this.http
      .get<DetailResponse<RenovacionPreview>>(`${this.baseUrl}/${id}/renovacion`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  create(payload: InscripcionPayload): Observable<DetailResponse<InscripcionDetalle>> {
    return this.http
      .post<DetailResponse<InscripcionDetalle>>(this.baseUrl, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  update(
    id: number,
    payload: InscripcionUpdatePayload,
  ): Observable<DetailResponse<InscripcionDetalle>> {
    return this.http
      .patch<DetailResponse<InscripcionDetalle>>(`${this.baseUrl}/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  renew(id: number, payload: RenovacionPayload): Observable<DetailResponse<InscripcionDetalle>> {
    return this.http
      .post<DetailResponse<InscripcionDetalle>>(`${this.baseUrl}/${id}/renovar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  withdraw(id: number, payload: BajaPayload): Observable<DetailResponse<InscripcionDetalle>> {
    return this.http
      .post<DetailResponse<InscripcionDetalle>>(`${this.baseUrl}/${id}/retirar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getPago(id: number): Observable<{ data: PagoEstado; can: unknown }> {
    return this.http
      .get<{ data: PagoEstado; can: unknown }>(`${this.baseUrl}/${id}/pago`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  registrarPago(id: number): Observable<{ data: unknown; can: unknown }> {
    return this.http
      .post<{ data: unknown; can: unknown }>(`${this.baseUrl}/${id}/pago`, {})
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getAsistencia(id: number): Observable<{ data: AsistenciaResumen; can: unknown }> {
    return this.http
      .get<{ data: AsistenciaResumen; can: unknown }>(`${this.baseUrl}/${id}/asistencia`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getTurnosFuturos(id: number): Observable<{ data: TurnosFuturosResumen; can: unknown }> {
    return this.http
      .get<{ data: TurnosFuturosResumen; can: unknown }>(`${this.baseUrl}/${id}/turnos-futuros`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getComprobante(id: number): Observable<{ data: ComprobanteData; can: unknown }> {
    return this.http
      .get<{ data: ComprobanteData; can: unknown }>(`${this.baseUrl}/${id}/comprobante`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getComprobantePdf(id: number): Observable<Blob> {
    return this.http
      .get(`${this.baseUrl}/${id}/comprobante/pdf`, { responseType: 'blob' })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  exportar(params: InscripcionListParams = {}): Observable<Blob> {
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
      const body = (error.error ?? {}) as Partial<InscripcionApiError>;

      return throwError(
        () =>
          ({
            status: error.status,
            message: body.message ?? 'No se pudo completar la operación.',
            code: body.code,
            errors: body.errors,
            conflict: body.conflict,
            tarifa_aplicable: body.tarifa_aplicable as PreviewMonto | undefined,
            modulo: body.modulo,
          }) satisfies InscripcionApiError,
      );
    }

    return throwError(
      () =>
        ({
          status: 0,
          message: 'No se pudo conectar con el servidor.',
        }) satisfies InscripcionApiError,
    );
  }
}
