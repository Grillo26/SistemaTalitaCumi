import {
  Component,
  DestroyRef,
  ElementRef,
  OnInit,
  ViewChild,
  computed,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import {
  FormControl,
  FormGroup,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { debounceTime, distinctUntilChanged } from 'rxjs';
import { ToastService } from '../../../core/services/toast.service';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';
import { ModalComponent } from '../../../shared/components/ui/modal/modal.component';
import { PaginationComponent } from '../../../shared/components/ui/pagination/pagination.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { SwitchComponent } from '../../../shared/components/ui/switch/switch.component';
import { TABLE_IMPORTS } from '../../../shared/components/ui/table';
import { TextareaDirective } from '../../../shared/components/ui/textarea/textarea.directive';
import {
  CATEGORY_OPTIONS,
  ConfiguracionAbilities,
  EMPTY_CONFIGURACION_ABILITIES,
  Parameter,
  ParameterApiError,
  ParameterCategory,
  ParameterGroup,
  ParameterHistoryEntry,
  ParameterType,
} from './configuracion.models';
import { ConfiguracionService } from './configuracion.service';
import { ParameterTimeInputComponent } from './parameter-time-input.component';

@Component({
  selector: 'app-configuracion-page',
  imports: [
    ReactiveFormsModule,
    PageBreadcrumbComponent,
    ComponentCardComponent,
    AlertComponent,
    BadgeComponent,
    ButtonComponent,
    InputDirective,
    ModalComponent,
    SelectFieldComponent,
    SwitchComponent,
    TextareaDirective,
    PaginationComponent,
    ParameterTimeInputComponent,
    ...TABLE_IMPORTS,
  ],
  templateUrl: './configuracion.page.html',
})
export class ConfiguracionPageComponent implements OnInit {
  private readonly service = inject(ConfiguracionService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  @ViewChild('logoInput') private logoInput?: ElementRef<HTMLInputElement>;

  readonly parameters = signal<Parameter[]>([]);
  readonly loading = signal(false);
  readonly error = signal<ParameterApiError | null>(null);
  readonly abilities = signal<ConfiguracionAbilities>(EMPTY_CONFIGURACION_ABILITIES);

  readonly searchControl = new FormControl('', { nonNullable: true });
  readonly categoryFilter = signal<ParameterCategory | ''>('');
  readonly categoryOptions = CATEGORY_OPTIONS;

  readonly selected = signal<Parameter | null>(null);
  readonly detailOpen = signal(false);
  readonly detailLoading = signal(false);

  readonly editOpen = signal(false);
  readonly editing = signal<Parameter | null>(null);
  readonly editForm = new FormGroup({
    valor: new FormControl<string | number | boolean>(''),
    motivo: new FormControl('', { nonNullable: true }),
    confirmado: new FormControl(false, { nonNullable: true }),
  });
  readonly editFieldErrors = signal<Record<string, string[]>>({});
  readonly editError = signal<string | null>(null);
  readonly editConcurrency = signal(false);
  readonly saving = signal(false);

  readonly restoreOpen = signal(false);
  readonly restoreTarget = signal<Parameter | null>(null);
  readonly restoreForm = new FormGroup({
    motivo: new FormControl('', { nonNullable: true }),
    confirmado: new FormControl(false, { nonNullable: true }),
  });
  readonly restoreFieldErrors = signal<Record<string, string[]>>({});
  readonly restoreError = signal<string | null>(null);
  readonly restoreConcurrency = signal(false);
  readonly restoring = signal(false);

  readonly historyOpen = signal(false);
  readonly historyTarget = signal<Parameter | null>(null);
  readonly historyEntries = signal<ParameterHistoryEntry[]>([]);
  readonly historyLoading = signal(false);
  readonly historyError = signal<string | null>(null);
  readonly historyPage = signal(1);
  readonly historyLastPage = signal(1);
  readonly historyTotal = signal(0);

  readonly logoFile = signal<File | null>(null);
  readonly logoMotivo = signal('');
  readonly logoUploading = signal(false);
  readonly logoError = signal<string | null>(null);

  readonly groupedParameters = computed<ParameterGroup[]>(() => {
    const groups = new Map<ParameterCategory, ParameterGroup>();

    for (const parameter of this.parameters()) {
      const group = groups.get(parameter.categoria);

      if (group === undefined) {
        groups.set(parameter.categoria, {
          categoria: parameter.categoria,
          label: parameter.categoria_label,
          items: [parameter],
        });
      } else {
        group.items.push(parameter);
      }
    }

    return Array.from(groups.values());
  });

  readonly totalParameters = computed(() => this.parameters().length);

  ngOnInit(): void {
    this.load();

    this.searchControl.valueChanges
      .pipe(debounceTime(300), distinctUntilChanged(), takeUntilDestroyed(this.destroyRef))
      .subscribe(() => this.load());
  }

  load(): void {
    this.loading.set(true);
    this.error.set(null);

    this.service
      .list({
        search: this.searchControl.value,
        categoria: this.categoryFilter(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.parameters.set(response.data);
          this.abilities.set(response.can);
          this.loading.set(false);
        },
        error: (error: ParameterApiError) => {
          this.error.set(error);
          this.loading.set(false);
        },
      });
  }

  onCategoryChange(value: string): void {
    this.categoryFilter.set(value as ParameterCategory | '');
    this.load();
  }

  openDetail(parameter: Parameter): void {
    this.detailLoading.set(true);
    this.error.set(null);

    this.service
      .get(parameter.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.selected.set(response.data);
          this.abilities.set(response.can);
          this.logoError.set(null);
          this.logoFile.set(null);
          this.logoMotivo.set('');
          this.detailOpen.set(true);
          this.detailLoading.set(false);
        },
        error: (error: ParameterApiError) => {
          this.error.set(error);
          this.detailLoading.set(false);
        },
      });
  }

  closeDetail(): void {
    this.detailOpen.set(false);
    this.selected.set(null);
  }

  openEdit(parameter: Parameter): void {
    if (!this.canEdit(parameter)) {
      return;
    }

    this.detailOpen.set(false);
    this.editing.set(parameter);
    this.editOpen.set(true);
    this.editError.set(null);
    this.editFieldErrors.set({});
    this.editConcurrency.set(false);
    this.configureEditForm(parameter);
  }

  closeEdit(): void {
    this.editOpen.set(false);
    this.editing.set(null);
    this.editConcurrency.set(false);
    this.editFieldErrors.set({});
    this.editError.set(null);
  }

  saveEdit(): void {
    const target = this.editing();

    if (target === null || this.saving()) {
      return;
    }

    this.editForm.markAllAsTouched();
    this.editFieldErrors.set({});
    this.editError.set(null);

    if (this.editForm.invalid) {
      return;
    }

    const raw = this.editForm.getRawValue();

    if (target.sensible && !raw.confirmado) {
      this.editFieldErrors.set({
        confirmado: ['Este parámetro es sensible: debés confirmar el cambio.'],
      });
      return;
    }

    let valor: string | number | boolean = raw.valor ?? '';

    if (target.tipo === 'entero') {
      valor = Number(raw.valor);
    } else if (target.tipo === 'decimal') {
      valor = String(raw.valor ?? '');
    } else if (target.tipo === 'booleano') {
      valor = Boolean(raw.valor);
    } else {
      valor = String(raw.valor ?? '');
    }

    this.saving.set(true);

    this.service
      .update(target.id, {
        valor,
        version: target.version,
        confirmado: raw.confirmado,
        motivo: raw.motivo.trim() === '' ? null : raw.motivo.trim(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.applyUpdated(response.data);
          this.abilities.set(response.can);
          this.saving.set(false);
          this.closeEdit();
          this.toast.success('Parámetro actualizado', 'El cambio se registró en la bitácora.');
        },
        error: (error: ParameterApiError) => {
          this.saving.set(false);
          this.handleEditError(error);
        },
      });
  }

  reloadEditConflict(): void {
    const target = this.editing();

    if (target === null) {
      return;
    }

    this.service
      .get(target.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.editing.set(response.data);
          this.applyUpdated(response.data);
          this.configureEditForm(response.data);
          this.editConcurrency.set(false);
          this.editFieldErrors.set({});
          this.editError.set(null);
          this.toast.info('Datos recargados', 'Revisá el valor vigente antes de guardar.');
        },
        error: (error: ParameterApiError) => this.editError.set(error.message),
      });
  }

  openRestore(parameter: Parameter): void {
    if (!this.canRestore(parameter)) {
      return;
    }

    this.detailOpen.set(false);
    this.restoreTarget.set(parameter);
    this.restoreOpen.set(true);
    this.restoreForm.reset({ motivo: '', confirmado: false });
    this.restoreFieldErrors.set({});
    this.restoreError.set(null);
    this.restoreConcurrency.set(false);
  }

  closeRestore(): void {
    this.restoreOpen.set(false);
    this.restoreTarget.set(null);
    this.restoreFieldErrors.set({});
    this.restoreError.set(null);
    this.restoreConcurrency.set(false);
  }

  saveRestore(): void {
    const target = this.restoreTarget();

    if (target === null || this.restoring()) {
      return;
    }

    const raw = this.restoreForm.getRawValue();
    this.restoreFieldErrors.set({});
    this.restoreError.set(null);

    if (target.sensible) {
      const errors: Record<string, string[]> = {};

      if (!raw.confirmado) {
        errors['confirmado'] = ['Este parámetro es sensible: debés confirmar la restauración.'];
      }

      if (raw.motivo.trim() === '') {
        errors['motivo'] = ['Este parámetro es sensible: el motivo es obligatorio.'];
      }

      if (Object.keys(errors).length > 0) {
        this.restoreFieldErrors.set(errors);
        return;
      }
    }

    this.restoring.set(true);

    this.service
      .restore(target.id, {
        version: target.version,
        confirmado: raw.confirmado,
        motivo: raw.motivo.trim() === '' ? null : raw.motivo.trim(),
      })
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.applyUpdated(response.data);
          this.abilities.set(response.can);
          this.restoring.set(false);
          this.closeRestore();
          this.toast.success('Valor restaurado', 'Se aplicó el valor por defecto del parámetro.');
        },
        error: (error: ParameterApiError) => {
          this.restoring.set(false);

          if (error.status === 409 && error.code === 'PARAMETRO_VERSION_CONFLICT') {
            this.restoreConcurrency.set(true);
            return;
          }

          if (error.status === 422 && error.errors) {
            this.restoreFieldErrors.set(error.errors);
            return;
          }

          this.restoreError.set(error.message);
        },
      });
  }

  reloadRestoreConflict(): void {
    const target = this.restoreTarget();

    if (target === null) {
      return;
    }

    this.service
      .get(target.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.restoreTarget.set(response.data);
          this.applyUpdated(response.data);
          this.restoreConcurrency.set(false);
          this.restoreError.set(null);
        },
        error: (error: ParameterApiError) => this.restoreError.set(error.message),
      });
  }

  openHistory(parameter: Parameter): void {
    if (!this.abilities().view_all) {
      return;
    }

    this.detailOpen.set(false);
    this.historyTarget.set(parameter);
    this.historyOpen.set(true);
    this.historyEntries.set([]);
    this.historyError.set(null);
    this.loadHistory(1);
  }

  closeHistory(): void {
    this.historyOpen.set(false);
    this.historyTarget.set(null);
    this.historyEntries.set([]);
    this.historyError.set(null);
  }

  goToHistoryPage(page: number): void {
    this.loadHistory(page);
  }

  onLogoSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    this.logoFile.set(input.files?.[0] ?? null);
    this.logoError.set(null);
  }

  uploadLogo(): void {
    const target = this.selected();
    const file = this.logoFile();

    if (target === null || file === null || this.logoUploading()) {
      return;
    }

    this.logoUploading.set(true);
    this.logoError.set(null);

    this.service
      .uploadLogo(file, target.version, this.logoMotivo())
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.applyUpdated(response.data);
          this.selected.set(response.data);
          this.abilities.set(response.can);
          this.logoFile.set(null);
          this.logoMotivo.set('');
          this.logoUploading.set(false);

          if (this.logoInput !== undefined) {
            this.logoInput.nativeElement.value = '';
          }

          this.toast.success('Logo actualizado', 'La imagen del centro se guardó correctamente.');
        },
        error: (error: ParameterApiError) => {
          this.logoUploading.set(false);
          this.logoError.set(error.message);
        },
      });
  }

  canEdit(parameter: Parameter): boolean {
    return this.abilities().update && parameter.editable;
  }

  canRestore(parameter: Parameter): boolean {
    return (
      this.abilities().restore &&
      parameter.editable &&
      parameter.valor_texto !== parameter.valor_defecto_texto
    );
  }

  canUploadLogo(): boolean {
    return this.abilities().update_logo;
  }

  typeHint(type: ParameterType): string {
    return {
      entero: 'Número entero',
      decimal: 'Número decimal (punto como separador)',
      hora: 'Hora en formato HH:MM',
      texto: 'Texto',
      booleano: 'Verdadero o falso',
    }[type];
  }

  formatValue(parameter: Parameter): string {
    if (parameter.tipo === 'booleano') {
      return parameter.valor ? 'Sí' : 'No';
    }

    const value = parameter.valor_texto;

    if (value === null || value === '') {
      return '—';
    }

    return value;
  }

  formatDefaultValue(parameter: Parameter): string {
    if (parameter.tipo === 'booleano') {
      return parameter.valor_defecto ? 'Sí' : 'No';
    }

    const value = parameter.valor_defecto_texto;

    return value === null || value === '' ? '—' : value;
  }

  valueWithUnit(parameter: Parameter): string {
    const value = this.formatValue(parameter);

    if (value === '—' || parameter.unidad === null || parameter.unidad === '') {
      return value;
    }

    return `${value} ${parameter.unidad}`;
  }

  rangeLabel(parameter: Parameter): string {
    if (parameter.tipo === 'texto') {
      if (parameter.maximo !== null && parameter.maximo !== '') {
        return `Hasta ${parameter.maximo} caracteres`;
      }

      return 'Sin restricción de longitud';
    }

    if (parameter.minimo !== null && parameter.maximo !== null) {
      return `${parameter.minimo} – ${parameter.maximo}`;
    }

    if (parameter.minimo !== null) {
      return `Mínimo ${parameter.minimo}`;
    }

    if (parameter.maximo !== null) {
      return `Máximo ${parameter.maximo}`;
    }

    return 'Sin restricción';
  }

  isLongText(parameter: Parameter | null): boolean {
    return parameter !== null
      && ['direccion_centro', 'mensaje_whatsapp'].includes(parameter.clave);
  }

  fieldError(form: 'edit' | 'restore', field: string): string | null {
    const errors = form === 'edit' ? this.editFieldErrors() : this.restoreFieldErrors();

    return errors[field]?.[0] ?? null;
  }

  formatDate(value: string | null): string {
    if (value === null || value === '') {
      return '—';
    }

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
      return '—';
    }

    return new Intl.DateTimeFormat('es-BO', {
      dateStyle: 'medium',
      timeStyle: 'short',
    }).format(date);
  }

  historyActionLabel(action: string): string {
    return {
      CREAR: 'Alta inicial',
      EDITAR: 'Edición',
      RESTAURAR: 'Restauración',
    }[action] ?? action;
  }

  historyBadgeColor(action: string): 'success' | 'warning' | 'info' {
    return action === 'CREAR' ? 'info' : action === 'RESTAURAR' ? 'warning' : 'success';
  }

  historyValue(entry: ParameterHistoryEntry, side: 'before' | 'after'): string {
    const snapshot = side === 'before' ? entry.datos_anteriores : entry.datos_nuevos;
    const value = snapshot?.['valor'];

    return value === undefined || value === null || value === '' ? '—' : String(value);
  }

  historyUser(entry: ParameterHistoryEntry): string {
    return entry.usuario?.nombre ?? 'Sistema';
  }

  private configureEditForm(parameter: Parameter): void {
    this.editForm.reset({
      valor: parameter.valor,
      motivo: '',
      confirmado: false,
    });

    const valorControl = this.editForm.controls.valor;
    const motivoControl = this.editForm.controls.motivo;
    valorControl.clearValidators();
    motivoControl.setValidators(
      parameter.sensible
        ? [Validators.required, Validators.maxLength(1000)]
        : [Validators.maxLength(1000)],
    );

    if (parameter.tipo === 'entero' || parameter.tipo === 'decimal') {
      const validators = [Validators.required];

      if (parameter.minimo !== null && Number.isFinite(Number(parameter.minimo))) {
        validators.push(Validators.min(Number(parameter.minimo)));
      }

      if (parameter.maximo !== null && Number.isFinite(Number(parameter.maximo))) {
        validators.push(Validators.max(Number(parameter.maximo)));
      }

      valorControl.setValidators(validators);
    } else if (parameter.tipo === 'hora') {
      valorControl.setValidators([
        Validators.required,
        Validators.pattern(/^([01]\d|2[0-3]):[0-5]\d$/),
      ]);
    }

    valorControl.updateValueAndValidity();
    motivoControl.updateValueAndValidity();
  }

  private handleEditError(error: ParameterApiError): void {
    if (error.status === 409 && error.code === 'PARAMETRO_VERSION_CONFLICT') {
      this.editConcurrency.set(true);
      return;
    }

    if (error.status === 422 && error.errors) {
      this.editFieldErrors.set(error.errors);
      return;
    }

    this.editError.set(error.message);
  }

  private applyUpdated(updated: Parameter): void {
    this.parameters.update((items) =>
      items.map((item) => (item.id === updated.id ? updated : item)),
    );

    if (this.selected()?.id === updated.id) {
      this.selected.set(updated);
    }
  }

  private loadHistory(page: number): void {
    const target = this.historyTarget();

    if (target === null) {
      return;
    }

    this.historyLoading.set(true);
    this.historyError.set(null);

    this.service
      .history(target.id, page)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.historyEntries.set(response.data);
          this.historyPage.set(response.meta.current_page);
          this.historyLastPage.set(response.meta.last_page);
          this.historyTotal.set(response.meta.total);
          this.abilities.set(response.can);
          this.historyLoading.set(false);
        },
        error: (error: ParameterApiError) => {
          this.historyError.set(error.message);
          this.historyLoading.set(false);
        },
      });
  }
}
