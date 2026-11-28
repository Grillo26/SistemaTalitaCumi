import { forkJoin } from 'rxjs';
import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { AuthService } from '../../../../../core/services/auth.service';
import { ToastService } from '../../../../../core/services/toast.service';
import { AlertComponent } from '../../../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../../../shared/components/ui/input/input.directive';
import { PaginationComponent } from '../../../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../../../shared/components/ui/select/select-field.component';
import { CatalogoServiciosService } from '../../../../catalogo-servicios/servicios/servicios.service';
import type { Servicio } from '../../../../catalogo-servicios/servicios/servicios.models';
import { InscripcionPacienteSearchComponent } from '../../../../inscripciones-atenciones/salas-grupales/inscripcion-paciente-search.component';
import { Paciente } from '../../../../pacientes/pacientes.models';
import {
  CAMBIOS_TEXTS,
  fechaHoraLegible,
  fechaLegible,
  type CambiosApiError,
  type MotivoTurno,
  type ReporteCambiosData,
} from '../../cambios-turnos.models';
import { CambiosTurnosService } from '../../cambios-turnos.service';

@Component({
  selector: 'app-reporte-cambios',
  imports: [
    AlertComponent,
    ButtonComponent,
    InputDirective,
    PaginationComponent,
    SelectFieldComponent,
    InscripcionPacienteSearchComponent,
  ],
  templateUrl: './reporte-cambios.component.html',
})
export class ReporteCambiosComponent implements OnInit {
  private readonly service = inject(CambiosTurnosService);
  private readonly auth = inject(AuthService);
  private readonly catalogo = inject(CatalogoServiciosService);
  private readonly toast = inject(ToastService);

  readonly texts = CAMBIOS_TEXTS;
  readonly fechaLegible = fechaLegible;
  readonly fechaHoraLegible = fechaHoraLegible;

  readonly desde = signal('');
  readonly hasta = signal('');
  readonly sucursalId = signal('');
  readonly tipoCambio = signal('');
  readonly motivoId = signal('');
  readonly servicioId = signal('');
  readonly paciente = signal<Paciente | null>(null);
  readonly motivos = signal<MotivoTurno[]>([]);
  readonly servicios = signal<Servicio[]>([]);
  readonly reporte = signal<ReporteCambiosData | null>(null);
  readonly loading = signal(false);
  readonly exporting = signal<'pdf' | 'excel' | null>(null);
  readonly error = signal<string | null>(null);

  readonly sucursalOpciones = computed(() => [
    { value: '', label: this.texts.allBranches },
    ...(this.auth.user()?.branches ?? []).map((branch) => ({
      value: String(branch.id),
      label: branch.nombre,
    })),
  ]);
  readonly tipoOpciones = [
    { value: '', label: 'Todos' },
    { value: 'reprogramado', label: this.texts.reprogrammed },
    { value: 'cancelado', label: this.texts.cancelled },
  ];
  readonly motivoOpciones = computed(() => [
    { value: '', label: 'Todos' },
    ...this.motivos().map((motivo) => ({ value: String(motivo.id), label: motivo.nombre })),
  ]);
  readonly servicioOpciones = computed(() => [
    { value: '', label: 'Todos' },
    ...this.servicios().map((servicio) => ({ value: String(servicio.id), label: servicio.nombre })),
  ]);

  ngOnInit(): void {
    this.cargarCatalogos();
    this.cargar(1);
  }

  private cargarCatalogos(): void {
    forkJoin({
      cancelar: this.service.getMotivos('cancelar'),
      reprogramar: this.service.getMotivos('reprogramar'),
    }).subscribe({
      next: ({ cancelar, reprogramar }) => {
        this.motivos.set(this.mergeMotivos(cancelar.data, reprogramar.data));
      },
      error: () => this.motivos.set([]),
    });

    this.catalogo.listServicios({ per_page: 100, estado: 'activo' }).subscribe({
      next: (response) => this.servicios.set(response.data),
      error: () => this.servicios.set([]),
    });
  }

  onPaciente(paciente: Paciente | null): void {
    this.paciente.set(paciente);
  }

  filtrar(): void {
    this.cargar(1);
  }

  limpiar(): void {
    this.desde.set('');
    this.hasta.set('');
    this.sucursalId.set('');
    this.tipoCambio.set('');
    this.motivoId.set('');
    this.servicioId.set('');
    this.paciente.set(null);
    this.cargar(1);
  }

  cambiarPagina(page: number): void {
    this.cargar(page);
  }

  cargar(page: number): void {
    this.loading.set(true);
    this.error.set(null);

    this.service.getReporte(this.params(page)).subscribe({
      next: (response) => {
        this.loading.set(false);
        this.reporte.set(response);
      },
      error: (error: CambiosApiError) => {
        this.loading.set(false);
        this.error.set(error.message);
      },
    });
  }

  exportar(formato: 'pdf' | 'excel'): void {
    if (this.exporting() !== null) {
      return;
    }

    this.exporting.set(formato);
    this.error.set(null);

    this.service.exportar(formato, this.params(this.reporte()?.meta.current_page ?? 1)).subscribe({
      next: (blob) => {
        this.exporting.set(null);
        const url = URL.createObjectURL(blob);
        const enlace = document.createElement('a');
        enlace.href = url;
        enlace.download = `cambios-turnos.${formato === 'pdf' ? 'pdf' : 'xlsx'}`;
        enlace.click();
        URL.revokeObjectURL(url);
      },
      error: (error: CambiosApiError) => {
        this.exporting.set(null);
        this.error.set(error.message);
        this.toast.error(this.texts.exportUnavailable, error.message);
      },
    });
  }

  private params(page: number): Record<string, string | number | null> {
    return {
      page,
      per_page: 20,
      periodo_desde: this.desde() || null,
      periodo_hasta: this.hasta() || null,
      sucursal_id: this.sucursalId() || null,
      tipo_cambio: this.tipoCambio() || null,
      motivo_id: this.motivoId() || null,
      servicio_id: this.servicioId() || null,
      paciente_id: this.paciente()?.id ?? null,
    };
  }

  private mergeMotivos(actuales: MotivoTurno[], nuevos: MotivoTurno[]): MotivoTurno[] {
    const mapa = new Map<number, MotivoTurno>();

    for (const motivo of [...actuales, ...nuevos]) {
      mapa.set(motivo.id, motivo);
    }

    return [...mapa.values()].sort((a, b) => a.nombre.localeCompare(b.nombre));
  }
}
