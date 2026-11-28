import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { AuthService } from '../../../../../core/services/auth.service';
import { ToastService } from '../../../../../core/services/toast.service';
import { AlertComponent } from '../../../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../../../shared/components/ui/input/input.directive';
import { PaginationComponent } from '../../../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../../../shared/components/ui/select/select-field.component';
import { AgendaSemanalService } from '../../../agenda-semanal/agenda-semanal.service';
import type { OpcionesFiltrosData } from '../../../agenda-semanal/agenda-semanal.models';
import {
  ASISTENCIA_TEXTS,
  porcentajeAsistencia,
  type AsistenciaApiError,
  type AsistenciaIndicadoresData,
} from '../../../asistencia-turno/asistencia-turno.models';
import { AsistenciaTurnoService } from '../../../asistencia-turno/asistencia-turno.service';

@Component({
  selector: 'app-indicadores-asistencia',
  imports: [
    AlertComponent,
    ButtonComponent,
    InputDirective,
    PaginationComponent,
    SelectFieldComponent,
  ],
  templateUrl: './indicadores-asistencia.component.html',
})
export class IndicadoresAsistenciaComponent implements OnInit {
  private readonly service = inject(AsistenciaTurnoService);
  private readonly agendaService = inject(AgendaSemanalService);
  private readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);

  readonly texts = ASISTENCIA_TEXTS;
  readonly porcentaje = porcentajeAsistencia;

  readonly desde = signal(this.monthStart());
  readonly hasta = signal(this.monthEnd());
  readonly sucursalId = signal('');
  readonly agrupacion = signal('paciente');
  readonly salaId = signal('');
  readonly servicioId = signal('');
  readonly profesionalId = signal('');
  readonly opciones = signal<OpcionesFiltrosData | null>(null);
  readonly data = signal<AsistenciaIndicadoresData | null>(null);
  readonly loading = signal(false);
  readonly exporting = signal(false);
  readonly error = signal<string | null>(null);

  readonly sucursalOpciones = computed(() =>
    (this.auth.user()?.branches ?? []).map((branch) => ({
      value: String(branch.id),
      label: branch.nombre,
    })),
  );
  readonly canExportar = computed(() => this.auth.hasPermission('asistencia.exportar'));
  readonly salaOpciones = computed(() => [
    { value: '', label: 'Todas las salas' },
    ...(this.opciones()?.salas ?? []).map((sala) => ({ value: String(sala.id), label: sala.nombre })),
  ]);
  readonly servicioOpciones = computed(() => [
    { value: '', label: 'Todos los servicios' },
    ...(this.opciones()?.servicios ?? []).map((servicio) => ({
      value: String(servicio.id),
      label: servicio.nombre,
    })),
  ]);
  readonly profesionalOpciones = computed(() => [
    { value: '', label: 'Todos los profesionales' },
    ...(this.opciones()?.profesionales ?? []).map((profesional) => ({
      value: String(profesional.id),
      label: profesional.nombre_completo,
    })),
  ]);
  readonly agrupacionOpciones = [
    { value: 'paciente', label: 'Paciente' },
    { value: 'sala', label: 'Sala' },
    { value: 'servicio', label: 'Servicio' },
    { value: 'profesional', label: 'Profesional' },
    { value: 'sucursal', label: 'Sucursal' },
    { value: 'dia', label: 'Día' },
  ];

  ngOnInit(): void {
    const first = this.auth.user()?.branches?.[0];

    if (first !== undefined) {
      this.sucursalId.set(String(first.id));
      this.cargarOpciones();
      this.cargar(1);
    }
  }

  cambiarSucursal(value: string): void {
    this.sucursalId.set(value);
    this.salaId.set('');
    this.servicioId.set('');
    this.profesionalId.set('');
    this.opciones.set(null);
    this.cargarOpciones();
    this.cargar(1);
  }

  private cargarOpciones(): void {
    if (this.sucursalId() === '') {
      return;
    }

    this.agendaService.getOpciones(Number(this.sucursalId())).subscribe({
      next: (response) => this.opciones.set(response.data),
      error: () => this.opciones.set(null),
    });
  }

  cargar(page: number): void {
    if (this.sucursalId() === '') {
      this.error.set('Seleccioná una sucursal.');
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    this.service.getIndicadores(this.params(page)).subscribe({
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

  exportar(): void {
    if (this.exporting() || !this.puedeExportar() || !this.canExportar()) {
      return;
    }

    this.exporting.set(true);
    this.error.set(null);

    this.service.exportarCsv(this.params(1)).subscribe({
      next: (blob) => {
        this.exporting.set(false);
        const url = URL.createObjectURL(blob);
        const enlace = document.createElement('a');
        enlace.href = url;
        enlace.download = `asistencia-${this.desde()}-${this.hasta()}.csv`;
        enlace.click();
        URL.revokeObjectURL(url);
      },
      error: (error: AsistenciaApiError) => {
        this.exporting.set(false);
        this.error.set(error.message);
        this.toast.error('No se pudo exportar', error.message);
      },
    });
  }

  puedeExportar(): boolean {
    return this.agrupacion() !== 'paciente';
  }

  private params(page: number): Record<string, string | number | null> {
    return {
      sucursal_id: Number(this.sucursalId()),
      periodo_desde: this.desde(),
      periodo_hasta: this.hasta(),
      agrupacion: this.agrupacion(),
      sala_id: this.salaId() === '' ? null : Number(this.salaId()),
      servicio_id: this.servicioId() === '' ? null : Number(this.servicioId()),
      profesional_id: this.profesionalId() === '' ? null : Number(this.profesionalId()),
      page,
      per_page: 25,
    };
  }

  private monthStart(): string {
    const now = new Date();

    return this.format(new Date(now.getFullYear(), now.getMonth(), 1));
  }

  private monthEnd(): string {
    const now = new Date();

    return this.format(new Date(now.getFullYear(), now.getMonth() + 1, 0));
  }

  private format(date: Date): string {
    const mes = String(date.getMonth() + 1).padStart(2, '0');
    const dia = String(date.getDate()).padStart(2, '0');

    return `${date.getFullYear()}-${mes}-${dia}`;
  }
}
