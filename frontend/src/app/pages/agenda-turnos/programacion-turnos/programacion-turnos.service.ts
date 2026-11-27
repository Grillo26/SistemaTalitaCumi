import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import type {
  Feriado,
  FeriadosResponse,
  GenerarTurnosPayload,
  LoteResultadoResponse,
  OpcionesAgendaResponse,
  OrigenProgramacionResponse,
  PreviewGeneracionParams,
  PreviewGeneracionResponse,
  PreviewRegeneracionResponse,
  ProgramacionApiError,
  TipoOrigenProgramacion,
  TurnosOrigenResponse,
} from './programacion-turnos.models';

@Injectable({ providedIn: 'root' })
export class ProgramacionTurnosService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/agenda-turnos/programacion-turnos`;
  private readonly agendaUrl = `${environment.apiUrl}/agenda-turnos/agenda-semanal`;

  getOrigen(
    tipo: TipoOrigenProgramacion,
    id: number,
  ): Observable<OrigenProgramacionResponse> {
    return this.http
      .get<OrigenProgramacionResponse>(`${this.baseUrl}/origenes/${tipo}/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getTurnos(
    tipo: TipoOrigenProgramacion,
    id: number,
  ): Observable<TurnosOrigenResponse> {
    return this.http
      .get<TurnosOrigenResponse>(`${this.baseUrl}/origenes/${tipo}/${id}/turnos`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  previewGeneracion(
    tipo: TipoOrigenProgramacion,
    id: number,
    params: PreviewGeneracionParams,
  ): Observable<PreviewGeneracionResponse> {
    return this.http
      .get<PreviewGeneracionResponse>(
        `${this.baseUrl}/origenes/${tipo}/${id}/preview-generacion`,
        { params: this.previewParams(params) },
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  generar(
    tipo: TipoOrigenProgramacion,
    id: number,
    payload: GenerarTurnosPayload,
  ): Observable<LoteResultadoResponse> {
    return this.http
      .post<LoteResultadoResponse>(`${this.baseUrl}/origenes/${tipo}/${id}/generar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  previewRegeneracion(
    tipo: TipoOrigenProgramacion,
    id: number,
  ): Observable<PreviewRegeneracionResponse> {
    return this.http
      .get<PreviewRegeneracionResponse>(
        `${this.baseUrl}/origenes/${tipo}/${id}/preview-regeneracion`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  regenerar(
    tipo: TipoOrigenProgramacion,
    id: number,
    payload: GenerarTurnosPayload,
  ): Observable<LoteResultadoResponse> {
    return this.http
      .post<LoteResultadoResponse>(`${this.baseUrl}/origenes/${tipo}/${id}/regenerar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getProfesionales(sucursalId: number): Observable<OpcionesAgendaResponse> {
    return this.http
      .get<OpcionesAgendaResponse>(`${this.agendaUrl}/opciones`, {
        params: new HttpParams().set('sucursal_id', String(sucursalId)),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getFeriados(params: {
    sucursal_id?: number | null;
    anio?: number | null;
    activo?: boolean | null;
  } = {}): Observable<FeriadosResponse> {
    let httpParams = new HttpParams();

    if (params.sucursal_id !== null && params.sucursal_id !== undefined) {
      httpParams = httpParams.set('sucursal_id', String(params.sucursal_id));
    }

    if (params.anio !== null && params.anio !== undefined) {
      httpParams = httpParams.set('anio', String(params.anio));
    }

    if (params.activo !== null && params.activo !== undefined) {
      httpParams = httpParams.set('activo', params.activo ? '1' : '0');
    }

    return this.http
      .get<FeriadosResponse>(`${this.baseUrl}/feriados`, { params: httpParams })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  crearFeriado(payload: {
    fecha: string;
    descripcion: string;
    sucursal_id: number | null;
  }): Observable<{ data: Feriado; can: unknown }> {
    return this.http
      .post<{ data: Feriado; can: unknown }>(`${this.baseUrl}/feriados`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  desactivarFeriado(id: number): Observable<{ data: Feriado; can: unknown }> {
    return this.http
      .post<{ data: Feriado; can: unknown }>(`${this.baseUrl}/feriados/${id}/desactivar`, {})
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  private previewParams(params: PreviewGeneracionParams): HttpParams {
    let httpParams = new HttpParams().set(
      'usar_profesional_origen',
      params.usar_profesional_origen ? '1' : '0',
    );

    if (params.profesional_id !== null && params.profesional_id !== undefined) {
      httpParams = httpParams.set('profesional_id', String(params.profesional_id));
    }

    return httpParams;
  }

  private fail(error: unknown): Observable<never> {
    if (error instanceof HttpErrorResponse) {
      const body = (error.error ?? {}) as Partial<ProgramacionApiError>;

      return throwError(
        () =>
          ({
            status: error.status,
            message: body.message ?? 'No se pudo completar la operación.',
            code: body.code,
            context: body.context,
            errors: body.errors,
          }) satisfies ProgramacionApiError,
      );
    }

    return throwError(
      () =>
        ({
          status: 0,
          message: 'No se pudo conectar con el servidor.',
        }) satisfies ProgramacionApiError,
    );
  }
}
