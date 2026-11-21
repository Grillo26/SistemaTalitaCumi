import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../environments/environment';
import {
  PacienteCreatePayload,
  PacienteListParams,
  PacienteListResponse,
  PacienteOptionsResponse,
  PacienteResponse,
  PacienteStatusPayload,
  PacienteTutorAssociation,
  PacienteUpdatePayload,
  PacientesApiError,
  Tutor,
  TutorCreatePayload,
  TutorListParams,
  TutorListResponse,
  TutorResponse,
} from './pacientes.models';

@Injectable({ providedIn: 'root' })
export class PacientesService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/pacientes`;

  listPacientes(params: PacienteListParams): Observable<PacienteListResponse> {
    return this.http
      .get<PacienteListResponse>(`${this.baseUrl}/pacientes`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getPaciente(id: number): Observable<PacienteResponse> {
    return this.http
      .get<PacienteResponse>(`${this.baseUrl}/pacientes/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getOptions(): Observable<PacienteOptionsResponse> {
    return this.http
      .get<PacienteOptionsResponse>(`${this.baseUrl}/pacientes/opciones`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createPaciente(payload: PacienteCreatePayload): Observable<PacienteResponse> {
    return this.http
      .post<PacienteResponse>(`${this.baseUrl}/pacientes`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updatePaciente(id: number, payload: PacienteUpdatePayload): Observable<PacienteResponse> {
    return this.http
      .patch<PacienteResponse>(`${this.baseUrl}/pacientes/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  changeStatus(id: number, payload: PacienteStatusPayload): Observable<PacienteResponse> {
    return this.http
      .post<PacienteResponse>(`${this.baseUrl}/pacientes/${id}/estado`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listTutores(params: TutorListParams): Observable<TutorListResponse> {
    return this.http
      .get<TutorListResponse>(`${this.baseUrl}/tutores`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getTutor(id: number): Observable<TutorResponse> {
    return this.http
      .get<TutorResponse>(`${this.baseUrl}/tutores/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createTutor(payload: TutorCreatePayload): Observable<TutorResponse> {
    return this.http
      .post<TutorResponse>(`${this.baseUrl}/tutores`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  associateTutor(
    pacienteId: number,
    tutorId: number,
    esPrincipal = false,
  ): Observable<PacienteTutorAssociation> {
    return this.http
      .post<PacienteTutorAssociation>(`${this.baseUrl}/pacientes/${pacienteId}/tutores`, {
        tutor_id: tutorId,
        es_principal: esPrincipal,
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  setPrincipalTutor(pacienteId: number, tutorId: number): Observable<PacienteTutorAssociation> {
    return this.http
      .post<PacienteTutorAssociation>(
        `${this.baseUrl}/pacientes/${pacienteId}/tutores/${tutorId}/principal`,
        {},
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  dissociateTutor(pacienteId: number, tutorId: number): Observable<{ message: string }> {
    return this.http
      .delete<{ message: string }>(
        `${this.baseUrl}/pacientes/${pacienteId}/tutores/${tutorId}`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  private buildParams(params: object): HttpParams {
    let httpParams = new HttpParams();

    for (const [key, value] of Object.entries(params)) {
      if (value === undefined || value === null || value === '') {
        continue;
      }

      if (typeof value === 'boolean') {
        httpParams = httpParams.set(key, value ? '1' : '0');
        continue;
      }

      httpParams = httpParams.set(key, String(value));
    }

    return httpParams;
  }

  private fail(error: unknown): Observable<never> {
    return throwError(() => this.normalizeError(error));
  }

  private normalizeError(error: unknown): PacientesApiError {
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
              ? (body.data as PacientesApiError['data'])
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
