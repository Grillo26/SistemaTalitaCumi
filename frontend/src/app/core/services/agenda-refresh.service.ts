import { Injectable } from '@angular/core';
import { Subject } from 'rxjs';

/**
 * Punto de integración de RF-012 para los futuros submódulos 7.3 y 7.4.
 *
 * Cuando una acción (asistencia, reprogramación o cancelación) termina, el
 * componente que la ejecuta llama a `notificarCambio()`. La agenda escucha y
 * recarga la semana sin recargar la página.
 */
@Injectable({ providedIn: 'root' })
export class AgendaRefreshService {
  private readonly changesSubject = new Subject<void>();

  readonly changes$ = this.changesSubject.asObservable();

  notificarCambio(): void {
    this.changesSubject.next();
  }
}
