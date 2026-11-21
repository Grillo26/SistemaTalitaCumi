import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import {
  Especialidad,
  EspecialidadCreatePayload,
  EspecialidadListParams,
  EspecialidadListResponse,
  EspecialidadResponse,
  EspecialidadUpdatePayload,
  MatrizListParams,
  MatrizListResponse,
  MatrizOptionsResponse,
  Profesional,
  ProfesionalCreatePayload,
  ProfesionalListParams,
  ProfesionalListResponse,
  ProfesionalOptionsResponse,
  ProfesionalResponse,
  ProfesionalSucursalResponse,
  ProfesionalSucursalesResponse,
  ProfesionalUpdatePayload,
  ProfesionalesApiError,
  StatusPayload,
} from './profesionales.models';

@Injectable({ providedIn: 'root' })
export class ProfesionalesService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/profesionales`;

  listProfesionales(params: ProfesionalListParams): Observable<ProfesionalListResponse> {
    return this.http
      .get<ProfesionalListResponse>(`${this.baseUrl}/profesionales`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getProfesional(id: number): Observable<ProfesionalResponse> {
    return this.http
      .get<ProfesionalResponse>(`${this.baseUrl}/profesionales/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getOpciones(): Observable<ProfesionalOptionsResponse> {
    return this.http
      .get<ProfesionalOptionsResponse>(`${this.baseUrl}/profesionales/opciones`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createProfesional(payload: ProfesionalCreatePayload): Observable<ProfesionalResponse> {
    return this.http
      .post<ProfesionalResponse>(`${this.baseUrl}/profesionales`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateProfesional(id: number, payload: ProfesionalUpdatePayload): Observable<ProfesionalResponse> {
    return this.http
      .patch<ProfesionalResponse>(`${this.baseUrl}/profesionales/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activateProfesional(id: number, payload: StatusPayload): Observable<ProfesionalResponse> {
    return this.http
      .post<ProfesionalResponse>(`${this.baseUrl}/profesionales/${id}/activar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivateProfesional(id: number, payload: StatusPayload): Observable<ProfesionalResponse> {
    return this.http
      .post<ProfesionalResponse>(`${this.baseUrl}/profesionales/${id}/desactivar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listSucursales(id: number): Observable<ProfesionalSucursalesResponse> {
    return this.http
      .get<ProfesionalSucursalesResponse>(`${this.baseUrl}/profesionales/${id}/sucursales`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  assignSucursal(id: number, sucursalId: number): Observable<ProfesionalSucursalResponse> {
    return this.http
      .post<ProfesionalSucursalResponse>(`${this.baseUrl}/profesionales/${id}/sucursales`, {
        sucursal_id: sucursalId,
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activateSucursal(id: number, sucursalId: number, payload: StatusPayload): Observable<ProfesionalSucursalResponse> {
    return this.http
      .post<ProfesionalSucursalResponse>(
        `${this.baseUrl}/profesionales/${id}/sucursales/${sucursalId}/activar`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivateSucursal(id: number, sucursalId: number, payload: StatusPayload): Observable<ProfesionalSucursalResponse> {
    return this.http
      .post<ProfesionalSucursalResponse>(
        `${this.baseUrl}/profesionales/${id}/sucursales/${sucursalId}/desactivar`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listMatriz(params: MatrizListParams): Observable<MatrizListResponse> {
    return this.http
      .get<MatrizListResponse>(`${this.baseUrl}/matriz`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getMatrizOpciones(): Observable<MatrizOptionsResponse> {
    return this.http
      .get<MatrizOptionsResponse>(`${this.baseUrl}/matriz/opciones`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listEspecialidades(params: EspecialidadListParams): Observable<EspecialidadListResponse> {
    return this.http
      .get<EspecialidadListResponse>(`${this.baseUrl}/especialidades`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getEspecialidad(id: number): Observable<EspecialidadResponse> {
    return this.http
      .get<EspecialidadResponse>(`${this.baseUrl}/especialidades/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createEspecialidad(payload: EspecialidadCreatePayload): Observable<EspecialidadResponse> {
    return this.http
      .post<EspecialidadResponse>(`${this.baseUrl}/especialidades`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateEspecialidad(id: number, payload: EspecialidadUpdatePayload): Observable<EspecialidadResponse> {
    return this.http
      .patch<EspecialidadResponse>(`${this.baseUrl}/especialidades/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activateEspecialidad(id: number, payload: StatusPayload): Observable<EspecialidadResponse> {
    return this.http
      .post<EspecialidadResponse>(`${this.baseUrl}/especialidades/${id}/activar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivateEspecialidad(id: number, payload: StatusPayload): Observable<EspecialidadResponse> {
    return this.http
      .post<EspecialidadResponse>(`${this.baseUrl}/especialidades/${id}/desactivar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
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
    return throwError(() => this.normalizeError(error));
  }

  private normalizeError(error: unknown): ProfesionalesApiError {
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
          profesionales_activos_count:
            typeof body.profesionales_activos_count === 'number'
              ? body.profesionales_activos_count
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
