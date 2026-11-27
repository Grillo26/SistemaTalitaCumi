import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { Observable, forkJoin, of, switchMap } from 'rxjs';
import { debounceTime, map } from 'rxjs/operators';
import { ToastService } from '../../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../../shared/components/ui/modal/modal.component';
import { PaginationComponent } from '../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { TABLE_IMPORTS } from '../../../shared/components/ui/table';
import { SucursalesService } from '../../organizacion/sucursales/sucursales.service';
import { Branch } from '../../organizacion/sucursales/sucursales.models';
import { CatalogoServiciosService } from '../../catalogo-servicios/servicios/servicios.service';
import { Servicio } from '../../catalogo-servicios/servicios/servicios.models';
import { PaqueteComprobanteComponent } from '../paquetes-sesiones/paquete-comprobante.component';
import { ComprobantePaqueteData } from '../paquetes-sesiones/paquetes-sesiones.models';
import { PaquetesSesionesService } from '../paquetes-sesiones/paquetes-sesiones.service';
import { InscripcionComprobanteComponent } from '../salas-grupales/inscripcion-comprobante.component';
import { InscripcionDateInputComponent } from '../salas-grupales/inscripcion-date-input.component';
import { ComprobanteData } from '../salas-grupales/salas-grupales.models';
import { SalasGrupalesService } from '../salas-grupales/salas-grupales.service';
import {
  ESTADO_VIGENCIA_OPTIONS,
  ExportFormat,
  RenovacionItem,
  RenovacionResult,
  TIPO_ENTIDAD_OPTIONS,
  TipoEntidadVigencia,
  VIGENCIA_TEXTS,
  VigenciaAbilities,
  VigenciaApiError,
  VigenciaRow,
  VigenciaTab,
  VentanaAlerta,
  estadoVigenciaColor,
  formatBs,
  formatDate,
  whatsappUrl,
} from './vigencias.models';
import { VigenciasService } from './vigencias.service';

interface VigenciaMeta {
  current_page: number;
  last_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

const EMPTY_ABILITIES: VigenciaAbilities = {
  view: false,
  reports: false,
  export: false,
  config: false,
  view_tutor_contact: false,
  renew: {
    inscripcion_sala: false,
    paquete_sesiones: false,
  },
  view_types: {
    inscripcion_sala: false,
    paquete_sesiones: false,
  },
};

@Component({
  selector: 'app-vigencias-page',
  imports: [
    ReactiveFormsModule,
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
    InscripcionComprobanteComponent,
    PaqueteComprobanteComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './vigencias.page.html',
})
export class VigenciasPageComponent implements OnInit {
  private readonly service = inject(VigenciasService);
  private readonly sucursalesService = inject(SucursalesService);
  private readonly catalogoService = inject(CatalogoServiciosService);
  private readonly salasService = inject(SalasGrupalesService);
  private readonly paquetesService = inject(PaquetesSesionesService);
  private readonly toast = inject(ToastService);
  private readonly fb = inject(FormBuilder);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = VIGENCIA_TEXTS;
  readonly estadoOptions = ESTADO_VIGENCIA_OPTIONS;
  readonly tipoOptions = TIPO_ENTIDAD_OPTIONS;
  readonly formatBs = formatBs;
  readonly formatDate = formatDate;
  readonly whatsappUrl = whatsappUrl;
  readonly estadoVigenciaColor = estadoVigenciaColor;

  readonly activeTab = signal<VigenciaTab>('panel');
  readonly abilities = signal<VigenciaAbilities>(EMPTY_ABILITIES);
  readonly branches = signal<Branch[]>([]);
  readonly services = signal<Servicio[]>([]);
  readonly loadingOptions = signal(false);

  readonly panelRows = signal<VigenciaRow[]>([]);
  readonly panelMeta = signal<VigenciaMeta>(this.emptyMeta());
  readonly panelLoading = signal(false);
  readonly panelError = signal<string | null>(null);

  readonly reportRows = signal<VigenciaRow[]>([]);
  readonly reportMeta = signal<VigenciaMeta>(this.emptyMeta());
  readonly reportLoading = signal(false);
  readonly reportError = signal<string | null>(null);
  readonly exporting = signal<ExportFormat | null>(null);

  readonly ventana = signal<VentanaAlerta | null>(null);
  readonly configLoading = signal(false);
  readonly configSaving = signal(false);
  readonly configError = signal<string | null>(null);

  readonly selectedRows = signal<VigenciaRow[]>([]);
  readonly seleccionPacienteId = computed(() => this.selectedRows()[0]?.paciente.id ?? null);

  readonly renovacionOpen = signal(false);
  readonly renovacionLoading = signal(false);
  readonly renovacionSaving = signal(false);
  readonly renovacionError = signal<string | null>(null);
  readonly renovacionItems = signal<RenovacionItem[]>([]);
  readonly renovacionMultiple = computed(() => this.renovacionItems().length > 1);

  readonly lastRenewal = signal<RenovacionResult[]>([]);
  readonly comprobanteOpen = signal(false);
  readonly comprobanteTipo = signal<TipoEntidadVigencia | null>(null);
  readonly inscripcionComprobante = signal<ComprobanteData | null>(null);
  readonly paqueteComprobante = signal<ComprobantePaqueteData | null>(null);

  readonly panelForm = this.fb.nonNullable.group({
    search: [''],
    sucursal_id: [''],
    tipo_entidad: [''],
    estado: [''],
  });

  readonly reportForm = this.fb.nonNullable.group({
    sucursal_id: [''],
    tipo_entidad: [''],
    estado: [''],
    servicio_id: [''],
    periodo_desde: [''],
    periodo_hasta: [''],
  });

  readonly configForm = this.fb.nonNullable.group({
    valor: [7, [Validators.required, Validators.min(1), Validators.max(365)]],
  });

  readonly branchOptions = computed(() => [
    { value: '', label: this.texts.allBranches },
    ...this.branches().map((branch) => ({ value: String(branch.id), label: branch.nombre })),
  ]);

  readonly branchTabs = computed(() => [
    { id: '', nombre: this.texts.allBranches },
    ...this.branches().map((branch) => ({ id: String(branch.id), nombre: branch.nombre })),
  ]);

  readonly serviceOptions = computed(() => [
    { value: '', label: this.texts.allServices },
    ...this.services().map((servicio) => ({ value: String(servicio.id), label: servicio.nombre })),
  ]);

  readonly tipoFilterOptions = computed(() => {
    const allowed = this.abilities().view_types;

    return this.tipoOptions.filter(
      (option) =>
        option.value === '' ||
        (option.value === 'inscripcion_sala' && allowed.inscripcion_sala) ||
        (option.value === 'paquete_sesiones' && allowed.paquete_sesiones),
    );
  });

  readonly canRenovarSeleccionadas = computed(
    () => this.selectedRows().length > 0 && this.abilities().view,
  );

  ngOnInit(): void {
    this.panelForm.controls.search.valueChanges
      .pipe(debounceTime(300), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => {
        this.panelMeta.update((meta) => ({ ...meta, current_page: 1 }));
        this.loadPanel(1);
      });

    this.loadOptions();
    this.loadPanel(1);
  }

  setTab(tab: VigenciaTab): void {
    if (this.activeTab() === tab) {
      return;
    }

    this.activeTab.set(tab);

    if (tab === 'panel') {
      this.loadPanel(1);
      return;
    }

    if (tab === 'reportes') {
      this.loadReport(1);
      return;
    }

    this.loadConfig();
  }

  loadPanel(page = 1): void {
    this.panelLoading.set(true);
    this.panelError.set(null);

    const raw = this.panelForm.getRawValue();

    this.service
      .listPanel({
        search: raw.search.trim() || undefined,
        sucursal_id: raw.sucursal_id ? Number(raw.sucursal_id) : '',
        tipo_entidad: raw.tipo_entidad || undefined,
        estado: raw.estado || undefined,
        page,
        per_page: 20,
        sort: 'fecha_fin',
        direction: 'asc',
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.panelRows.set(response.data);
          this.abilities.set(response.can);
          this.panelMeta.set(this.metaFrom(response.meta));
          this.panelLoading.set(false);
          this.selectedRows.set([]);
        },
        error: (error: VigenciaApiError) => {
          this.panelLoading.set(false);
          this.panelError.set(this.errorMessage(error));
          this.panelRows.set([]);
        },
      });
  }

  applyPanelFilters(): void {
    this.loadPanel(1);
  }

  clearPanelFilters(): void {
    this.panelForm.reset({
      search: '',
      sucursal_id: '',
      tipo_entidad: '',
      estado: '',
    });
    this.loadPanel(1);
  }

  selectBranch(branchId: string): void {
    this.panelForm.controls.sucursal_id.setValue(branchId);
    this.loadPanel(1);
  }

  goToPanelPage(page: number): void {
    if (page < 1 || page > this.panelMeta().last_page) {
      return;
    }

    this.loadPanel(page);
  }

  loadReport(page = 1): void {
    if (!this.validateReportDates()) {
      return;
    }

    this.reportLoading.set(true);
    this.reportError.set(null);

    const raw = this.reportForm.getRawValue();

    this.service
      .listReporte({
        sucursal_id: raw.sucursal_id ? Number(raw.sucursal_id) : '',
        tipo_entidad: raw.tipo_entidad || undefined,
        estado: raw.estado || undefined,
        servicio_id: raw.servicio_id ? Number(raw.servicio_id) : '',
        periodo_desde: raw.periodo_desde || undefined,
        periodo_hasta: raw.periodo_hasta || undefined,
        page,
        per_page: 25,
        sort: 'fecha_fin',
        direction: 'asc',
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.reportRows.set(response.data);
          this.abilities.set(response.can);
          this.reportMeta.set(this.metaFrom(response.meta));
          this.reportLoading.set(false);
        },
        error: (error: VigenciaApiError) => {
          this.reportLoading.set(false);
          this.reportError.set(this.errorMessage(error));
          this.reportRows.set([]);
        },
      });
  }

  applyReportFilters(): void {
    this.loadReport(1);
  }

  clearReportFilters(): void {
    this.reportForm.reset({
      sucursal_id: '',
      tipo_entidad: '',
      estado: '',
      servicio_id: '',
      periodo_desde: '',
      periodo_hasta: '',
    });
    this.loadReport(1);
  }

  goToReportPage(page: number): void {
    if (page < 1 || page > this.reportMeta().last_page) {
      return;
    }

    this.loadReport(page);
  }

  exportar(formato: ExportFormat): void {
    if (this.exporting() !== null) {
      return;
    }

    this.exporting.set(formato);
    const raw = this.reportForm.getRawValue();

    this.service
      .exportar(formato, {
        sucursal_id: raw.sucursal_id ? Number(raw.sucursal_id) : '',
        tipo_entidad: raw.tipo_entidad || undefined,
        estado: raw.estado || undefined,
        servicio_id: raw.servicio_id ? Number(raw.servicio_id) : '',
        periodo_desde: raw.periodo_desde || undefined,
        periodo_hasta: raw.periodo_hasta || undefined,
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (blob) => {
          this.exporting.set(null);
          const url = URL.createObjectURL(blob);
          const link = document.createElement('a');
          link.href = url;
          link.download = `vigencias-${formato}.${formato === 'excel' ? 'xlsx' : 'pdf'}`;
          link.click();
          URL.revokeObjectURL(url);
        },
        error: (error: VigenciaApiError) => {
          this.exporting.set(null);
          this.toast.error(
            this.texts.reports,
            error.code === 'MODULO_NO_DISPONIBLE'
              ? this.texts.exportUnavailable
              : this.errorMessage(error),
          );
        },
      });
  }

  loadConfig(): void {
    this.configLoading.set(true);
    this.configError.set(null);

    this.service
      .getVentana()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.ventana.set(response.data);
          this.configForm.controls.valor.setValue(Number(response.data.valor));
          this.abilities.set(response.can);
          this.configLoading.set(false);
        },
        error: (error: VigenciaApiError) => {
          this.configLoading.set(false);
          this.configError.set(this.errorMessage(error));
        },
      });
  }

  saveConfig(): void {
    this.configForm.markAllAsTouched();

    if (this.configForm.invalid || this.ventana() === null || this.configSaving()) {
      return;
    }

    this.configSaving.set(true);
    this.configError.set(null);

    this.service
      .updateVentana(Number(this.configForm.controls.valor.value), this.ventana()!.version)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.ventana.set(response.data);
          this.configForm.controls.valor.setValue(Number(response.data.valor));
          this.configSaving.set(false);
          this.toast.success(this.texts.configuration, this.texts.configSaved);
        },
        error: (error: VigenciaApiError) => {
          this.configSaving.set(false);
          this.configError.set(this.errorMessage(error));
        },
      });
  }

  toggleSelection(row: VigenciaRow): void {
    if (!row.can.renew) {
      return;
    }

    const current = this.selectedRows();
    const exists = current.some(
      (selected) => selected.tipo_entidad === row.tipo_entidad && selected.id === row.id,
    );

    if (exists) {
      this.selectedRows.set(
        current.filter(
          (selected) => !(selected.tipo_entidad === row.tipo_entidad && selected.id === row.id),
        ),
      );
      return;
    }

    if (current.length > 0 && current[0].paciente.id !== row.paciente.id) {
      this.toast.error(
        this.texts.renewSelected,
        'Solo podés renovar entidades del mismo paciente.',
      );
      return;
    }

    this.selectedRows.set([...current, row]);
  }

  isSelected(row: VigenciaRow): boolean {
    return this.selectedRows().some(
      (selected) => selected.tipo_entidad === row.tipo_entidad && selected.id === row.id,
    );
  }

  selectionDisabled(row: VigenciaRow): boolean {
    const selectedPatient = this.seleccionPacienteId();

    return !row.can.renew || (selectedPatient !== null && selectedPatient !== row.paciente.id);
  }

  openRenovar(row: VigenciaRow): void {
    this.startRenovacion([row]);
  }

  openRenovarSeleccionadas(): void {
    this.startRenovacion(this.selectedRows());
  }

  closeRenovacion(): void {
    if (this.renovacionSaving()) {
      return;
    }

    this.renovacionOpen.set(false);
    this.renovacionItems.set([]);
    this.renovacionError.set(null);
  }

  toggleConfirmarTarifa(index: number): void {
    this.renovacionItems.update((items) =>
      items.map((item, itemIndex) =>
        itemIndex === index ? { ...item, confirmarTarifa: !item.confirmarTarifa } : item,
      ),
    );
  }

  confirmarRenovacion(): void {
    const items = this.renovacionItems();

    if (items.length === 0 || this.renovacionSaving()) {
      return;
    }

    if (items.some((item) => item.preview.monto_vigente === null)) {
      this.renovacionError.set(this.texts.noTariff);
      return;
    }

    if (items.some((item) => item.preview.cambio_tarifa && !item.confirmarTarifa)) {
      this.renovacionError.set('Confirmá la nueva tarifa en cada entidad que cambió.');
      return;
    }

    this.renovacionSaving.set(true);
    this.renovacionError.set(null);

    if (items.length === 1) {
      const item = items[0];

      this.service
        .renovar(item.fila.tipo_entidad, item.fila.id, {
          fecha_inicio: item.preview.fecha_inicio,
          fecha_fin: item.preview.fecha_fin,
          monto_visto: item.preview.monto_vigente ?? '',
          tarifa_id_vista: item.preview.tarifa_vigente?.tarifa_id ?? 0,
          confirmar_tarifa_cambio: item.confirmarTarifa,
        })
        .pipe(takeUntilDestroyed(this.destroyRef))
        .subscribe({
          next: (response) => this.onRenovacionSuccess([response.data]),
          error: (error: VigenciaApiError) => this.onRenovacionError(error),
        });

      return;
    }

    this.service
      .renovarMultiple({
        items: items.map((item) => ({
          tipo_entidad: item.fila.tipo_entidad,
          entidad_id: item.fila.id,
          fecha_inicio: item.preview.fecha_inicio,
          fecha_fin: item.preview.fecha_fin,
          monto_visto: item.preview.monto_vigente ?? '',
          tarifa_id_vista: item.preview.tarifa_vigente?.tarifa_id ?? 0,
          confirmar_tarifa_cambio: item.confirmarTarifa,
        })),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.onRenovacionSuccess(response.data.items),
        error: (error: VigenciaApiError) => this.onRenovacionError(error),
      });
  }

  verComprobante(result: RenovacionResult): void {
    this.comprobanteTipo.set(result.tipo_entidad);
    this.inscripcionComprobante.set(null);
    this.paqueteComprobante.set(null);

    if (result.tipo_entidad === 'inscripcion_sala') {
      this.salasService.getComprobante(result.entidad_id).subscribe({
        next: (response) => {
          this.inscripcionComprobante.set(response.data);
          this.comprobanteOpen.set(true);
        },
        error: (error: VigenciaApiError) =>
          this.toast.error(this.texts.receipt, this.errorMessage(error)),
      });

      return;
    }

    this.paquetesService.getComprobante(result.entidad_id).subscribe({
      next: (response) => {
        this.paqueteComprobante.set(response.data);
        this.comprobanteOpen.set(true);
      },
      error: (error: VigenciaApiError) =>
        this.toast.error(this.texts.receipt, this.errorMessage(error)),
    });
  }

  registrarPago(result: RenovacionResult): void {
    const request: Observable<unknown> =
      result.tipo_entidad === 'inscripcion_sala'
        ? this.salasService.registrarPago(result.entidad_id)
        : this.paquetesService.registrarPago(result.entidad_id);

    request.subscribe({
      next: () => this.toast.success(this.texts.registerPayment, 'Pago registrado.'),
      error: (error: VigenciaApiError) =>
        this.toast.error(this.texts.registerPayment, this.errorMessage(error)),
    });
  }

  closeComprobante(): void {
    this.comprobanteOpen.set(false);
    this.inscripcionComprobante.set(null);
    this.paqueteComprobante.set(null);
  }

  diasLabel(row: VigenciaRow): string {
    if (row.dias_para_vencer === 0) {
      return 'Vence hoy';
    }

    return row.dias_para_vencer > 0
      ? `En ${row.dias_para_vencer} día(s)`
      : `Hace ${Math.abs(row.dias_para_vencer)} día(s)`;
  }

  saldoLabel(row: VigenciaRow): string {
    if (row.tipo_entidad === 'inscripcion_sala') {
      return this.texts.notApplicable;
    }

    return row.saldo_disponible && row.saldo !== null
      ? `${row.saldo} sesión(es)`
      : this.texts.noBalance;
  }

  errorMessage(error: VigenciaApiError): string {
    if (error.code === 'SIN_SUCURSALES_ASIGNADAS') {
      return this.texts.noBranches;
    }

    if (error.code === 'MODULO_NO_DISPONIBLE') {
      return error.message;
    }

    return error.message || this.texts.noPermission;
  }

  private startRenovacion(rows: VigenciaRow[]): void {
    if (rows.length === 0) {
      return;
    }

    this.renovacionOpen.set(true);
    this.renovacionLoading.set(true);
    this.renovacionError.set(null);
    this.renovacionItems.set([]);

    forkJoin(
      rows.map((row) =>
        this.service.getRenovacion(row.tipo_entidad, row.id).pipe(
          map(
            (response) =>
              ({
                fila: row,
                preview: response.data,
                confirmarTarifa: false,
              }) satisfies RenovacionItem,
          ),
        ),
      ),
    )
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (items) => {
          this.renovacionItems.set(items);
          this.renovacionLoading.set(false);
        },
        error: (error: VigenciaApiError) => {
          this.renovacionLoading.set(false);
          this.renovacionError.set(this.errorMessage(error));
        },
      });
  }

  private onRenovacionSuccess(results: RenovacionResult[]): void {
    this.renovacionSaving.set(false);
    this.renovacionOpen.set(false);
    this.renovacionItems.set([]);
    this.selectedRows.set([]);
    this.lastRenewal.set(results);
    this.toast.success(
      results.length > 1 ? this.texts.successMultiple : this.texts.successRenew,
      'La nueva entidad quedó vinculada al origen.',
    );
    this.loadPanel(1);
  }

  private onRenovacionError(error: VigenciaApiError): void {
    this.renovacionSaving.set(false);
    this.renovacionError.set(this.errorMessage(error));

    if (error.code === 'CAMBIO_TARIFA_SIN_CONFIRMAR') {
      this.refreshRenovacionPreviews();
    }
  }

  /**
   * Vuelve a pedir las tarifas vigentes para que el usuario confirme el monto
   * actual sin cerrar el modal. No renueva nada por sí solo.
   */
  private refreshRenovacionPreviews(): void {
    const items = this.renovacionItems();

    if (items.length === 0) {
      return;
    }

    this.renovacionLoading.set(true);

    forkJoin(
      items.map((item) =>
        this.service.getRenovacion(item.fila.tipo_entidad, item.fila.id).pipe(
          map(
            (response) =>
              ({
                fila: item.fila,
                preview: response.data,
                confirmarTarifa: false,
              }) satisfies RenovacionItem,
          ),
        ),
      ),
    )
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (fresh) => {
          this.renovacionItems.set(fresh);
          this.renovacionLoading.set(false);
        },
        error: () => {
          this.renovacionLoading.set(false);
        },
      });
  }

  private validateReportDates(): boolean {
    const { periodo_desde, periodo_hasta } = this.reportForm.getRawValue();

    if ((periodo_desde === '') !== (periodo_hasta === '')) {
      this.reportError.set('Completá ambas fechas del período o dejá las dos vacías.');
      return false;
    }

    if (periodo_desde !== '' && periodo_hasta !== '' && periodo_hasta < periodo_desde) {
      this.reportError.set('La fecha hasta no puede ser anterior a la fecha desde.');
      return false;
    }

    return true;
  }

  private loadOptions(): void {
    this.loadingOptions.set(true);

    this.sucursalesService
      .list({ estado: 'activa', page: 1, per_page: 50, sort: 'nombre', direction: 'asc' })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.branches.set(response.data),
        error: () => this.branches.set([]),
      });

    this.loadAllServices()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (services) => {
          this.services.set(services);
          this.loadingOptions.set(false);
        },
        error: () => {
          this.services.set([]);
          this.loadingOptions.set(false);
        },
      });
  }

  private loadAllServices(): Observable<Servicio[]> {
    return this.loadAllPages<Servicio>((page) =>
      this.catalogoService.listServicios({
        estado: 'activo',
        page,
        per_page: 50,
      }),
    );
  }

  private loadAllPages<T>(
    fetcher: (page: number) => Observable<{ data: T[]; meta: { last_page: number } }>,
  ): Observable<T[]> {
    return fetcher(1).pipe(
      switchMap((first) => {
        if (first.meta.last_page <= 1) {
          return of(first.data);
        }

        return forkJoin(
          Array.from({ length: first.meta.last_page - 1 }, (_, index) => fetcher(index + 2)),
        ).pipe(map((pages) => [...first.data, ...pages.flatMap((page) => page.data)]));
      }),
    );
  }

  private metaFrom(meta: {
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
  }): VigenciaMeta {
    return {
      current_page: meta.current_page,
      last_page: meta.last_page,
      total: meta.total,
      from: meta.from,
      to: meta.to,
    };
  }

  private emptyMeta(): VigenciaMeta {
    return {
      current_page: 1,
      last_page: 1,
      total: 0,
      from: null,
      to: null,
    };
  }
}
