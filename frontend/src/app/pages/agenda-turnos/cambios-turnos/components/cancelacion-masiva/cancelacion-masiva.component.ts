import { Component, computed, inject, signal } from '@angular/core';
import { AgendaRefreshService } from '../../../../../core/services/agenda-refresh.service';
import { ToastService } from '../../../../../core/services/toast.service';
import { AlertComponent } from '../../../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { SelectFieldComponent } from '../../../../../shared/components/ui/select/select-field.component';
import { TextareaDirective } from '../../../../../shared/components/ui/textarea/textarea.directive';
import { InscripcionPacienteSearchComponent } from '../../../../inscripciones-atenciones/salas-grupales/inscripcion-paciente-search.component';
import { Paciente } from '../../../../pacientes/pacientes.models';
import {
  CAMBIOS_TEXTS,
  ESTADO_CAMBIO_LABEL,
  fechaLegible,
  type CambiosApiError,
  type CancelacionMasivaResultado,
  type MotivoTurno,
  type OrigenCancelable,
  type PreviewCancelacionMasiva,
} from '../../cambios-turnos.models';
import { CambiosTurnosService } from '../../cambios-turnos.service';

@Component({
  selector: 'app-cancelacion-masiva',
  imports: [
    AlertComponent,
    ButtonComponent,
    SelectFieldComponent,
    TextareaDirective,
    InscripcionPacienteSearchComponent,
  ],
  templateUrl: './cancelacion-masiva.component.html',
})
export class CancelacionMasivaComponent {
  private readonly service = inject(CambiosTurnosService);
  private readonly toast = inject(ToastService);
  private readonly refresh = inject(AgendaRefreshService);

  readonly texts = CAMBIOS_TEXTS;
  readonly fechaLegible = fechaLegible;
  readonly estadoLabel = (estado: string): string => ESTADO_CAMBIO_LABEL[estado] ?? estado;

  readonly paciente = signal<Paciente | null>(null);
  readonly origenes = signal<OrigenCancelable[]>([]);
  readonly selectedKeys = signal<string[]>([]);
  readonly servicioFiltro = signal('');
  readonly motivos = signal<MotivoTurno[]>([]);
  readonly motivoId = signal('');
  readonly motivoTexto = signal('');
  readonly loading = signal(false);
  readonly previewLoading = signal(false);
  readonly saving = signal(false);
  readonly error = signal<string | null>(null);
  readonly preview = signal<PreviewCancelacionMasiva | null>(null);
  readonly resultado = signal<CancelacionMasivaResultado | null>(null);
  readonly confirmar = signal(false);

  readonly servicios = computed(() => {
    const vistos = new Map<number, string>();

    for (const origen of this.origenes()) {
      vistos.set(origen.servicio.id, origen.servicio.nombre);
    }

    return [
      { value: '', label: 'Todos los servicios' },
      ...[...vistos.entries()].map(([id, nombre]) => ({ value: String(id), label: nombre })),
    ];
  });
  readonly origenesVisibles = computed(() => {
    const servicio = this.servicioFiltro();

    return servicio === ''
      ? this.origenes()
      : this.origenes().filter((origen) => String(origen.servicio.id) === servicio);
  });
  readonly motivoSeleccionado = computed(
    () => this.motivos().find((motivo) => String(motivo.id) === this.motivoId()) ?? null,
  );
  readonly requiereTexto = computed(() => this.motivoSeleccionado()?.requiere_texto === true);
  readonly motivoOpciones = computed(() =>
    this.motivos().map((motivo) => ({ value: String(motivo.id), label: motivo.nombre })),
  );
  readonly puedeConfirmar = computed(() => {
    const preview = this.preview();

    return (
      preview !== null &&
      preview.puede_confirmar &&
      preview.elegibles.length > 0 &&
      this.confirmar() &&
      !this.saving()
    );
  });
  readonly totalSeleccionados = computed(() => this.selectedKeys().length);

  constructor() {
    this.cargarMotivos();
  }

  onPaciente(paciente: Paciente | null): void {
    this.paciente.set(paciente);
    this.origenes.set([]);
    this.selectedKeys.set([]);
    this.servicioFiltro.set('');
    this.preview.set(null);
    this.resultado.set(null);
    this.error.set(null);

    if (paciente !== null) {
      this.cargarOrigenes(paciente.id);
    }
  }

  private cargarOrigenes(pacienteId: number): void {
    this.loading.set(true);

    this.service.getOrigenesCancelables(pacienteId).subscribe({
      next: (response) => {
        this.loading.set(false);
        this.origenes.set(response.data);
      },
      error: (error: CambiosApiError) => {
        this.loading.set(false);
        this.error.set(error.message);
      },
    });
  }

  private cargarMotivos(): void {
    this.service.getMotivos('cancelar').subscribe({
      next: (response) => {
        this.motivos.set(response.data);
        this.motivoId.set(response.data[0] ? String(response.data[0].id) : '');
      },
      error: () => this.motivos.set([]),
    });
  }

  isSelected(origen: OrigenCancelable): boolean {
    return this.selectedKeys().includes(this.key(origen));
  }

  toggle(origen: OrigenCancelable): void {
    const key = this.key(origen);
    const actuales = this.selectedKeys();

    this.selectedKeys.set(
      actuales.includes(key)
        ? actuales.filter((item) => item !== key)
        : [...actuales, key],
    );
    this.preview.set(null);
    this.confirmar.set(false);
  }

  cambiarServicio(value: string): void {
    this.servicioFiltro.set(value);
    this.selectedKeys.set([]);
    this.preview.set(null);
    this.confirmar.set(false);
  }

  previsualizar(): void {
    const paciente = this.paciente();

    if (paciente === null || this.previewLoading()) {
      return;
    }

    if (this.selectedKeys().length === 0) {
      this.error.set(this.texts.selectOrigins);
      return;
    }

    if (this.motivoId() === '') {
      this.error.set(this.texts.reasonRequired);
      return;
    }

    if (this.requiereTexto() && this.motivoTexto().trim() === '') {
      this.error.set(this.texts.otherRequired);
      return;
    }

    this.previewLoading.set(true);
    this.error.set(null);

    this.service.previewMasiva(this.payload()).subscribe({
      next: (response) => {
        this.previewLoading.set(false);
        this.preview.set(response.data);
        this.confirmar.set(false);
      },
      error: (error: CambiosApiError) => {
        this.previewLoading.set(false);
        this.error.set(error.message);
      },
    });
  }

  ejecutar(): void {
    const preview = this.preview();

    if (preview === null || !this.puedeConfirmar()) {
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    this.service.cancelarMasivo({
      ...this.payload(),
      turno_ids: preview.elegibles.map((turno) => turno.id),
      confirmar: true,
    }).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.resultado.set(response.data);
        this.toast.success(this.texts.successMass, `${response.data.cantidad} turnos`);
        this.refresh.notificarCambio();
        this.preview.set(null);
        this.confirmar.set(false);
        this.selectedKeys.set([]);
        this.cargarOrigenes(preview.paciente_id);
      },
      error: (error: CambiosApiError) => {
        this.saving.set(false);
        this.error.set(error.message);
      },
    });
  }

  private payload(): Record<string, unknown> {
    return {
      paciente_id: this.paciente()?.id,
      entidades: this.selectedKeys().map((key) => {
        const [tipo, id] = key.split(':');

        return { tipo, id: Number(id) };
      }),
      servicio_id: this.servicioFiltro() === '' ? null : Number(this.servicioFiltro()),
      motivo_id: Number(this.motivoId()),
      motivo_texto: this.motivoTexto().trim() === '' ? null : this.motivoTexto().trim(),
    };
  }

  private key(origen: OrigenCancelable): string {
    return `${origen.tipo}:${origen.id}`;
  }
}
