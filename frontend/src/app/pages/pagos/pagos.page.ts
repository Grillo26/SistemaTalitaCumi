import { Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormsModule } from '@angular/forms';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { InputDirective } from '../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../shared/components/ui/modal/modal.component';
import { PaginationComponent } from '../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../shared/components/ui/select/select-field.component';
import { TABLE_IMPORTS } from '../../shared/components/ui/table';
import { InscripcionDateInputComponent } from '../inscripciones-atenciones/salas-grupales/inscripcion-date-input.component';
import { ToastService } from '../../core/services/toast.service';
import {
  PAGO_ESTADO_OPTIONS,
  PagoAbilities,
  PagoApiError,
  PagoEntidadResumen,
  PagoListParams,
  PagoMedio,
  PagoRow,
  formatBs,
  formatFecha,
} from './pagos.models';
import { PagoRegistroModalComponent } from './pago-registro-modal.component';
import { PagosService } from './pagos.service';

/**
 * Listado de pagos y saldos (08.01/RF-008, RF-009, RF-006, RNF-010).
 * Sin botón de PDF ni de anulación: esas acciones pertenecen a 8.2 y 8.4.
 */
@Component({
  selector: 'app-pagos-page',
  imports: [
    FormsModule,
    PageBreadcrumbComponent,
    ComponentCardComponent,
    AlertComponent,
    BadgeComponent,
    ButtonComponent,
    InputDirective,
    ModalComponent,
    PaginationComponent,
    SelectFieldComponent,
    InscripcionDateInputComponent,
    PagoRegistroModalComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './pagos.page.html',
})
export class PagosPageComponent implements OnInit {
  private readonly service = inject(PagosService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly estadoOptions = PAGO_ESTADO_OPTIONS;
  readonly formatBs = formatBs;
  readonly formatFecha = formatFecha;

  readonly rows = signal<PagoRow[]>([]);
  readonly abilities = signal<PagoAbilities>({
    view: false,
    register: false,
    authorize_future: false,
    export: false,
    delete: false,
    reprint: false,
    payable_entities: false,
  });
  readonly medios = signal<{ value: string; label: string }[]>([]);
  readonly loading = signal(false);
  readonly listError = signal<string | null>(null);
  readonly page = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly selected = signal<PagoRow | null>(null);
  readonly exporting = signal(false);

  buscarQ = '';
  readonly buscando = signal(false);
  readonly resultadosEntidad = signal<PagoEntidadResumen[]>([]);
  readonly buscarError = signal<string | null>(null);

  readonly registroEntidad = signal<PagoEntidadResumen | null>(null);

  filters: PagoListParams & { numero: string; medio_pago: string; estado: string } = {
    periodo_desde: '',
    periodo_hasta: '',
    numero: '',
    medio_pago: '',
    estado: '',
  };

  ngOnInit(): void {
    this.service
      .opciones()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) =>
          this.medios.set([
            { value: '', label: 'Todos los medios' },
            ...response.data.medios.map((medio: PagoMedio) => ({ value: medio.codigo, label: medio.nombre })),
          ]),
        error: () => this.medios.set([{ value: '', label: 'Todos los medios' }]),
      });

    this.load();
  }

  applyFilters(): void {
    this.page.set(1);
    this.load();
  }

  clearFilters(): void {
    this.filters = { periodo_desde: '', periodo_hasta: '', numero: '', medio_pago: '', estado: '' };
    this.applyFilters();
  }

  goToPage(page: number): void {
    this.page.set(page);
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.listError.set(null);

    this.service
      .list({ ...this.filters, page: this.page(), per_page: 20 })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.loading.set(false);
          this.rows.set(response.data);
          this.abilities.set(response.can);
          this.lastPage.set(response.meta.last_page);
          this.total.set(response.meta.total);
        },
        error: (error: PagoApiError) => {
          this.loading.set(false);
          this.listError.set(error.message);
        },
      });
  }

  open(row: PagoRow): void {
    this.selected.set(row);

    this.service
      .show(row.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.selected.set(response.data),
        error: (error: PagoApiError) => this.toast.error('No se pudo cargar el pago', error.message),
      });
  }

  closeDetail(): void {
    this.selected.set(null);
  }

  buscar(): void {
    const q = this.buscarQ.trim();

    if (q.length < 2) {
      this.buscarError.set('Ingresá al menos dos caracteres del nombre del paciente.');
      return;
    }

    this.buscando.set(true);
    this.buscarError.set(null);

    this.service
      .buscarEntidades(q)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.buscando.set(false);
          this.resultadosEntidad.set(response.data);
        },
        error: (error: PagoApiError) => {
          this.buscando.set(false);
          this.resultadosEntidad.set([]);
          this.buscarError.set(error.message);
        },
      });
  }

  registrarEn(entidad: PagoEntidadResumen): void {
    this.registroEntidad.set(entidad);
  }

  onRegistered(): void {
    this.registroEntidad.set(null);
    this.toast.success('Pago registrado', 'El saldo y el listado se actualizaron.');
    this.load();
  }

  exportar(): void {
    this.exporting.set(true);

    this.service
      .exportarCsv({ ...this.filters })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (blob) => {
          this.exporting.set(false);
          const url = URL.createObjectURL(blob);
          const enlace = document.createElement('a');
          enlace.href = url;
          enlace.download = `pagos_${new Date().toISOString().slice(0, 10)}.csv`;
          enlace.click();
          URL.revokeObjectURL(url);
        },
        error: (error: PagoApiError) => {
          this.exporting.set(false);
          this.toast.error('No se pudo exportar', error.message);
        },
      });
  }
}
