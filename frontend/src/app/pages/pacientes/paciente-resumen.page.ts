import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastService } from '../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { EMPTY_PACIENTE_ABILITIES, PACIENTES_TEXTS, PacientePageAbilities, PacientesApiError } from './pacientes.models';
import { PacientesService } from './pacientes.service';
import {
  PACIENTE_RESUMEN_TEXTS,
  PacienteResumenData,
  ResumenBloque,
} from './paciente-resumen.models';

@Component({
  selector: 'app-paciente-resumen-page',
  imports: [
    PageBreadcrumbComponent,
    ComponentCardComponent,
    AlertComponent,
    BadgeComponent,
    ButtonComponent,
  ],
  templateUrl: './paciente-resumen.page.html',
})
export class PacienteResumenPageComponent implements OnInit {
  private readonly service = inject(PacientesService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = PACIENTE_RESUMEN_TEXTS;
  readonly pacientesTexts = PACIENTES_TEXTS;
  readonly resumen = signal<PacienteResumenData | null>(null);
  readonly can = signal<PacientePageAbilities>(EMPTY_PACIENTE_ABILITIES);
  readonly loading = signal(true);
  readonly refreshing = signal(false);
  readonly error = signal<PacientesApiError | null>(null);
  readonly notFound = signal(false);
  readonly sucursalId = signal<number | null>(null);

  readonly contextoLabel = computed(() => {
    const resumen = this.resumen();
    const id = resumen?.contexto_sucursal;

    if (id === null || id === undefined) {
      return this.texts.allBranches;
    }

    if (resumen?.paciente.sucursal_registro.id === id) {
      return resumen.paciente.sucursal_registro.nombre
        ?? `Sucursal ${id}`;
    }

    return `Sucursal ${id}`;
  });

  private pacienteId = 0;

  ngOnInit(): void {
    this.pacienteId = Number(this.route.snapshot.paramMap.get('id'));
    const querySucursal = Number(this.route.snapshot.queryParamMap.get('sucursal_id'));

    this.sucursalId.set(Number.isFinite(querySucursal) && querySucursal > 0 ? querySucursal : null);
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.error.set(null);
    this.notFound.set(false);

    this.service
      .getPacienteResumen(this.pacienteId, this.sucursalId())
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.resumen.set(response.data);
          this.can.set(response.can);
          this.loading.set(false);
        },
        error: (apiError: PacientesApiError) => {
          this.loading.set(false);

          if (apiError.status === 404) {
            this.notFound.set(true);
            return;
          }

          if (apiError.status === 503 && apiError.code === 'AUDIT_UNAVAILABLE') {
            this.error.set({ ...apiError, message: this.pacientesTexts.auditUnavailable });
            return;
          }

          this.error.set(apiError);
        },
      });
  }

  refreshIndicators(): void {
    if (this.refreshing()) {
      return;
    }

    this.refreshing.set(true);
    this.error.set(null);

    this.service
      .getPacienteResumenIndicadores(this.pacienteId, this.sucursalId())
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          const current = this.resumen();

          if (current !== null) {
            this.resumen.set({
              ...current,
              bloques: {
                ...current.bloques,
                ...response.data.bloques,
              },
              contexto_sucursal: response.data.contexto_sucursal,
              sucursales_accesibles: response.data.sucursales_accesibles,
              fecha_corte: response.data.fecha_corte,
              generado_en: response.data.generado_en,
            });
          }

          this.can.set(response.can);
          this.refreshing.set(false);
          this.toast.success(this.texts.refreshed, 'Los indicadores se recalcularon.');
        },
        error: (apiError: PacientesApiError) => {
          this.refreshing.set(false);
          this.error.set(apiError);
        },
      });
  }

  back(): void {
    void this.router.navigate(['/pacientes']);
  }

  openFullFicha(): void {
    void this.router.navigate(['/pacientes', this.pacienteId]);
  }

  openFichaIngreso(): void {
    void this.router.navigate(['/pacientes', this.pacienteId, 'ficha-ingreso']);
  }

  openInscripciones(): void {
    void this.router.navigate(['/inscripciones'], {
      queryParams: { paciente_id: this.pacienteId },
    });
  }

  openTurnos(): void {
    void this.router.navigate(['/agenda'], {
      queryParams: { paciente_id: this.pacienteId },
    });
  }

  openPagos(): void {
    void this.router.navigate(['/pagos'], {
      queryParams: { paciente_id: this.pacienteId },
    });
  }

  estadoColor(estado: string): 'success' | 'warning' | 'gray' {
    return estado === 'activo' ? 'success' : estado === 'en_pausa' ? 'warning' : 'gray';
  }

  estadoPagoColor(estado: string): 'success' | 'warning' | 'error' | 'gray' {
    return estado === 'al_dia'
      ? 'success'
      : estado === 'con_saldo'
        ? 'warning'
        : estado === 'vencido'
          ? 'error'
          : 'gray';
  }

  estadoPagoLabel(estado: string): string {
    return estado === 'al_dia'
      ? this.texts.alDia
      : estado === 'con_saldo'
        ? this.texts.conSaldo
        : estado === 'vencido'
          ? this.texts.vencido
          : this.texts.sinServicios;
  }

  tieneBanderasSensibles(data: { tiene_alergias: boolean; tiene_condiciones_medicas: boolean; tiene_observaciones: boolean }): boolean {
    return data.tiene_alergias || data.tiene_condiciones_medicas || data.tiene_observaciones;
  }

  isUnavailable(bloque: ResumenBloque<unknown>): boolean {
    return bloque.estado === 'no_disponible';
  }

  tipoServicioLabel(tipo: string): string {
    return tipo === 'sala_grupal'
      ? 'Sala grupal'
      : tipo === 'paquete'
        ? 'Paquete de sesiones'
        : tipo;
  }

  formatDate(value: string | null | undefined): string {
    if (!value) {
      return '—';
    }

    const [year, month, day] = value.slice(0, 10).split('-');

    return year && month && day ? `${day}/${month}/${year}` : value;
  }

  formatDateTime(value: string | null | undefined): string {
    if (!value) {
      return '—';
    }

    const date = new Date(value);

    return Number.isNaN(date.getTime())
      ? value
      : new Intl.DateTimeFormat('es-BO', {
          day: '2-digit',
          month: '2-digit',
          year: 'numeric',
          hour: '2-digit',
          minute: '2-digit',
        }).format(date);
  }
}
