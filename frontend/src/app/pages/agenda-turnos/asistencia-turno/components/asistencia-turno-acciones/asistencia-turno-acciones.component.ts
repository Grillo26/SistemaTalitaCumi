import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { AuthService } from '../../../../../core/services/auth.service';
import { ToastService } from '../../../../../core/services/toast.service';
import { AlertComponent } from '../../../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import {
  ASISTENCIA_TEXTS,
  ESTADO_ASISTENCIA_LABEL,
  fechaHoraAsistencia,
  type AsistenciaApiError,
  type AsistenciaResultado,
  type AsistenciaTurnoData,
  type EstadoAsistencia,
} from '../../asistencia-turno.models';
import { AsistenciaTurnoService } from '../../asistencia-turno.service';
import { CorregirAsistenciaModalComponent } from '../corregir-asistencia-modal/corregir-asistencia-modal.component';

@Component({
  selector: 'app-asistencia-turno-acciones',
  imports: [
    AlertComponent,
    BadgeComponent,
    ButtonComponent,
    CorregirAsistenciaModalComponent,
  ],
  templateUrl: './asistencia-turno-acciones.component.html',
})
export class AsistenciaTurnoAccionesComponent {
  private readonly service = inject(AsistenciaTurnoService);
  private readonly toast = inject(ToastService);
  private readonly auth = inject(AuthService);

  readonly turnoId = input<number | null>(null);

  readonly actualizado = output<AsistenciaResultado>();
  readonly verHistorial = output<number>();

  readonly texts = ASISTENCIA_TEXTS;
  readonly fechaHora = fechaHoraAsistencia;
  readonly puedeVer = computed(() => this.auth.hasPermission('asistencia.ver'));

  readonly data = signal<AsistenciaTurnoData | null>(null);
  readonly loading = signal(false);
  readonly saving = signal(false);
  readonly error = signal<string | null>(null);
  readonly correccionOpen = signal(false);

  readonly estadoLabel = computed(() => {
    const data = this.data();

    return data === null
      ? ''
      : ESTADO_ASISTENCIA_LABEL[data.turno.estado] ?? data.turno.estado_label;
  });

  constructor() {
    effect(() => {
      const id = this.turnoId();

      if (id !== null && this.puedeVer()) {
        this.cargar(id);
      } else {
        this.data.set(null);
      }
    });
  }

  registrar(estado: EstadoAsistencia): void {
    const data = this.data();

    if (data === null || this.saving()) {
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    this.service.registrar(data.turno.id, estado, data.turno.version).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.toast.success(
          this.texts.title,
          response.data.sin_cambios
            ? this.texts.noChanges
            : estado === 'asistio'
              ? this.texts.successAttended
              : this.texts.successMissed,
        );
        this.actualizado.emit(response.data);
        this.cargar(data.turno.id);
      },
      error: (error: AsistenciaApiError) => {
        this.saving.set(false);
        this.error.set(error.message);

        if (error.status === 409) {
          this.cargar(data.turno.id);
        }
      },
    });
  }

  corregir(): void {
    if (this.data()?.can.corregir === true) {
      this.correccionOpen.set(true);
    }
  }

  onCorregido(resultado: AsistenciaResultado): void {
    this.correccionOpen.set(false);
    this.toast.success(this.texts.title, this.texts.successCorrected);
    this.actualizado.emit(resultado);

    const id = this.data()?.turno.id;

    if (id !== undefined) {
      this.cargar(id);
    }
  }

  abrirHistorial(): void {
    const id = this.data()?.turno.id;

    if (id !== undefined) {
      this.verHistorial.emit(id);
    }
  }

  private cargar(id: number): void {
    this.loading.set(true);
    this.error.set(null);

    this.service.getTurno(id).subscribe({
      next: (response) => {
        this.loading.set(false);
        this.data.set(response.data);
      },
      error: (error: AsistenciaApiError) => {
        this.loading.set(false);
        this.error.set(error.message);
      },
    });
  }
}
