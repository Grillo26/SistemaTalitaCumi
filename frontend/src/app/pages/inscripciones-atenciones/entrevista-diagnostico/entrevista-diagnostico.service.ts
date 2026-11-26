import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  AtencionApiError,
  BajaAtencionPayload,
  ComprobanteAtencionData,
  DetailResponse,
  DiagnosticoDetalle,
  DiagnosticoListParams,
  DiagnosticoPayload,
  DiagnosticoRow,
  DiagnosticoUpdatePayload,
  EntrevistaDetalle,
  EntrevistaListParams,
  EntrevistaPayload,
  EntrevistaPrevia,
  EntrevistaRow,
  EntrevistaUpdatePayload,
  PaginatedResponse,
  PagoEstado,
  PreviewMontoResponse,
  RequisitosDiagnosticoResponse,
} from './entrevista-diagnostico.models';

@Injectable({ providedIn: 'root' })
export class EntrevistaDiagnosticoService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/inscripciones-atenciones/entrevista-diagnostico`;

  listEntrevistas(
    params: EntrevistaListParams = {},
  ): Observable<PaginatedResponse<EntrevistaRow>> {
    return this.http
      .get<PaginatedResponse<EntrevistaRow>>(`${this.baseUrl}/entrevistas`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getEntrevista(id: number): Observable<DetailResponse<EntrevistaDetalle>> {
    return this.http
      .get<DetailResponse<EntrevistaDetalle>>(`${this.baseUrl}/entrevistas/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  previewMontoEntrevista(params: {
    servicio_id: number;
    sucursal_id: number;
    categoria_tarifa_id: number;
    fecha: string;
  }): Observable<PreviewMontoResponse> {
    return this.http
      .get<PreviewMontoResponse>(`${this.baseUrl}/entrevistas/monto-esperado`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createEntrevista(payload: EntrevistaPayload): Observable<DetailResponse<EntrevistaDetalle>> {
    return this.http
      .post<DetailResponse<EntrevistaDetalle>>(`${this.baseUrl}/entrevistas`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateEntrevista(
    id: number,
    payload: EntrevistaUpdatePayload,
  ): Observable<DetailResponse<EntrevistaDetalle>> {
    return this.http
      .patch<DetailResponse<EntrevistaDetalle>>(`${this.baseUrl}/entrevistas/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  withdrawEntrevista(
    id: number,
    payload: BajaAtencionPayload,
  ): Observable<DetailResponse<EntrevistaDetalle>> {
    return this.http
      .post<DetailResponse<EntrevistaDetalle>>(`${this.baseUrl}/entrevistas/${id}/retirar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listDiagnosticos(
    params: DiagnosticoListParams = {},
  ): Observable<PaginatedResponse<DiagnosticoRow>> {
    return this.http
      .get<PaginatedResponse<DiagnosticoRow>>(`${this.baseUrl}/diagnosticos`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getDiagnostico(id: number): Observable<DetailResponse<DiagnosticoDetalle>> {
    return this.http
      .get<DetailResponse<DiagnosticoDetalle>>(`${this.baseUrl}/diagnosticos/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  previewMontoDiagnostico(params: {
    tipo_diagnostico_id: number;
    sucursal_id: number;
    categoria_tarifa_id: number;
    fecha_inicio: string;
  }): Observable<PreviewMontoResponse> {
    return this.http
      .get<PreviewMontoResponse>(`${this.baseUrl}/diagnosticos/monto-esperado`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  requisitos(tipoDiagnosticoId: number): Observable<RequisitosDiagnosticoResponse> {
    return this.http
      .get<RequisitosDiagnosticoResponse>(`${this.baseUrl}/diagnosticos/requisitos`, {
        params: { tipo_diagnostico_id: String(tipoDiagnosticoId) },
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  entrevistasPrevias(pacienteId: number): Observable<{ data: EntrevistaPrevia[] }> {
    return this.http
      .get<{ data: EntrevistaPrevia[] }>(`${this.baseUrl}/entrevistas-previas`, {
        params: { paciente_id: String(pacienteId) },
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createDiagnostico(payload: DiagnosticoPayload): Observable<DetailResponse<DiagnosticoDetalle>> {
    return this.http
      .post<DetailResponse<DiagnosticoDetalle>>(`${this.baseUrl}/diagnosticos`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateDiagnostico(
    id: number,
    payload: DiagnosticoUpdatePayload,
  ): Observable<DetailResponse<DiagnosticoDetalle>> {
    return this.http
      .patch<DetailResponse<DiagnosticoDetalle>>(`${this.baseUrl}/diagnosticos/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  withdrawDiagnostico(
    id: number,
    payload: BajaAtencionPayload,
  ): Observable<DetailResponse<DiagnosticoDetalle>> {
    return this.http
      .post<DetailResponse<DiagnosticoDetalle>>(`${this.baseUrl}/diagnosticos/${id}/retirar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  vincularEntrevista(
    diagnosticoId: number,
    entrevistaId: number,
  ): Observable<DetailResponse<DiagnosticoDetalle>> {
    return this.http
      .post<DetailResponse<DiagnosticoDetalle>>(
        `${this.baseUrl}/diagnosticos/${diagnosticoId}/vincular-entrevista`,
        { entrevista_id: entrevistaId },
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getPago(tipo: 'entrevista' | 'diagnostico', id: number): Observable<{ data: PagoEstado }> {
    return this.http
      .get<{ data: PagoEstado }>(`${this.baseUrl}/${this.plural(tipo)}/${id}/pago`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  registrarPago(
    tipo: 'entrevista' | 'diagnostico',
    id: number,
  ): Observable<{ data: unknown }> {
    return this.http
      .post<{ data: unknown }>(`${this.baseUrl}/${this.plural(tipo)}/${id}/pago`, {})
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getComprobante(
    tipo: 'entrevista' | 'diagnostico',
    id: number,
  ): Observable<{ data: ComprobanteAtencionData }> {
    return this.http
      .get<{ data: ComprobanteAtencionData }>(
        `${this.baseUrl}/${this.plural(tipo)}/${id}/comprobante`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getComprobantePdf(tipo: 'entrevista' | 'diagnostico', id: number): Observable<Blob> {
    return this.http
      .get(`${this.baseUrl}/${this.plural(tipo)}/${id}/comprobante/pdf`, {
        responseType: 'blob',
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  private plural(tipo: 'entrevista' | 'diagnostico'): string {
    return tipo === 'entrevista' ? 'entrevistas' : 'diagnosticos';
  }

  private buildParams(params: object): HttpParams {
    let httpParams = new HttpParams();

    for (const [key, value] of Object.entries(params)) {
      if (value !== undefined && value !== null && value !== '') {
        httpParams = httpParams.set(key, String(value));
      }
    }

    return httpParams;
  }

  private fail(error: unknown): Observable<never> {
    if (error instanceof HttpErrorResponse) {
      const body = (error.error ?? {}) as Partial<AtencionApiError>;

      return throwError(
        () =>
          ({
            status: error.status,
            message: body.message ?? 'No se pudo completar la operación.',
            code: body.code,
            errors: body.errors,
            faltantes: body.faltantes,
            tarifa_aplicable: body.tarifa_aplicable,
            modulo: body.modulo,
          }) satisfies AtencionApiError,
      );
    }

    return throwError(
      () =>
        ({
          status: 0,
          message: 'No se pudo conectar con el servidor.',
        }) satisfies AtencionApiError,
    );
  }
}
