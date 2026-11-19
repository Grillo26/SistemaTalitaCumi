import { HttpClient } from '@angular/common/http';
import { Injectable, inject } from '@angular/core';
import { Observable } from 'rxjs';
import { environment } from '../../environments/environment';

export interface HealthResponse {
  status: 'ok' | 'degraded';
  service: string;
  timestamp: string;
  app: {
    environment: string;
    locale: string;
    timezone: string;
    currency: string;
    currency_symbol: string;
  };
  database: {
    status: 'connected' | 'disconnected';
    driver: string;
    database?: string;
  };
}

@Injectable({ providedIn: 'root' })
export class HealthService {
  private readonly http = inject(HttpClient);

  getHealth(): Observable<HealthResponse> {
    return this.http.get<HealthResponse>(`${environment.apiUrl}/health`);
  }
}
