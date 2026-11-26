import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  Area,
  AreaListParams,
  AreaPayload,
  CatalogoServiciosApiError,
  DetailResponse,
  PaginatedResponse,
  Servicio,
  ServicioListParams,
  ServicioPayload,
  SimpleListResponse,
  StatusPayload,
  TipoServicio,
  TipoServicioListParams,
  TipoServicioPayload,
} from './servicios.models';

@Injectable({ providedIn: 'root' })
export class CatalogoServiciosService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/catalogo-servicios`;

  listAreas(params: AreaListParams = {}): Observable<SimpleListResponse<Area>> {
    return this.http
      .get<SimpleListResponse<Area>>(`${this.baseUrl}/areas`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getArea(id: number): Observable<DetailResponse<Area>> {
    return this.http
      .get<DetailResponse<Area>>(`${this.baseUrl}/areas/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createArea(payload: AreaPayload): Observable<DetailResponse<Area>> {
    return this.http
      .post<DetailResponse<Area>>(`${this.baseUrl}/areas`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateArea(id: number, payload: AreaPayload): Observable<DetailResponse<Area>> {
    return this.http
      .patch<DetailResponse<Area>>(`${this.baseUrl}/areas/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activateArea(id: number, payload: StatusPayload): Observable<DetailResponse<Area>> {
    return this.http
      .post<DetailResponse<Area>>(`${this.baseUrl}/areas/${id}/activar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivateArea(id: number, payload: StatusPayload): Observable<DetailResponse<Area>> {
    return this.http
      .post<DetailResponse<Area>>(`${this.baseUrl}/areas/${id}/desactivar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listServicios(params: ServicioListParams = {}): Observable<PaginatedResponse<Servicio>> {
    return this.http
      .get<PaginatedResponse<Servicio>>(`${this.baseUrl}/servicios`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getServicio(id: number): Observable<DetailResponse<Servicio>> {
    return this.http
      .get<DetailResponse<Servicio>>(`${this.baseUrl}/servicios/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createServicio(payload: ServicioPayload): Observable<DetailResponse<Servicio>> {
    return this.http
      .post<DetailResponse<Servicio>>(`${this.baseUrl}/servicios`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateServicio(id: number, payload: ServicioPayload): Observable<DetailResponse<Servicio>> {
    return this.http
      .patch<DetailResponse<Servicio>>(`${this.baseUrl}/servicios/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activateServicio(id: number, payload: StatusPayload): Observable<DetailResponse<Servicio>> {
    return this.http
      .post<DetailResponse<Servicio>>(`${this.baseUrl}/servicios/${id}/activar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivateServicio(id: number, payload: StatusPayload): Observable<DetailResponse<Servicio>> {
    return this.http
      .post<DetailResponse<Servicio>>(`${this.baseUrl}/servicios/${id}/desactivar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listTiposServicio(
    params: TipoServicioListParams = {},
  ): Observable<SimpleListResponse<TipoServicio>> {
    return this.http
      .get<SimpleListResponse<TipoServicio>>(`${this.baseUrl}/tipos-servicio`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getTipoServicio(id: number): Observable<DetailResponse<TipoServicio>> {
    return this.http
      .get<DetailResponse<TipoServicio>>(`${this.baseUrl}/tipos-servicio/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createTipoServicio(payload: TipoServicioPayload): Observable<DetailResponse<TipoServicio>> {
    return this.http
      .post<DetailResponse<TipoServicio>>(`${this.baseUrl}/tipos-servicio`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateTipoServicio(
    id: number,
    payload: TipoServicioPayload,
  ): Observable<DetailResponse<TipoServicio>> {
    return this.http
      .patch<DetailResponse<TipoServicio>>(`${this.baseUrl}/tipos-servicio/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activateTipoServicio(id: number, payload: StatusPayload): Observable<DetailResponse<TipoServicio>> {
    return this.http
      .post<DetailResponse<TipoServicio>>(`${this.baseUrl}/tipos-servicio/${id}/activar`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivateTipoServicio(
    id: number,
    payload: StatusPayload,
  ): Observable<DetailResponse<TipoServicio>> {
    return this.http
      .post<DetailResponse<TipoServicio>>(`${this.baseUrl}/tipos-servicio/${id}/desactivar`, payload)
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

  private normalizeError(error: unknown): CatalogoServiciosApiError {
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
          active_services_count:
            typeof body.active_services_count === 'number'
              ? body.active_services_count
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
