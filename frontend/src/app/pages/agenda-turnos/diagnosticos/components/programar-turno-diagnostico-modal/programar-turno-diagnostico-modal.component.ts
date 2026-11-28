import { Component, computed, effect, inject, input, output, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ToastService } from '../../../../../core/services/toast.service';
import { AlertComponent } from '../../../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../../../../shared/components/ui/modal/modal.component';
import { SelectFieldComponent } from '../../../../../shared/components/ui/select/select-field.component';
import { InscripcionDateInputComponent } from '../../../../inscripciones-atenciones/salas-grupales/inscripcion-date-input.component';
import type {
  DiagnosticosApiError,
  ProfesionalDiagnostico,
  ProgramarTurnoResponse,
} from '../../diagnosticos.models';
import { DiagnosticosService } from '../../diagnosticos.service';

@Component({
  selector: 'app-programar-turno-diagnostico-modal',
  imports: [
    AlertComponent,
    ButtonComponent,
    FormsModule,
    InputDirective,
    InscripcionDateInputComponent,
    ModalComponent,
    SelectFieldComponent,
  ],
  templateUrl: './programar-turno-diagnostico-modal.component.html',
})
export class ProgramarTurnoDiagnosticoModalComponent {
  private readonly service = inject(DiagnosticosService);
  private readonly toast = inject(ToastService);

  readonly open = input(false);
  readonly diagnosticoId = input<number | null>(null);
  readonly profesionales = input<ProfesionalDiagnostico[]>([]);
  readonly especialidadId = input<number | null>(null);
  readonly fechaInicio = input<string>('');
  readonly fechaFin = input<string>('');
  readonly hoy = input<string>('');

  readonly closed = output<void>();
  readonly completed = output<ProgramarTurnoResponse>();

  readonly profesionalId = signal('');
  readonly fecha = signal('');
  readonly horaInicio = signal('09:00');
  readonly duracionMinutos = signal('');
  readonly saving = signal(false);
  readonly error = signal<string | null>(null);
  readonly fieldErrors = signal<Record<string, string[]>>({});

  readonly profesionalesDisponibles = computed(() => {
    const especialidadId = this.especialidadId();

    return this.profesionales().filter((profesional) => {
      if (!profesional.disponible_para_programar || !profesional.activo_en_sucursal) {
        return false;
      }

      if (especialidadId === null) {
        return true;
      }

      return profesional.especialidades.some((especialidad) => especialidad.id === especialidadId);
    });
  });
  readonly profesionalOpciones = computed(() =>
    this.profesionalesDisponibles().map((profesional) => ({
      value: String(profesional.id),
      label: profesional.nombre_completo,
    })),
  );
  readonly minFecha = computed(() => {
    const hoy = this.hoy();
    const inicio = this.fechaInicio();

    if (hoy === '') {
      return inicio;
    }

    if (inicio === '') {
      return hoy;
    }

    return hoy > inicio ? hoy : inicio;
  });

  constructor() {
    effect(() => {
      if (!this.open()) {
        return;
      }

      this.error.set(null);
      this.fieldErrors.set({});
      this.saving.set(false);
      this.duracionMinutos.set('');

      const disponibles = this.profesionalesDisponibles();
      this.profesionalId.set(disponibles.length === 1 ? String(disponibles[0].id) : '');

      if (this.fecha() === '') {
        this.fecha.set(this.minFecha());
      }
    });
  }

  cerrar(): void {
    if (this.saving()) {
      return;
    }

    this.closed.emit();
  }

  guardar(): void {
    const diagnosticoId = this.diagnosticoId();

    if (diagnosticoId === null || this.saving()) {
      return;
    }

    if (this.profesionalId() === '' || this.fecha() === '' || this.horaInicio() === '') {
      this.error.set('Seleccioná profesional, fecha y hora.');
      return;
    }

    this.saving.set(true);
    this.error.set(null);
    this.fieldErrors.set({});

    this.service
      .programar(diagnosticoId, {
        profesional_id: Number(this.profesionalId()),
        fecha: this.fecha(),
        hora_inicio: this.horaInicio(),
        ...(this.duracionMinutos() === ''
          ? {}
          : { duracion_minutos: Number(this.duracionMinutos()) }),
      })
      .subscribe({
        next: (response) => {
          this.saving.set(false);
          this.toast.success('Turno programado', 'El turno del profesional quedó registrado.');
          this.completed.emit(response);
          this.closed.emit();
        },
        error: (error: DiagnosticosApiError) => {
          this.saving.set(false);
          this.error.set(error.message);
          this.fieldErrors.set(error.errors ?? {});
        },
      });
  }

  errorDe(campo: string): string | null {
    return this.fieldErrors()[campo]?.[0] ?? null;
  }
}
