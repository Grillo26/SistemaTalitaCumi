import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import {
  ConsentimientoIndexResponse,
  ConsentimientoPayload,
  ConsentimientoResponse,
  ConsentimientoTipo,
  ConsentimientoTipoListResponse,
  ConsentimientoTipoPayload,
  ConsentimientoTipoUpdatePayload,
  FichaIngresoCreatePayload,
  FichaIngresoPermissionResponse,
  FichaIngresoResponse,
  FichaIngresoRevisionResponse,
  FichaIngresoUpdatePayload,
  FichaIngresoApiError,
} from './ficha-ingreso.models';

@Injectable({ providedIn: 'root' })
export class FichaIngresoService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/pacientes/pacientes`;
  private readonly catalogUrl = `${environment.apiUrl}/pacientes/ficha-ingreso/consentimientos/tipos`;

  get(pacienteId: number): Observable<FichaIngresoResponse> {
    return this.http
      .get<FichaIngresoResponse>(`${this.baseUrl}/${pacienteId}/ficha-ingreso`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getById(pacienteId: number, fichaId: number): Observable<FichaIngresoPermissionResponse> {
    return this.http
      .get<FichaIngresoPermissionResponse>(
        `${this.baseUrl}/${pacienteId}/ficha-ingreso/${fichaId}`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  create(
    pacienteId: number,
    payload: FichaIngresoCreatePayload,
  ): Observable<FichaIngresoPermissionResponse> {
    return this.http
      .post<FichaIngresoPermissionResponse>(
        `${this.baseUrl}/${pacienteId}/ficha-ingreso`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  update(
    pacienteId: number,
    fichaId: number,
    payload: FichaIngresoUpdatePayload,
  ): Observable<FichaIngresoPermissionResponse> {
    return this.http
      .patch<FichaIngresoPermissionResponse>(
        `${this.baseUrl}/${pacienteId}/ficha-ingreso/${fichaId}`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  complete(
    pacienteId: number,
    fichaId: number,
    version: number,
  ): Observable<FichaIngresoPermissionResponse> {
    return this.http
      .post<FichaIngresoPermissionResponse>(
        `${this.baseUrl}/${pacienteId}/ficha-ingreso/${fichaId}/completar`,
        { version },
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  archive(
    pacienteId: number,
    fichaId: number,
    motivo: string,
    version: number,
  ): Observable<FichaIngresoPermissionResponse> {
    return this.http
      .post<FichaIngresoPermissionResponse>(
        `${this.baseUrl}/${pacienteId}/ficha-ingreso/${fichaId}/archivar`,
        { motivo, version },
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  reactivate(
    pacienteId: number,
    fichaId: number,
    motivo: string,
    version: number,
  ): Observable<FichaIngresoPermissionResponse> {
    return this.http
      .post<FichaIngresoPermissionResponse>(
        `${this.baseUrl}/${pacienteId}/ficha-ingreso/${fichaId}/reactivar`,
        { motivo, version },
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  revisions(pacienteId: number, fichaId: number): Observable<FichaIngresoRevisionResponse> {
    return this.http
      .get<FichaIngresoRevisionResponse>(
        `${this.baseUrl}/${pacienteId}/ficha-ingreso/${fichaId}/revisiones`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  consentimientos(
    pacienteId: number,
    fichaId: number,
  ): Observable<ConsentimientoIndexResponse> {
    return this.http
      .get<ConsentimientoIndexResponse>(
        `${this.baseUrl}/${pacienteId}/ficha-ingreso/${fichaId}/consentimientos`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  registerConsentimiento(
    pacienteId: number,
    fichaId: number,
    payload: ConsentimientoPayload,
  ): Observable<ConsentimientoResponse> {
    return this.http
      .post<ConsentimientoResponse>(
        `${this.baseUrl}/${pacienteId}/ficha-ingreso/${fichaId}/consentimientos`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  revokeConsentimiento(
    pacienteId: number,
    fichaId: number,
    consentimientoId: number,
    motivo: string,
  ): Observable<ConsentimientoResponse> {
    return this.http
      .post<ConsentimientoResponse>(
        `${this.baseUrl}/${pacienteId}/ficha-ingreso/${fichaId}/consentimientos/${consentimientoId}/revocar`,
        { motivo },
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  tipos(incluirInactivos = false): Observable<ConsentimientoTipoListResponse> {
    const url = incluirInactivos ? `${this.catalogUrl}?incluir_inactivos=1` : this.catalogUrl;

    return this.http
      .get<ConsentimientoTipoListResponse>(url)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createTipo(payload: ConsentimientoTipoPayload): Observable<{ data: ConsentimientoTipo }> {
    return this.http
      .post<{ data: ConsentimientoTipo }>(this.catalogUrl, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateTipo(
    tipoId: number,
    payload: ConsentimientoTipoUpdatePayload,
  ): Observable<{ data: ConsentimientoTipo }> {
    return this.http
      .patch<{ data: ConsentimientoTipo }>(`${this.catalogUrl}/${tipoId}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  private fail(error: unknown): Observable<never> {
    return throwError(() => this.normalizeError(error));
  }

  private normalizeError(error: unknown): FichaIngresoApiError {
    if (error instanceof HttpErrorResponse) {
      const body = error.error;

      if (body && typeof body === 'object') {
        return {
          status: error.status,
          message:
            typeof body.message === 'string' && body.message.trim() !== ''
              ? body.message
              : 'No se pudo completar la operación.',
          code: typeof body.code === 'string' ? body.code : undefined,
          errors:
            body.errors && typeof body.errors === 'object'
              ? (body.errors as Record<string, string[]>)
              : undefined,
          data:
            body.data && typeof body.data === 'object'
              ? (body.data as FichaIngresoApiError['data'])
              : undefined,
        };
      }

      if (error.status === 0) {
        return {
          status: 0,
          message: 'No se pudo conectar con el servidor. Reintentá la operación.',
        };
      }

      return { status: error.status, message: error.message };
    }

    return { status: 0, message: 'Ocurrió un error inesperado.' };
  }
}
