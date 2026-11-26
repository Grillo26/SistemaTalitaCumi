import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  BajaPaquetePayload,
  ComprobantePaqueteData,
  ConsumoPaquete,
  DetailResponse,
  MontoPreviewResponse,
  PaqueteApiError,
  PaqueteDetalle,
  PaqueteListParams,
  PaqueteOpciones,
  PaquetePayload,
  PaqueteRow,
  PaqueteUpdatePayload,
  PaginatedResponse,
  PagoEstado,
  RenovacionPayload,
  RenovacionPreview,
  TarifaAplicable,
  TurnosFuturosResumen,
} from './paquetes-sesiones.models';

@Injectable({ providedIn: 'root' })
export class PaquetesSesionesService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/inscripciones-atenciones/paquetes-sesiones`;

  list(params: PaqueteListParams = {}): Observable<PaginatedResponse<PaqueteRow>> {
    return this.http
      .get<PaginatedResponse<PaqueteRow>>(this.baseUrl, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  get(id: number): Observable<DetailResponse<PaqueteDetalle>> {
    return this.http
      .get<DetailResponse<PaqueteDetalle>>(`${this.baseUrl}/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  opciones(): Observable<DetailResponse<PaqueteOpciones>> {
    return this.http
      .get<DetailResponse<PaqueteOpciones>>(`${this.baseUrl}/opciones`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  previewMonto(params: {
    servicio_id: number;
    sucursal_id: number;
    categoria_tarifa_id: number;
    fecha_inicio: string;
    cantidad_sesiones?: number;
    dias_semana?: number[];
    fecha_fin?: string;
  }): Observable<MontoPreviewResponse> {
    let httpParams = this.buildParams(params);

    (params.dias_semana ?? []).forEach((dia) => {
      httpParams = httpParams.append('dias_semana[]', String(dia));
    });

    return this.http
      .get<MontoPreviewResponse>(`${this.baseUrl}/monto-esperado`, { params: httpParams })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getRenovacion(id: number): Observable<DetailResponse<RenovacionPreview>> {
    return this.http
      .get<DetailResponse<RenovacionPreview>>(`${this.baseUrl}/${id}/renovacion`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  create(payload: PaquetePayload): Observable<DetailResponse<PaqueteDetalle>> {
    return this.http
      .post<DetailResponse<PaqueteDetalle>>(this.baseUrl, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  update(id: number, payload: PaqueteUpdatePayload): Observable<DetailResponse<PaqueteDetalle>> {
    return this.http
      .patch<DetailResponse<PaqueteDetalle>>(`${this.baseUrl}/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  renew(id: number, payload: RenovacionPayload): Observable<DetailResponse<PaqueteDetalle>> {
    return this.http
      .post<DetailResponse<PaqueteDetalle>>(`${this.baseUrl}/${id}/renovar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  withdraw(id: number, payload: BajaPaquetePayload): Observable<DetailResponse<PaqueteDetalle>> {
    return this.http
      .post<DetailResponse<PaqueteDetalle>>(`${this.baseUrl}/${id}/retirar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getConsumo(id: number): Observable<{ data: ConsumoPaquete; can: unknown }> {
    return this.http
      .get<{ data: ConsumoPaquete; can: unknown }>(`${this.baseUrl}/${id}/consumo`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getPago(id: number): Observable<{ data: PagoEstado; can: unknown }> {
    return this.http
      .get<{ data: PagoEstado; can: unknown }>(`${this.baseUrl}/${id}/pago`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getTurnosFuturos(id: number): Observable<{ data: TurnosFuturosResumen; can: unknown }> {
    return this.http
      .get<{ data: TurnosFuturosResumen; can: unknown }>(`${this.baseUrl}/${id}/turnos-futuros`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getComprobante(id: number): Observable<{ data: ComprobantePaqueteData; can: unknown }> {
    return this.http
      .get<{ data: ComprobantePaqueteData; can: unknown }>(`${this.baseUrl}/${id}/comprobante`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  exportar(params: PaqueteListParams = {}): Observable<Blob> {
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
      if (value === null || value === undefined || value === '' || Array.isArray(value)) {
        return;
      }

      httpParams = httpParams.set(key, String(value));
    });

    return httpParams;
  }

  private fail(error: unknown): Observable<never> {
    if (error instanceof HttpErrorResponse) {
      const body = (error.error ?? {}) as Partial<PaqueteApiError>;

      return throwError(
        () =>
          ({
            status: error.status,
            message: body.message ?? 'No se pudo completar la operación.',
            code: body.code,
            errors: body.errors,
            conflict: body.conflict,
            tarifa_aplicable: body.tarifa_aplicable as TarifaAplicable | undefined,
            modulo: body.modulo,
          }) satisfies PaqueteApiError,
      );
    }

    return throwError(
      () =>
        ({
          status: 0,
          message: 'No se pudo conectar con el servidor.',
        }) satisfies PaqueteApiError,
    );
  }
}
