import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  Ambiente,
  AmbienteCreatePayload,
  AmbienteListParams,
  AmbienteUpdatePayload,
  Area,
  CatalogoListParams,
  DetailResponse,
  PaginatedResponse,
  Sala,
  SalaCreatePayload,
  SalaListParams,
  SalaUpdatePayload,
  SalasAmbientesApiError,
  SimpleListResponse,
  StatusPayload,
  TipoSala,
  TipoSalaPayload,
  TurnoCatalogo,
  TurnoCatalogoPayload,
} from './salas-ambientes.models';

@Injectable({ providedIn: 'root' })
export class SalasAmbientesService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/organizacion/salas-ambientes`;

  listTiposSala(params: CatalogoListParams): Observable<SimpleListResponse<TipoSala>> {
    return this.http
      .get<SimpleListResponse<TipoSala>>(`${this.baseUrl}/tipos-sala`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getTipoSala(id: number): Observable<DetailResponse<TipoSala>> {
    return this.http
      .get<DetailResponse<TipoSala>>(`${this.baseUrl}/tipos-sala/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createTipoSala(payload: TipoSalaPayload): Observable<DetailResponse<TipoSala>> {
    return this.http
      .post<DetailResponse<TipoSala>>(`${this.baseUrl}/tipos-sala`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateTipoSala(id: number, payload: TipoSalaPayload): Observable<DetailResponse<TipoSala>> {
    return this.http
      .patch<DetailResponse<TipoSala>>(`${this.baseUrl}/tipos-sala/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activateTipoSala(id: number, payload: StatusPayload): Observable<DetailResponse<TipoSala>> {
    return this.http
      .post<DetailResponse<TipoSala>>(`${this.baseUrl}/tipos-sala/${id}/activar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivateTipoSala(id: number, payload: StatusPayload): Observable<DetailResponse<TipoSala>> {
    return this.http
      .post<DetailResponse<TipoSala>>(`${this.baseUrl}/tipos-sala/${id}/desactivar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listTurnosCatalogo(params: CatalogoListParams): Observable<SimpleListResponse<TurnoCatalogo>> {
    return this.http
      .get<SimpleListResponse<TurnoCatalogo>>(`${this.baseUrl}/turnos-catalogo`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getTurnoCatalogo(id: number): Observable<DetailResponse<TurnoCatalogo>> {
    return this.http
      .get<DetailResponse<TurnoCatalogo>>(`${this.baseUrl}/turnos-catalogo/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createTurnoCatalogo(payload: TurnoCatalogoPayload): Observable<DetailResponse<TurnoCatalogo>> {
    return this.http
      .post<DetailResponse<TurnoCatalogo>>(`${this.baseUrl}/turnos-catalogo`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateTurnoCatalogo(id: number, payload: TurnoCatalogoPayload): Observable<DetailResponse<TurnoCatalogo>> {
    return this.http
      .patch<DetailResponse<TurnoCatalogo>>(`${this.baseUrl}/turnos-catalogo/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activateTurnoCatalogo(id: number, payload: StatusPayload): Observable<DetailResponse<TurnoCatalogo>> {
    return this.http
      .post<DetailResponse<TurnoCatalogo>>(`${this.baseUrl}/turnos-catalogo/${id}/activar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivateTurnoCatalogo(id: number, payload: StatusPayload): Observable<DetailResponse<TurnoCatalogo>> {
    return this.http
      .post<DetailResponse<TurnoCatalogo>>(`${this.baseUrl}/turnos-catalogo/${id}/desactivar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listSalas(params: SalaListParams): Observable<PaginatedResponse<Sala>> {
    return this.http
      .get<PaginatedResponse<Sala>>(`${this.baseUrl}/salas`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getSala(id: number): Observable<DetailResponse<Sala>> {
    return this.http
      .get<DetailResponse<Sala>>(`${this.baseUrl}/salas/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createSala(payload: SalaCreatePayload): Observable<DetailResponse<Sala>> {
    return this.http
      .post<DetailResponse<Sala>>(`${this.baseUrl}/salas`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateSala(id: number, payload: SalaUpdatePayload): Observable<DetailResponse<Sala>> {
    return this.http
      .patch<DetailResponse<Sala>>(`${this.baseUrl}/salas/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activateSala(id: number, payload: StatusPayload): Observable<DetailResponse<Sala>> {
    return this.http
      .post<DetailResponse<Sala>>(`${this.baseUrl}/salas/${id}/activar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivateSala(id: number, payload: StatusPayload): Observable<DetailResponse<Sala>> {
    return this.http
      .post<DetailResponse<Sala>>(`${this.baseUrl}/salas/${id}/desactivar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listAmbientes(params: AmbienteListParams): Observable<PaginatedResponse<Ambiente>> {
    return this.http
      .get<PaginatedResponse<Ambiente>>(`${this.baseUrl}/ambientes`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getAmbiente(id: number): Observable<DetailResponse<Ambiente>> {
    return this.http
      .get<DetailResponse<Ambiente>>(`${this.baseUrl}/ambientes/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createAmbiente(payload: AmbienteCreatePayload): Observable<DetailResponse<Ambiente>> {
    return this.http
      .post<DetailResponse<Ambiente>>(`${this.baseUrl}/ambientes`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateAmbiente(id: number, payload: AmbienteUpdatePayload): Observable<DetailResponse<Ambiente>> {
    return this.http
      .patch<DetailResponse<Ambiente>>(`${this.baseUrl}/ambientes/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activateAmbiente(id: number, payload: StatusPayload): Observable<DetailResponse<Ambiente>> {
    return this.http
      .post<DetailResponse<Ambiente>>(`${this.baseUrl}/ambientes/${id}/activar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivateAmbiente(id: number, payload: StatusPayload): Observable<DetailResponse<Ambiente>> {
    return this.http
      .post<DetailResponse<Ambiente>>(`${this.baseUrl}/ambientes/${id}/desactivar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listAreas(): Observable<SimpleListResponse<Area>> {
    return this.http
      .get<SimpleListResponse<Area>>(`${this.baseUrl}/areas`)
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

  private normalizeError(error: unknown): SalasAmbientesApiError {
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
          conflict:
            body.conflict && typeof body.conflict === 'object'
              ? body.conflict
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
