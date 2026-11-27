import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, from, switchMap, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import type {
  CambiosApiError,
  CancelacionMasivaResultado,
  CancelacionResultado,
  HistorialTurnoResponse,
  MotivoTurno,
  OrigenCancelable,
  PreviewCancelacion,
  PreviewCancelacionMasiva,
  PreviewReprogramacion,
  ReporteCambiosData,
  ReprogramacionResultado,
  TurnoCambioDetalle,
  WhatsappCambio,
} from './cambios-turnos.models';

interface DataResponse<T> {
  data: T;
  can: unknown;
}

@Injectable({ providedIn: 'root' })
export class CambiosTurnosService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/agenda-turnos/cambios-turnos`;

  getTurno(id: number): Observable<DataResponse<TurnoCambioDetalle>> {
    return this.http
      .get<DataResponse<TurnoCambioDetalle>>(`${this.baseUrl}/turnos/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getHistorial(id: number): Observable<HistorialTurnoResponse> {
    return this.http
      .get<HistorialTurnoResponse>(`${this.baseUrl}/turnos/${id}/historial`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getWhatsapp(id: number, accion: 'reprogramado' | 'cancelado'): Observable<DataResponse<WhatsappCambio>> {
    return this.http
      .get<DataResponse<WhatsappCambio>>(`${this.baseUrl}/turnos/${id}/contacto-whatsapp`, {
        params: new HttpParams().set('accion', accion),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  previewReprogramacion(id: number, payload: Record<string, unknown>): Observable<DataResponse<PreviewReprogramacion>> {
    return this.http
      .post<DataResponse<PreviewReprogramacion>>(
        `${this.baseUrl}/turnos/${id}/preview-reprogramacion`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  reprogramar(id: number, payload: Record<string, unknown>): Observable<DataResponse<ReprogramacionResultado>> {
    return this.http
      .post<DataResponse<ReprogramacionResultado>>(
        `${this.baseUrl}/turnos/${id}/reprogramar`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  previewCancelacion(id: number, payload: Record<string, unknown>): Observable<DataResponse<PreviewCancelacion>> {
    return this.http
      .post<DataResponse<PreviewCancelacion>>(
        `${this.baseUrl}/turnos/${id}/preview-cancelacion`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  cancelar(id: number, payload: Record<string, unknown>): Observable<DataResponse<CancelacionResultado>> {
    return this.http
      .post<DataResponse<CancelacionResultado>>(`${this.baseUrl}/turnos/${id}/cancelar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getOrigenesCancelables(pacienteId: number): Observable<DataResponse<OrigenCancelable[]>> {
    return this.http
      .get<DataResponse<OrigenCancelable[]>>(
        `${this.baseUrl}/pacientes/${pacienteId}/origenes-cancelables`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getMotivos(accion: 'reprogramar' | 'cancelar'): Observable<DataResponse<MotivoTurno[]>> {
    return this.http
      .get<DataResponse<MotivoTurno[]>>(`${this.baseUrl}/motivos`, {
        params: new HttpParams().set('accion', accion),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  previewMasiva(payload: Record<string, unknown>): Observable<DataResponse<PreviewCancelacionMasiva>> {
    return this.http
      .post<DataResponse<PreviewCancelacionMasiva>>(
        `${this.baseUrl}/cancelacion-masiva/preview`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  cancelarMasivo(payload: Record<string, unknown>): Observable<DataResponse<CancelacionMasivaResultado>> {
    return this.http
      .post<DataResponse<CancelacionMasivaResultado>>(`${this.baseUrl}/cancelacion-masiva`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getReporte(params: Record<string, string | number | null | undefined>): Observable<ReporteCambiosData> {
    return this.http
      .get<ReporteCambiosData>(`${this.baseUrl}/reporte-cambios`, { params: this.params(params) })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  exportar(
    formato: 'pdf' | 'excel',
    params: Record<string, string | number | null | undefined>,
  ): Observable<Blob> {
    return this.http
      .get(`${this.baseUrl}/reporte-cambios/exportar`, {
        params: this.params({ ...params, formato }),
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
      const body = (error.error ?? {}) as Partial<CambiosApiError>;

      return throwError(
        () =>
          ({
            status: error.status,
            message: body.message ?? 'No se pudo completar la operación.',
            code: body.code,
            errors: body.errors,
            context: body.context,
          }) satisfies CambiosApiError,
      );
    }

    return throwError(
      () =>
        ({
          status: 0,
          message: 'No se pudo conectar con el servidor.',
        }) satisfies CambiosApiError,
    );
  }

  private failBlob(error: unknown): Observable<never> {
    if (error instanceof HttpErrorResponse && error.error instanceof Blob) {
      return from(error.error.text()).pipe(
        switchMap((text) => {
          let body: Partial<CambiosApiError> = {};

          try {
            body = JSON.parse(text) as Partial<CambiosApiError>;
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
              }) satisfies CambiosApiError,
          );
        }),
      );
    }

    return this.fail(error);
  }
}
