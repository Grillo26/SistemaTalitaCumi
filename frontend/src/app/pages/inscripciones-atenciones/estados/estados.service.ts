import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, from, switchMap, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  CambioEstadoPayload,
  EstadoApiError,
  EstadoInscripcionRow,
  EstadosTab,
  ExportFormat,
  HistorialEstadoItem,
  OpcionesEstado,
  PaginatedInscripciones,
  PaginatedPacientes,
  PrechequeoEstado,
  PrechequeoPaciente,
  PreviewRetiroPaciente,
  ReporteEstadosData,
  RetiroPacientePayload,
  TipoEntidad,
} from './estados.models';

export interface EstadoListParams {
  search?: string;
  estado?: string;
  sucursal_id?: number | '';
  servicio_id?: number | '';
  tipo_entidad?: string;
  paciente_id?: number | '';
  periodo_desde?: string;
  periodo_hasta?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: 'asc' | 'desc';
}

@Injectable({ providedIn: 'root' })
export class EstadosService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/inscripciones-atenciones/estados`;

  getOpciones(): Observable<{ data: OpcionesEstado; can: unknown }> {
    return this.http
      .get<{ data: OpcionesEstado; can: unknown }>(`${this.baseUrl}/opciones`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listPacientes(params: EstadoListParams = {}): Observable<PaginatedPacientes> {
    return this.http
      .get<PaginatedPacientes>(`${this.baseUrl}/pacientes`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listInscripciones(params: EstadoListParams = {}): Observable<PaginatedInscripciones> {
    return this.http
      .get<PaginatedInscripciones>(`${this.baseUrl}/inscripciones`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  historialPaciente(id: number): Observable<{ data: HistorialEstadoItem[]; can: unknown }> {
    return this.http
      .get<{ data: HistorialEstadoItem[]; can: unknown }>(
        `${this.baseUrl}/pacientes/${id}/historial`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  historialInscripcion(
    tipo: TipoEntidad,
    id: number,
  ): Observable<{ data: HistorialEstadoItem[]; can: unknown }> {
    return this.http
      .get<{ data: HistorialEstadoItem[]; can: unknown }>(
        `${this.baseUrl}/inscripciones/${tipo}/${id}/historial`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  pausarPaciente(id: number, payload: CambioEstadoPayload): Observable<unknown> {
    return this.http
      .post(`${this.baseUrl}/pacientes/${id}/pausar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  reactivarPaciente(id: number, payload: CambioEstadoPayload): Observable<unknown> {
    return this.http
      .post(`${this.baseUrl}/pacientes/${id}/reactivar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  previewRetiroPaciente(id: number): Observable<{ data: PreviewRetiroPaciente; can: unknown }> {
    return this.http
      .get<{ data: PreviewRetiroPaciente; can: unknown }>(
        `${this.baseUrl}/pacientes/${id}/retiro/preview`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  prechequeoRetiroPaciente(id: number): Observable<{
    data: { preview: PreviewRetiroPaciente; prechequeo: PrechequeoPaciente };
    can: unknown;
  }> {
    return this.http
      .get<{
        data: { preview: PreviewRetiroPaciente; prechequeo: PrechequeoPaciente };
        can: unknown;
      }>(`${this.baseUrl}/pacientes/${id}/retiro/prechequeo`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  retirarPaciente(id: number, payload: RetiroPacientePayload): Observable<unknown> {
    return this.http
      .post(`${this.baseUrl}/pacientes/${id}/retirar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  pausarInscripcion(
    tipo: TipoEntidad,
    id: number,
    payload: CambioEstadoPayload,
  ): Observable<unknown> {
    return this.http
      .post(`${this.baseUrl}/inscripciones/${tipo}/${id}/pausar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  reactivarInscripcion(
    tipo: TipoEntidad,
    id: number,
    payload: CambioEstadoPayload,
  ): Observable<unknown> {
    return this.http
      .post(`${this.baseUrl}/inscripciones/${tipo}/${id}/reactivar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  retirarInscripcion(
    tipo: TipoEntidad,
    id: number,
    payload: CambioEstadoPayload,
  ): Observable<unknown> {
    return this.http
      .post(`${this.baseUrl}/inscripciones/${tipo}/${id}/retirar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  prechequeoInscripcion(
    tipo: TipoEntidad,
    id: number,
    accion: 'pausar' | 'reactivar' | 'retirar',
  ): Observable<{ data: PrechequeoEstado; can: unknown }> {
    return this.http
      .get<{ data: PrechequeoEstado; can: unknown }>(
        `${this.baseUrl}/inscripciones/${tipo}/${id}/prechequeo-${accion}`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getReporte(
    tipo: 'pacientes' | 'inscripciones',
    params: EstadoListParams = {},
  ): Observable<{ data: ReporteEstadosData; can: unknown }> {
    return this.http
      .get<{ data: ReporteEstadosData; can: unknown }>(`${this.baseUrl}/reportes`, {
        params: this.buildParams({ ...params, tipo }),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  exportar(
    formato: ExportFormat,
    tipo: 'pacientes' | 'inscripciones',
    params: EstadoListParams = {},
  ): Observable<Blob> {
    return this.http
      .get(`${this.baseUrl}/reportes/exportar`, {
        params: this.buildParams({ ...params, tipo, formato }),
        responseType: 'blob',
      })
      .pipe(catchError((error: unknown) => this.failBlob(error)));
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
      return throwError(() => this.toError(error, error.error ?? {}));
    }

    return throwError(
      () =>
        ({
          status: 0,
          message: 'No se pudo conectar con el servidor.',
        }) satisfies EstadoApiError,
    );
  }

  private failBlob(error: unknown): Observable<never> {
    if (error instanceof HttpErrorResponse && error.error instanceof Blob) {
      return from(error.error.text()).pipe(
        switchMap((text) => {
          let body: Record<string, unknown> = {};

          try {
            body = JSON.parse(text) as Record<string, unknown>;
          } catch {
            body = {};
          }

          return throwError(() => this.toError(error, body));
        }),
      );
    }

    return this.fail(error);
  }

  private toError(
    error: HttpErrorResponse,
    body: Record<string, unknown>,
  ): EstadoApiError {
    return {
      status: error.status,
      message:
        typeof body['message'] === 'string'
          ? body['message']
          : 'No se pudo completar la operación.',
      code: typeof body['code'] === 'string' ? body['code'] : undefined,
      errors: (body['errors'] as Record<string, string[]> | undefined) ?? undefined,
      estado_actual:
        typeof body['estado_actual'] === 'string' ? body['estado_actual'] : undefined,
      estado_esperado:
        typeof body['estado_esperado'] === 'string' ? body['estado_esperado'] : undefined,
      sucursales_faltantes: Array.isArray(body['sucursales_faltantes'])
        ? (body['sucursales_faltantes'] as number[])
        : undefined,
      modulo: typeof body['modulo'] === 'string' ? body['modulo'] : undefined,
    };
  }
}

export type { EstadoInscripcionRow, EstadosTab };
