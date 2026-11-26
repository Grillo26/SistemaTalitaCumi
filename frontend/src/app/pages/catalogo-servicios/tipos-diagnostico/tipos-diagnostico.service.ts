import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, forkJoin, map, of, switchMap, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  Especialidad,
  EspecialidadListResponse,
} from '../../profesionales/profesionales.models';
import { ProfesionalesService } from '../../profesionales/profesionales.service';
import {
  PaginatedResponse as CatalogoPaginatedResponse,
  Servicio,
} from '../servicios/servicios.models';
import { CatalogoServiciosService } from '../servicios/servicios.service';
import {
  DetailResponse,
  PaginatedResponse,
  StatusTipoDiagnosticoPayload,
  TipoDiagnostico,
  TipoDiagnosticoListParams,
  TipoDiagnosticoPayload,
  TiposDiagnosticoApiError,
} from './tipos-diagnostico.models';

@Injectable({ providedIn: 'root' })
export class TiposDiagnosticoService {
  private readonly http = inject(HttpClient);
  private readonly catalogo = inject(CatalogoServiciosService);
  private readonly profesionales = inject(ProfesionalesService);
  private readonly baseUrl = `${environment.apiUrl}/catalogo-servicios`;

  listTipos(
    params: TipoDiagnosticoListParams = {},
  ): Observable<PaginatedResponse<TipoDiagnostico>> {
    return this.http
      .get<PaginatedResponse<TipoDiagnostico>>(`${this.baseUrl}/tipos-diagnostico`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getTipo(id: number): Observable<DetailResponse<TipoDiagnostico>> {
    return this.http
      .get<DetailResponse<TipoDiagnostico>>(`${this.baseUrl}/tipos-diagnostico/${id}`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  createTipo(payload: TipoDiagnosticoPayload): Observable<DetailResponse<TipoDiagnostico>> {
    return this.http
      .post<DetailResponse<TipoDiagnostico>>(`${this.baseUrl}/tipos-diagnostico`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateTipo(
    id: number,
    payload: TipoDiagnosticoPayload,
  ): Observable<DetailResponse<TipoDiagnostico>> {
    return this.http
      .patch<DetailResponse<TipoDiagnostico>>(`${this.baseUrl}/tipos-diagnostico/${id}`, payload)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  activateTipo(
    id: number,
    payload: StatusTipoDiagnosticoPayload,
  ): Observable<DetailResponse<TipoDiagnostico>> {
    return this.http
      .post<DetailResponse<TipoDiagnostico>>(
        `${this.baseUrl}/tipos-diagnostico/${id}/activar`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  deactivateTipo(
    id: number,
    payload: StatusTipoDiagnosticoPayload,
  ): Observable<DetailResponse<TipoDiagnostico>> {
    return this.http
      .post<DetailResponse<TipoDiagnostico>>(
        `${this.baseUrl}/tipos-diagnostico/${id}/desactivar`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  /**
   * Especialidades desde el catálogo real del módulo 03.
   */
  listEspecialidades(): Observable<Especialidad[]> {
    return this.profesionales
      .listEspecialidades({ page: 1, per_page: 50 })
      .pipe(
        switchMap((first) => {
          if (first.meta.last_page <= 1) {
            return of(first.data);
          }

          const requests: Observable<EspecialidadListResponse>[] = [];

          for (let page = 2; page <= first.meta.last_page; page++) {
            requests.push(
              this.profesionales.listEspecialidades({ page, per_page: 50 }),
            );
          }

          return forkJoin(requests).pipe(
            map((pages) => [...first.data, ...pages.flatMap((page) => page.data)]),
          );
        }),
      );
  }

  /**
   * Servicios activos cuyo tipo de servicio es diagnóstico/evaluación.
   */
  listServiciosDiagnostico(): Observable<Servicio[]> {
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
        map((servicios) =>
          servicios.filter(
            (servicio) => servicio.tipo_servicio?.codigo === 'diagnostico_evaluacion',
          ),
        ),
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

  private normalizeError(error: unknown): TiposDiagnosticoApiError {
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
