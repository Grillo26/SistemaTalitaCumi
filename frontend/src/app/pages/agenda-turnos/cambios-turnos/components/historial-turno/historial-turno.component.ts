import { Component, effect, inject, input, output, signal } from '@angular/core';
import { AlertComponent } from '../../../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../../../../shared/components/ui/modal/modal.component';
import {
  CAMBIOS_TEXTS,
  fechaHoraLegible,
  fechaLegible,
  type CambiosApiError,
  type HistorialTurnoData,
} from '../../cambios-turnos.models';
import { CambiosTurnosService } from '../../cambios-turnos.service';

@Component({
  selector: 'app-historial-turno',
  imports: [AlertComponent, ButtonComponent, ModalComponent],
  templateUrl: './historial-turno.component.html',
})
export class HistorialTurnoComponent {
  private readonly service = inject(CambiosTurnosService);

  readonly open = input(false);
  readonly turnoId = input<number | null>(null);

  readonly closed = output<void>();
  readonly verTurno = output<number>();

  readonly texts = CAMBIOS_TEXTS;
  readonly fechaLegible = fechaLegible;
  readonly fechaHoraLegible = fechaHoraLegible;

  readonly data = signal<HistorialTurnoData | null>(null);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  constructor() {
    effect(() => {
      const abierto = this.open();
      const id = this.turnoId();

      if (abierto && id !== null) {
        this.cargar(id);
      }
    });
  }

  private cargar(id: number): void {
    this.loading.set(true);
    this.error.set(null);
    this.data.set(null);

    this.service.getHistorial(id).subscribe({
      next: (response) => {
        this.loading.set(false);
        this.data.set(response.data);
      },
      error: (error: CambiosApiError) => {
        this.loading.set(false);
        this.error.set(error.message);
      },
    });
  }

  cerrar(): void {
    this.closed.emit();
  }

  abrirVinculado(id: number): void {
    this.verTurno.emit(id);
  }
}
