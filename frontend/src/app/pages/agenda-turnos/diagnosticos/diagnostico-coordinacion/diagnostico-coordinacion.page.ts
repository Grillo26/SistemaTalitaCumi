import { Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute } from '@angular/router';
import { ToastService } from '../../../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../../../shared/components/ui/modal/modal.component';
import { SelectFieldComponent } from '../../../../shared/components/ui/select/select-field.component';
import { TextareaDirective } from '../../../../shared/components/ui/textarea/textarea.directive';
import { TABLE_IMPORTS } from '../../../../shared/components/ui/table';
import { AsistenciaTurnoAccionesComponent } from '../../asistencia-turno/components/asistencia-turno-acciones/asistencia-turno-acciones.component';
import { ReprogramarTurnoModalComponent } from '../../cambios-turnos/components/reprogramar-turno-modal/reprogramar-turno-modal.component';
import { ProgramarTurnoDiagnosticoModalComponent } from '../components/programar-turno-diagnostico-modal/programar-turno-diagnostico-modal.component';
import {
  ESTADO_GENERAL_COLOR,
  ESTADO_TURNO_COLOR,
  fechaLegible,
  horaLegible,
  type ActualizarEstadoPayload,
  type DiagnosticosApiError,
  type DiagnosticoDetalle,
  type DiagnosticosCan,
  type EstadoGeneralDiagnostico,
  type FaltanteDiagnostico,
  type PuestoDiagnostico,
  type TurnoDiagnostico,
} from '../diagnosticos.models';
import { DiagnosticosService } from '../diagnosticos.service';

@Component({
  selector: 'app-diagnostico-coordinacion-page',
  imports: [
    AlertComponent,
    AsistenciaTurnoAccionesComponent,
    BadgeComponent,
    ButtonComponent,
    ComponentCardComponent,
    ModalComponent,
    PageBreadcrumbComponent,
    ProgramarTurnoDiagnosticoModalComponent,
    ReprogramarTurnoModalComponent,
    SelectFieldComponent,
    TextareaDirective,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './diagnostico-coordinacion.page.html',
})
export class DiagnosticoCoordinacionPageComponent implements OnInit {
  private readonly route = inject(ActivatedRoute);
  private readonly service = inject(DiagnosticosService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly fechaLegible = fechaLegible;
  readonly horaLegible = horaLegible;
  readonly estadoGeneralColor = (estado: EstadoGeneralDiagnostico) =>
    ESTADO_GENERAL_COLOR[estado] ?? 'gray';
  readonly estadoTurnoColor = (estado: TurnoDiagnostico['estado']) =>
    ESTADO_TURNO_COLOR[estado] ?? 'gray';

  readonly id = signal<number | null>(null);
  readonly data = signal<DiagnosticoDetalle | null>(null);
  readonly can = signal<DiagnosticosCan | null>(null);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);
  readonly errorCode = signal<string | null>(null);
  readonly hoy = signal(this.hoyLocal());

  readonly programarOpen = signal(false);
  readonly programarEspecialidadId = signal<number | null>(null);

  readonly reprogramarOpen = signal(false);
  readonly reprogramarTurnoId = signal<number | null>(null);

  readonly asistenciaAbierta = signal<Set<number>>(new Set<number>());

  readonly cancelarOpen = signal(false);
  readonly cancelarMotivo = signal('');
  readonly cancelando = signal(false);
  readonly cancelarError = signal<string | null>(null);

  readonly estadoOpen = signal(false);
  readonly estadoSolicitado = signal<Exclude<EstadoGeneralDiagnostico, 'cancelado'>>('en_proceso');
  readonly estadoMotivo = signal('');
  readonly guardandoEstado = signal(false);
  readonly estadoError = signal<string | null>(null);
  readonly estadoFaltantes = signal<string[]>([]);

  readonly estadoOpciones = [
    { value: 'incompleto', label: 'Incompleto' },
    { value: 'en_proceso', label: 'En proceso' },
    { value: 'completo', label: 'Completo' },
  ];

  ngOnInit(): void {
    this.route.paramMap.pipe(takeUntilDestroyed(this.destroyRef)).subscribe((params) => {
      const id = Number(params.get('id') ?? 0);

      if (id > 0) {
        this.id.set(id);
        this.cargar();
      }
    });
  }

  cargar(): void {
    const id = this.id();

    if (id === null) {
      return;
    }

    this.loading.set(true);
    this.error.set(null);
    this.errorCode.set(null);

    this.service.detalle(id).subscribe({
      next: (response) => {
        this.loading.set(false);
        this.data.set(response.data);
        this.can.set(response.can);
      },
      error: (error: DiagnosticosApiError) => {
        this.loading.set(false);
        this.data.set(null);
        this.can.set(null);
        this.error.set(error.message);
        this.errorCode.set(error.code ?? null);
      },
    });
  }

  abrirProgramar(puesto: PuestoDiagnostico): void {
    this.programarEspecialidadId.set(puesto.especialidad_id);
    this.programarOpen.set(true);
  }

  abrirProgramarSinPuesto(): void {
    this.programarEspecialidadId.set(null);
    this.programarOpen.set(true);
  }

  onProgramado(): void {
    this.programarOpen.set(false);
    this.cargar();
  }

  abrirReprogramar(turno: TurnoDiagnostico): void {
    this.reprogramarTurnoId.set(turno.id);
    this.reprogramarOpen.set(true);
  }

  onReprogramado(): void {
    this.reprogramarOpen.set(false);
    this.cargar();
  }

  toggleAsistencia(turnoId: number): void {
    const abiertos = new Set(this.asistenciaAbierta());

    if (abiertos.has(turnoId)) {
      abiertos.delete(turnoId);
    } else {
      abiertos.add(turnoId);
    }

    this.asistenciaAbierta.set(abiertos);
  }

  estaAsistenciaAbierta(turnoId: number): boolean {
    return this.asistenciaAbierta().has(turnoId);
  }

  abrirCancelar(): void {
    this.cancelarMotivo.set('');
    this.cancelarError.set(null);
    this.cancelarOpen.set(true);
  }

  confirmarCancelar(): void {
    const id = this.id();

    if (id === null || this.cancelando()) {
      return;
    }

    if (this.cancelarMotivo().trim().length < 3) {
      this.cancelarError.set('Ingresá un motivo de al menos 3 caracteres.');
      return;
    }

    this.cancelando.set(true);
    this.cancelarError.set(null);

    this.service.cancelar(id, this.cancelarMotivo().trim()).subscribe({
      next: (response) => {
        this.cancelando.set(false);
        this.cancelarOpen.set(false);
        this.toast.success(
          'Diagnóstico cancelado',
          `${response.data.cantidad_cancelados} turno(s) futuro(s) cancelado(s); ${response.data.turnos_conservados} conservado(s).`,
        );
        this.cargar();
      },
      error: (error: DiagnosticosApiError) => {
        this.cancelando.set(false);
        this.cancelarError.set(error.message);
      },
    });
  }

  abrirEstado(): void {
    const data = this.data();

    if (data === null) {
      return;
    }

    const inicial = data.estado_general === 'cancelado' ? 'en_proceso' : data.estado_general;
    this.estadoSolicitado.set(inicial as Exclude<EstadoGeneralDiagnostico, 'cancelado'>);
    this.estadoMotivo.set('');
    this.estadoError.set(null);
    this.estadoFaltantes.set([]);
    this.estadoOpen.set(true);
  }

  guardarEstado(): void {
    const id = this.id();

    if (id === null || this.guardandoEstado()) {
      return;
    }

    if (this.estadoMotivo().trim().length < 3) {
      this.estadoError.set('Ingresá un motivo de al menos 3 caracteres.');
      return;
    }

    const payload: ActualizarEstadoPayload = {
      estado_general: this.estadoSolicitado(),
      motivo: this.estadoMotivo().trim(),
    };

    this.guardandoEstado.set(true);
    this.estadoError.set(null);
    this.estadoFaltantes.set([]);

    this.service.actualizarEstado(id, payload).subscribe({
      next: (response) => {
        this.guardandoEstado.set(false);
        this.estadoOpen.set(false);
        this.toast.success(
          'Estado actualizado',
          response.data.sin_cambios
            ? 'El diagnóstico ya tenía ese estado.'
            : `El diagnóstico quedó en «${response.data.estado_general_label}».`,
        );
        this.cargar();
      },
      error: (error: DiagnosticosApiError) => {
        this.guardandoEstado.set(false);
        this.estadoError.set(error.message);
        this.estadoFaltantes.set(
          (error.context?.faltantes ?? []).map((faltante: FaltanteDiagnostico) => faltante.mensaje),
        );
      },
    });
  }

  abrirWhatsapp(): void {
    const url = this.data()?.whatsapp?.whatsapp_url;

    if (url) {
      window.open(url, '_blank', 'noopener');
    }
  }

  private hoyLocal(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
  }
}
