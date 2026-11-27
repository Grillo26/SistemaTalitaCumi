import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute } from '@angular/router';
import { forkJoin } from 'rxjs';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { GenerarTurnosWizardComponent } from './components/generar-turnos-wizard/generar-turnos-wizard.component';
import {
  PROGRAMACION_TEXTS,
  type Feriado,
  type LoteResultado,
  type ModoProgramacion,
  type OrigenProgramacionData,
  type PermisosProgramacion,
  type ProgramacionApiError,
  type TipoOrigenProgramacion,
  type TurnoOrigen,
} from './programacion-turnos.models';
import { ProgramacionTurnosService } from './programacion-turnos.service';

type Pestana = 'programacion' | 'feriados';

@Component({
  selector: 'app-programacion-turnos-page',
  imports: [
    PageBreadcrumbComponent,
    ComponentCardComponent,
    AlertComponent,
    ButtonComponent,
    InputDirective,
    SelectFieldComponent,
    GenerarTurnosWizardComponent,
  ],
  templateUrl: './programacion-turnos.page.html',
})
export class ProgramacionTurnosPageComponent implements OnInit {
  private readonly service = inject(ProgramacionTurnosService);
  private readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);
  private readonly route = inject(ActivatedRoute);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = PROGRAMACION_TEXTS;

  readonly tipoOpciones = [
    { value: 'paquete_sesiones', label: 'Paquete de sesiones' },
    { value: 'inscripcion_sala', label: 'Inscripción a sala' },
  ];
  readonly tipo = signal<TipoOrigenProgramacion>('paquete_sesiones');
  readonly idTexto = signal('');
  readonly origenData = signal<OrigenProgramacionData | null>(null);
  readonly turnos = signal<TurnoOrigen[]>([]);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  readonly pestana = signal<Pestana>('programacion');
  readonly wizardOpen = signal(false);
  readonly wizardModo = signal<ModoProgramacion>('generar');
  readonly wizardTipo = signal<TipoOrigenProgramacion>('paquete_sesiones');
  readonly wizardId = signal<number | null>(null);

  readonly feriados = signal<Feriado[]>([]);
  readonly feriadosLoading = signal(false);
  readonly feriadosError = signal<string | null>(null);
  readonly anio = signal<number | null>(null);
  readonly sucursalFiltro = signal('');
  readonly feriadoFecha = signal('');
  readonly feriadoDescripcion = signal('');
  readonly feriadoSucursal = signal('global');
  readonly savingFeriado = signal(false);

  readonly can = computed<PermisosProgramacion>(() => ({
    ver: this.auth.hasPermission('turno.ver'),
    generar: this.auth.hasPermission('turno.generar'),
    regenerar: this.auth.hasPermission('turno.regenerar'),
    asignar_profesional: this.auth.hasPermission('turno.asignar_profesional'),
    feriados: this.auth.hasPermission('turno.feriados'),
  }));

  readonly puedeGenerar = computed(
    () => (this.origenData()?.puede_generar ?? false) && this.can().generar,
  );
  readonly puedeRegenerar = computed(
    () => (this.origenData()?.puede_regenerar ?? false) && this.can().regenerar,
  );
  readonly origen = computed(() => this.origenData()?.origen ?? null);

  readonly sucursalOpciones = computed(() => [
    { value: 'global', label: this.texts.global },
    ...(this.auth.user()?.branches ?? []).map((branch) => ({
      value: String(branch.id),
      label: branch.nombre,
    })),
  ]);

  readonly sucursalFiltroOpciones = computed(() => [
    { value: '', label: 'Todas' },
    ...(this.auth.user()?.branches ?? []).map((branch) => ({
      value: String(branch.id),
      label: branch.nombre,
    })),
  ]);

  ngOnInit(): void {
    if (this.can().feriados) {
      this.cargarFeriados();
    }

    const params = this.route.snapshot.queryParamMap;
    const tipo = params.get('tipo');
    const id = Number(params.get('id'));
    const accion = params.get('accion');

    if (tipo === 'inscripcion_sala' || tipo === 'paquete_sesiones') {
      this.tipo.set(tipo);
    }

    if (Number.isInteger(id) && id > 0) {
      this.idTexto.set(String(id));
      this.cargarOrigen(true, accion);
    }
  }

  cambiarTipo(tipo: string): void {
    this.tipo.set(tipo === 'inscripcion_sala' ? 'inscripcion_sala' : 'paquete_sesiones');
  }

  setAnio(value: string): void {
    const anio = Number(value);

    this.anio.set(Number.isInteger(anio) && anio > 0 ? anio : null);
  }

  cambiarPestana(pestana: Pestana): void {
    if (pestana === 'feriados' && !this.can().feriados) {
      return;
    }

    this.pestana.set(pestana);

    if (pestana === 'feriados' && this.feriados().length === 0) {
      this.cargarFeriados();
    }
  }

  cargarOrigen(automatico = false, accion: string | null = null): void {
    const id = Number(this.idTexto());

    if (!Number.isInteger(id) || id <= 0) {
      this.error.set(this.texts.requiredId);
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    forkJoin({
      origen: this.service.getOrigen(this.tipo(), id),
      turnos: this.service.getTurnos(this.tipo(), id),
    })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: ({ origen, turnos }) => {
          this.loading.set(false);
          this.origenData.set(origen.data);
          this.turnos.set(turnos.data.turnos);

          if (automatico && accion !== null) {
            this.abrirSegunAccion(origen.data, accion);
          }
        },
        error: (error: ProgramacionApiError) => {
          this.loading.set(false);
          this.origenData.set(null);
          this.turnos.set([]);
          this.error.set(this.mensajeError(error));
        },
      });
  }

  private abrirSegunAccion(origen: OrigenProgramacionData, accion: string): void {
    const quiereRegenerar =
      accion === 'regenerar' || (accion === 'generar' && !origen.puede_generar);

    if (quiereRegenerar && origen.puede_regenerar && this.can().regenerar) {
      this.abrirWizard('regenerar');
      return;
    }

    if (!quiereRegenerar && origen.puede_generar && this.can().generar) {
      this.abrirWizard('generar');
      return;
    }

    if (origen.puede_regenerar && !this.can().regenerar) {
      this.error.set(this.texts.noPermission);
    }
  }

  abrirWizard(modo: ModoProgramacion): void {
    const origen = this.origen();

    if (origen === null) {
      return;
    }

    if (modo === 'generar' && !this.can().generar) {
      this.error.set(this.texts.noPermission);
      return;
    }

    if (modo === 'regenerar' && !this.can().regenerar) {
      this.error.set(this.texts.noPermission);
      return;
    }

    this.wizardTipo.set(origen.tipo);
    this.wizardId.set(origen.id);
    this.wizardModo.set(modo);
    this.wizardOpen.set(true);
  }

  cerrarWizard(): void {
    this.wizardOpen.set(false);
    this.recargarOrigen();
  }

  onWizardCompleted(_resultado: LoteResultado): void {
    // La recarga se hace al cerrar el wizard para no competir con el resumen.
  }

  private recargarOrigen(): void {
    const origen = this.origen();

    if (origen === null) {
      return;
    }

    this.service.getOrigen(origen.tipo, origen.id).subscribe({
      next: (response) => this.origenData.set(response.data),
      error: () => undefined,
    });
    this.service.getTurnos(origen.tipo, origen.id).subscribe({
      next: (response) => this.turnos.set(response.data.turnos),
      error: () => undefined,
    });
  }

  cargarFeriados(): void {
    this.feriadosLoading.set(true);
    this.feriadosError.set(null);

    this.service
      .getFeriados({
        anio: this.anio(),
        sucursal_id: this.sucursalFiltro() === '' ? null : Number(this.sucursalFiltro()),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.feriadosLoading.set(false);
          this.feriados.set(response.data);
        },
        error: (error: ProgramacionApiError) => {
          this.feriadosLoading.set(false);
          this.feriadosError.set(this.mensajeError(error));
        },
      });
  }

  filtrarFeriados(): void {
    this.cargarFeriados();
  }

  crearFeriado(): void {
    const fecha = this.feriadoFecha();
    const descripcion = this.feriadoDescripcion().trim();

    if (fecha === '' || descripcion === '') {
      this.toast.error('Datos incompletos', 'Ingresá la fecha y la descripción del feriado.');
      return;
    }

    this.savingFeriado.set(true);

    this.service
      .crearFeriado({
        fecha,
        descripcion,
        sucursal_id:
          this.feriadoSucursal() === 'global' ? null : Number(this.feriadoSucursal()),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.savingFeriado.set(false);
          this.feriadoFecha.set('');
          this.feriadoDescripcion.set('');
          this.toast.success(this.texts.successHoliday, this.texts.holidayTitle);
          this.cargarFeriados();
        },
        error: (error: ProgramacionApiError) => {
          this.savingFeriado.set(false);
          this.toast.error(this.texts.saveError, this.mensajeError(error));
        },
      });
  }

  desactivarFeriado(feriado: Feriado): void {
    this.service
      .desactivarFeriado(feriado.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.toast.success(this.texts.successDeactivated, feriado.descripcion);
          this.cargarFeriados();
        },
        error: (error: ProgramacionApiError) => {
          this.toast.error(this.texts.saveError, this.mensajeError(error));
        },
      });
  }

  fechaLegible(fecha: string): string {
    const [anio, mes, dia] = fecha.split('-');

    return `${dia}/${mes}/${anio}`;
  }

  estadoTurnoClase(estado: string): string {
    switch (estado) {
      case 'programado':
        return 'bg-brand-50 text-brand-700 dark:bg-brand-500/10 dark:text-brand-400';
      case 'asistio':
        return 'bg-success-50 text-success-700 dark:bg-success-500/10 dark:text-success-400';
      case 'falto':
        return 'bg-warning-50 text-warning-700 dark:bg-warning-500/10 dark:text-warning-400';
      case 'cancelado':
        return 'bg-error-50 text-error-700 dark:bg-error-500/10 dark:text-error-400';
      default:
        return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300';
    }
  }

  private mensajeError(error: ProgramacionApiError): string {
    switch (error.code) {
      case 'BRANCH_ACCESS_DENIED':
        return this.texts.noBranch;
      case 'ORIGEN_NO_ENCONTRADO':
        return 'No se encontró la entidad origen indicada.';
      case 'PATRON_NO_DISPONIBLE':
        return this.texts.patternUnavailable;
      default:
        return error.message || this.texts.loadError;
    }
  }
}
