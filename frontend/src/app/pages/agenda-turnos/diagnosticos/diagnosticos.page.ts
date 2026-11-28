import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
import { PaginationComponent } from '../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { TABLE_IMPORTS } from '../../../shared/components/ui/table';
import {
  ESTADO_GENERAL_COLOR,
  fechaLegible,
  horaLegible,
  type DiagnosticosApiError,
  type DiagnosticoListaRow,
  type DiagnosticosOpcionesData,
} from './diagnosticos.models';
import { DiagnosticosService } from './diagnosticos.service';

@Component({
  selector: 'app-diagnosticos-page',
  imports: [
    AlertComponent,
    BadgeComponent,
    ButtonComponent,
    ComponentCardComponent,
    InputDirective,
    PageBreadcrumbComponent,
    PaginationComponent,
    SelectFieldComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './diagnosticos.page.html',
})
export class DiagnosticosPageComponent implements OnInit {
  private readonly service = inject(DiagnosticosService);
  private readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);

  readonly fechaLegible = fechaLegible;
  readonly horaLegible = horaLegible;
  readonly estadoColor = (estado: keyof typeof ESTADO_GENERAL_COLOR) =>
    ESTADO_GENERAL_COLOR[estado] ?? 'gray';

  readonly desde = signal(this.monthStart());
  readonly hasta = signal(this.monthEnd());
  readonly sucursalId = signal('');
  readonly tipoId = signal('');
  readonly estadoGeneral = signal('');
  readonly profesionalId = signal('');
  readonly opciones = signal<DiagnosticosOpcionesData | null>(null);
  readonly data = signal<DiagnosticoListaRow[]>([]);
  readonly loading = signal(false);
  readonly exporting = signal(false);
  readonly error = signal<string | null>(null);
  readonly page = signal(1);
  readonly lastPage = signal(1);
  readonly total = signal(0);
  readonly canExportar = signal(false);

  readonly sucursalOpciones = computed(() =>
    (this.auth.user()?.branches ?? []).map((branch) => ({
      value: String(branch.id),
      label: branch.nombre,
    })),
  );
  readonly tipoOpciones = computed(() => [
    { value: '', label: 'Todos los tipos' },
    ...(this.opciones()?.tipos_diagnostico ?? []).map((tipo) => ({
      value: String(tipo.id),
      label: tipo.nombre,
    })),
  ]);
  readonly estadoOpciones = computed(() => [
    { value: '', label: 'Todos los estados' },
    ...(this.opciones()?.estados ?? []).map((estado) => ({
      value: estado.value,
      label: estado.label,
    })),
  ]);
  readonly profesionalOpciones = computed(() => [
    { value: '', label: 'Todos los profesionales' },
    ...(this.opciones()?.profesionales ?? []).map((profesional) => ({
      value: String(profesional.id),
      label: profesional.nombre_completo,
    })),
  ]);

  ngOnInit(): void {
    const first = this.auth.user()?.branches?.[0];

    if (first !== undefined) {
      this.sucursalId.set(String(first.id));
      this.cargarOpciones();
    }

    this.cargar(1);
  }

  cambiarSucursal(value: string): void {
    this.sucursalId.set(value);
    this.profesionalId.set('');
    this.cargarOpciones();
    this.cargar(1);
  }

  filtrar(): void {
    this.cargar(1);
  }

  irAPagina(page: number): void {
    this.cargar(page);
  }

  limpiar(): void {
    this.tipoId.set('');
    this.estadoGeneral.set('');
    this.profesionalId.set('');
    this.cargar(1);
  }

  irACoordinar(id: number): void {
    this.router.navigate(['/agenda-turnos/diagnosticos', id]);
  }

  exportar(): void {
    if (this.exporting()) {
      return;
    }

    this.exporting.set(true);
    this.error.set(null);

    this.service.exportarCsv(this.filtros()).subscribe({
      next: (blob) => {
        this.exporting.set(false);
        this.descargar(blob);
      },
      error: (error: DiagnosticosApiError) => {
        this.exporting.set(false);
        const message = error.errors?.['reporte']?.[0] ?? error.message;
        this.error.set(message);
        this.toast.error('No se pudo exportar', message);
      },
    });
  }

  private cargar(page: number): void {
    this.loading.set(true);
    this.error.set(null);

    this.service.listar({ ...this.filtros(), page, per_page: 25 }).subscribe({
      next: (response) => {
        this.loading.set(false);
        this.data.set(response.data);
        this.page.set(response.meta.current_page);
        this.lastPage.set(response.meta.last_page);
        this.total.set(response.meta.total);
        this.canExportar.set(response.can.exportar);
      },
      error: (error: DiagnosticosApiError) => {
        this.loading.set(false);
        this.data.set([]);
        this.error.set(error.message);
      },
    });
  }

  private cargarOpciones(): void {
    this.service.opciones(this.sucursalId() ? Number(this.sucursalId()) : null).subscribe({
      next: (response) => this.opciones.set(response.data),
      error: () => this.opciones.set(null),
    });
  }

  private filtros(): Record<string, string | number> {
    return {
      desde: this.desde(),
      hasta: this.hasta(),
      ...(this.sucursalId() ? { sucursal_id: Number(this.sucursalId()) } : {}),
      ...(this.tipoId() ? { tipo_diagnostico_id: Number(this.tipoId()) } : {}),
      ...(this.estadoGeneral() ? { estado_general: this.estadoGeneral() } : {}),
      ...(this.profesionalId() ? { profesional_id: Number(this.profesionalId()) } : {}),
    };
  }

  private descargar(blob: Blob): void {
    const url = URL.createObjectURL(blob);
    const anchor = document.createElement('a');
    anchor.href = url;
    anchor.download = `diagnosticos-${this.desde()}-${this.hasta()}.csv`;
    anchor.click();
    URL.revokeObjectURL(url);
  }

  private monthStart(): string {
    const now = new Date();

    return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-01`;
  }

  private monthEnd(): string {
    const now = new Date();
    const last = new Date(now.getFullYear(), now.getMonth() + 1, 0);

    return `${last.getFullYear()}-${String(last.getMonth() + 1).padStart(2, '0')}-${String(last.getDate()).padStart(2, '0')}`;
  }
}
