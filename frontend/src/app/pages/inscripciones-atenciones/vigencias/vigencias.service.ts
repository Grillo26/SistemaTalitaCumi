import { HttpClient, HttpErrorResponse, HttpParams } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable, catchError, from, switchMap, throwError } from 'rxjs';
import { environment } from '../../../../environments/environment';
import {
  ExportFormat,
  PaginatedVigencias,
  RenovacionMultipleResult,
  RenovacionPreview,
  RenovacionResult,
  ResumenVigenciaPaciente,
  VigenciaAbilities,
  VigenciaApiError,
  VentanaAlerta,
} from './vigencias.models';

export interface VigenciaListParams {
  search?: string;
  sucursal_id?: number | '';
  paciente_id?: number | '';
  servicio_id?: number | '';
  tipo_entidad?: string;
  estado?: string;
  periodo_desde?: string;
  periodo_hasta?: string;
  page?: number;
  per_page?: number;
  sort?: string;
  direction?: 'asc' | 'desc';
}

export interface RenovacionPayload {
  fecha_inicio?: string;
  fecha_fin?: string;
  monto_visto: string;
  tarifa_id_vista: number;
  confirmar_tarifa_cambio?: boolean;
  observaciones?: string | null;
}

export interface RenovacionMultiplePayload {
  items: Array<
    RenovacionPayload & {
      tipo_entidad: string;
      entidad_id: number;
    }
  >;
}

@Injectable({ providedIn: 'root' })
export class VigenciasService {
  private readonly http = inject(HttpClient);
  private readonly baseUrl = `${environment.apiUrl}/inscripciones-atenciones/vigencias`;

  listPanel(params: VigenciaListParams = {}): Observable<PaginatedVigencias> {
    return this.http
      .get<PaginatedVigencias>(`${this.baseUrl}/panel`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  listReporte(params: VigenciaListParams = {}): Observable<PaginatedVigencias> {
    return this.http
      .get<PaginatedVigencias>(`${this.baseUrl}/reportes`, {
        params: this.buildParams(params),
      })
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  exportar(formato: ExportFormat, params: VigenciaListParams = {}): Observable<Blob> {
    return this.http
      .get(`${this.baseUrl}/reportes/exportar`, {
        params: this.buildParams({ ...params, formato }),
        responseType: 'blob',
      })
      .pipe(catchError((error: unknown) => this.failBlob(error)));
  }

  getRenovacion(tipo: string, id: number): Observable<{ data: RenovacionPreview; can: unknown }> {
    return this.http
      .get<{ data: RenovacionPreview; can: unknown }>(
        `${this.baseUrl}/entidades/${tipo}/${id}/renovacion`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  renovar(
    tipo: string,
    id: number,
    payload: RenovacionPayload,
  ): Observable<{ data: RenovacionResult; can: unknown }> {
    return this.http
      .post<{ data: RenovacionResult; can: unknown }>(
        `${this.baseUrl}/entidades/${tipo}/${id}/renovar`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  renovarMultiple(
    payload: RenovacionMultiplePayload,
  ): Observable<{ data: RenovacionMultipleResult; can: unknown }> {
    return this.http
      .post<{ data: RenovacionMultipleResult; can: unknown }>(
        `${this.baseUrl}/renovaciones`,
        payload,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getResumenPaciente(
    pacienteId: number,
  ): Observable<{ data: ResumenVigenciaPaciente; can: unknown }> {
    return this.http
      .get<{ data: ResumenVigenciaPaciente; can: unknown }>(
        `${this.baseUrl}/pacientes/${pacienteId}/resumen`,
      )
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  getVentana(): Observable<{ data: VentanaAlerta; can: VigenciaAbilities }> {
    return this.http
      .get<{ data: VentanaAlerta; can: VigenciaAbilities }>(`${this.baseUrl}/configuracion/ventana`)
      .pipe(catchError((error: unknown) => this.fail(error)));
  }

  updateVentana(
    valor: number,
    version: number,
  ): Observable<{ data: VentanaAlerta; can: VigenciaAbilities }> {
    return this.http
      .patch<{ data: VentanaAlerta; can: VigenciaAbilities }>(
        `${this.baseUrl}/configuracion/ventana`,
        { valor, version },
      )
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
    if (error instanceof HttpErrorResponse) {
      return throwError(() => this.toError(error, error.error ?? {}));
    }

    return throwError(
      () =>
        ({
          status: 0,
          message: 'No se pudo conectar con el servidor.',
        }) satisfies VigenciaApiError,
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

  private toError(error: HttpErrorResponse, body: Record<string, unknown>): VigenciaApiError {
    return {
      status: error.status,
      message:
        typeof body['message'] === 'string'
          ? body['message']
          : 'No se pudo completar la operación.',
      code: typeof body['code'] === 'string' ? body['code'] : undefined,
      motivo: typeof body['motivo'] === 'string' ? body['motivo'] : undefined,
      errors: (body['errors'] as Record<string, string[]> | undefined) ?? undefined,
      monto_visto: typeof body['monto_visto'] === 'string' ? body['monto_visto'] : undefined,
      tarifa_vigente: (body['tarifa_vigente'] as never) ?? undefined,
      tarifa_aplicable: (body['tarifa_aplicable'] as never) ?? undefined,
      modulo: typeof body['modulo'] === 'string' ? body['modulo'] : undefined,
    };
  }
}
