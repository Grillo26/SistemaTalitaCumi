import { Component, OnInit, inject, signal } from '@angular/core';
import { environment } from '../environments/environment';
import { HealthResponse, HealthService } from './services/health.service';

type ConnectionState = 'loading' | 'connected' | 'error';

@Component({
  selector: 'app-root',
  templateUrl: './app.html',
  styleUrl: './app.scss',
})
export class App implements OnInit {
  private readonly healthService = inject(HealthService);

  protected readonly appName = environment.appName;
  protected readonly apiUrl = environment.apiUrl;
  protected readonly currencySymbol = environment.currencySymbol;
  protected readonly state = signal<ConnectionState>('loading');
  protected readonly health = signal<HealthResponse | null>(null);
  protected readonly errorMessage = signal<string | null>(null);

  ngOnInit(): void {
    this.checkHealth();
  }

  protected checkHealth(): void {
    this.state.set('loading');
    this.errorMessage.set(null);

    this.healthService.getHealth().subscribe({
      next: (health) => {
        this.health.set(health);
        this.state.set(health.database.status === 'connected' ? 'connected' : 'error');
      },
      error: () => {
        this.health.set(null);
        this.errorMessage.set('No se pudo contactar al backend. Verifica que los contenedores estén activos.');
        this.state.set('error');
      },
    });
  }
}
