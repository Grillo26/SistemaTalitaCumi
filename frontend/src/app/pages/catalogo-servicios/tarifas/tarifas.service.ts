import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, forkJoin, map, of, switchMap, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  PaginatedResponse as CatalogoPaginatedResponse,
  Servicio,
} from '../servicios/servicios.models';
import { CatalogoServiciosService } from '../servicios/servicios.service';
import {
  AplicableParams,
  AplicableResponse,
  CategoriaListParams,
  CategoriaPayload,
  CategoriaTarifa,
  CloseVigenciaPayload,
  DetailResponse,
  PaginatedResponse,
  SimpleListResponse,
  StatusCategoriaPayload,
  Tarifa,
  TarifaListParams,
  TarifaPayload,
  TarifasApiError,
} from './tarifas.models';

@Injectable({ providedIn: 'root' })
export class TarifasService {
  private readonly http = inject(HttpClient);
  private readonly catalogo = inject(CatalogoServiciosService);
  private readonly baseUrl = `${environment.apiUrl}/catalogo-servicios`;

  listCategorias(
    params: CategoriaListParams = {},
  ): Observable<SimpleListResponse<CategoriaTarifa>> {
    return this.http
      .get<SimpleListResponse<CategoriaTarifa>>(`${this.baseUrl}/categorias-tarifa`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getCategoria(id: number): Observable<DetailResponse<CategoriaTarifa>> {
    return this.http
      .get<DetailResponse<CategoriaTarifa>>(`${this.baseUrl}/categorias-tarifa/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createCategoria(payload: CategoriaPayload): Observable<DetailResponse<CategoriaTarifa>> {
    return this.http
      .post<DetailResponse<CategoriaTarifa>>(`${this.baseUrl}/categorias-tarifa`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateCategoria(
    id: number,
    payload: CategoriaPayload,
  ): Observable<DetailResponse<CategoriaTarifa>> {
    return this.http
      .patch<DetailResponse<CategoriaTarifa>>(`${this.baseUrl}/categorias-tarifa/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activateCategoria(
    id: number,
    payload: StatusCategoriaPayload,
  ): Observable<DetailResponse<CategoriaTarifa>> {
    return this.http
      .post<DetailResponse<CategoriaTarifa>>(
        `${this.baseUrl}/categorias-tarifa/${id}/activar`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivateCategoria(
    id: number,
    payload: StatusCategoriaPayload,
  ): Observable<DetailResponse<CategoriaTarifa>> {
    return this.http
      .post<DetailResponse<CategoriaTarifa>>(
        `${this.baseUrl}/categorias-tarifa/${id}/desactivar`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listTarifas(params: TarifaListParams = {}): Observable<PaginatedResponse<Tarifa>> {
    return this.http
      .get<PaginatedResponse<Tarifa>>(`${this.baseUrl}/tarifas`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getTarifa(id: number): Observable<DetailResponse<Tarifa>> {
    return this.http
      .get<DetailResponse<Tarifa>>(`${this.baseUrl}/tarifas/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createTarifa(payload: TarifaPayload): Observable<DetailResponse<Tarifa>> {
    return this.http
      .post<DetailResponse<Tarifa>>(`${this.baseUrl}/tarifas`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateTarifa(id: number, payload: TarifaPayload): Observable<DetailResponse<Tarifa>> {
    return this.http
      .patch<DetailResponse<Tarifa>>(`${this.baseUrl}/tarifas/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  closeVigencia(
    id: number,
    payload: CloseVigenciaPayload,
  ): Observable<DetailResponse<Tarifa>> {
    return this.http
      .post<DetailResponse<Tarifa>>(`${this.baseUrl}/tarifas/${id}/cerrar-vigencia`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  consultarAplicable(params: AplicableParams): Observable<AplicableResponse> {
    return this.http
      .get<AplicableResponse>(`${this.baseUrl}/tarifas/aplicable`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  /**
   * Carga todos los servicios activos desde la API del submódulo 01.
   */
  listServiciosActivos(): Observable<Servicio[]> {
    return this.catalogo
      .listServicios({ estado: 'activo', page: 1, per_page: 50 })
      .pipe(
        switchMap((first) => {
          if (first.meta.last_page <= 1) {
            return of(first.data);
          }

          const requests: Observable<CatalogoPaginatedResponse<Servicio>>[] = [];

          for (let page = 2; page <= first.meta.last_page; page++) {
            requests.push(
              this.catalogo.listServicios({ estado: 'activo', page, per_page: 50 }),
            );
          }

          return forkJoin(requests).pipe(
            map((pages) => [...first.data, ...pages.flatMap((page) => page.data)]),
          );
        }),
      );
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

  private normalizeError(error: unknown): TarifasApiError {
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
          active_tariffs_count:
            typeof body.active_tariffs_count === 'number'
              ? body.active_tariffs_count
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
