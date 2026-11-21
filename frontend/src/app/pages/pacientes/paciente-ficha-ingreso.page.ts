import {
  Component,
  DestroyRef,
  OnInit,
  computed,
  inject,
  signal,
} from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastService } from '../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { InputDirective } from '../../shared/components/ui/input/input.directive';
import { SelectFieldComponent } from '../../shared/components/ui/select/select-field.component';
import { SwitchComponent } from '../../shared/components/ui/switch/switch.component';
import { TextareaDirective } from '../../shared/components/ui/textarea/textarea.directive';
import { PacienteDateInputComponent } from './paciente-date-input.component';
import {
  Consentimiento,
  ConsentimientoTipo,
  ConsentimientoTutorResumen,
  EMPTY_FICHA_INGRESO_ABILITIES,
  FichaIngreso,
  FichaIngresoAbilities,
  FichaIngresoApiError,
  FichaIngresoCreatePayload,
  FichaIngresoHistorial,
  FichaIngresoRevision,
  FICHA_INGRESO_TEXTS,
} from './ficha-ingreso.models';
import { FichaIngresoService } from './ficha-ingreso.service';
import { FichaIngresoEstadoModalComponent } from './ficha-ingreso-estado-modal.component';
import { FichaIngresoRevisionesModalComponent } from './ficha-ingreso-revisiones-modal.component';
import { ConsentimientoFormModalComponent } from './consentimiento-form-modal.component';
import { ConsentimientoRevokeModalComponent } from './consentimiento-revoke-modal.component';
import { ConsentimientoTiposModalComponent } from './consentimiento-tipos-modal.component';
import { PacientesService } from './pacientes.service';
import { Paciente, PacienteOptions } from './pacientes.models';

@Component({
  selector: 'app-paciente-ficha-ingreso-page',
  imports: [
    ReactiveFormsModule,
    PageBreadcrumbComponent,
    ComponentCardComponent,
    AlertComponent,
    BadgeComponent,
    ButtonComponent,
    InputDirective,
    SelectFieldComponent,
    SwitchComponent,
    TextareaDirective,
    PacienteDateInputComponent,
    FichaIngresoEstadoModalComponent,
    FichaIngresoRevisionesModalComponent,
    ConsentimientoFormModalComponent,
    ConsentimientoRevokeModalComponent,
    ConsentimientoTiposModalComponent,
  ],
  templateUrl: './paciente-ficha-ingreso.page.html',
})
export class PacienteFichaIngresoPageComponent implements OnInit {
  private readonly formBuilder = inject(FormBuilder);
  private readonly service = inject(FichaIngresoService);
  private readonly pacientes = inject(PacientesService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = FICHA_INGRESO_TEXTS;
  readonly form = this.formBuilder.nonNullable.group({
    fecha_ingreso: ['', [Validators.required]],
    motivo_consulta_inicial: ['', [Validators.required, Validators.maxLength(2000)]],
    sucursal_ingreso_id: [''],
    composicion_familiar: [''],
    antecedentes_familiares: [''],
    observaciones: [''],
    derivado_por: [''],
    derivado_por_institucion: [''],
    derivado_por_contacto: [''],
  });

  readonly ficha = signal<FichaIngreso | null>(null);
  readonly paciente = signal<Paciente | null>(null);
  readonly historial = signal<FichaIngresoHistorial[]>([]);
  readonly can = signal<FichaIngresoAbilities>(EMPTY_FICHA_INGRESO_ABILITIES);
  readonly loading = signal(true);
  readonly saving = signal(false);
  readonly error = signal<FichaIngresoApiError | null>(null);
  readonly notFound = signal(false);
  readonly mostrarSensibles = signal(false);
  readonly estadoModalOpen = signal(false);
  readonly estadoAccion = signal<'archivar' | 'reactivar'>('archivar');
  readonly revisionesOpen = signal(false);
  readonly revisionesLoading = signal(false);
  readonly revisiones = signal<FichaIngresoRevision[]>([]);
  readonly sucursalOptions = signal<{ value: string; label: string }[]>([]);
  readonly consentimientosVigentes = signal<Consentimiento[]>([]);
  readonly consentimientosHistorial = signal<Consentimiento[]>([]);
  readonly consentimientoTipos = signal<ConsentimientoTipo[]>([]);
  readonly consentimientoTutores = signal<ConsentimientoTutorResumen[]>([]);
  readonly consentimientosLoading = signal(false);
  readonly consentimientoFormOpen = signal(false);
  readonly consentimientoRevokeOpen = signal(false);
  readonly consentimientoSelected = signal<Consentimiento | null>(null);
  readonly consentimientoTiposOpen = signal(false);

  readonly canEdit = computed(() => this.ficha()?.can.edit ?? this.can().create);
  readonly canCreate = computed(() => this.can().create);
  readonly canViewSensitive = computed(
    () => this.ficha()?.can.view_sensibles ?? this.can().view_sensibles,
  );
  readonly sensitiveVisible = computed(
    () => this.canViewSensitive() && this.mostrarSensibles(),
  );
  readonly isArchived = computed(() => this.ficha()?.estado === 'archivada');
  readonly canRegisterConsent = computed(
    () =>
      (this.ficha()?.can.consent_register ?? this.can().consent_register) &&
      this.consentimientoTutores().length > 0,
  );
  readonly canRevokeConsent = computed(
    () => this.ficha()?.can.consent_revoke ?? this.can().consent_revoke,
  );
  readonly canManageConsentCatalog = computed(
    () => this.ficha()?.can.consent_catalog ?? this.can().consent_catalog,
  );
  readonly hasActiveConsentTutors = computed(
    () => this.consentimientoTutores().length > 0,
  );

  private pacienteId = 0;

  ngOnInit(): void {
    this.pacienteId = Number(this.route.snapshot.paramMap.get('id'));
    this.loadBranches();
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.error.set(null);
    this.notFound.set(false);

    this.service
      .get(this.pacienteId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.aplicarRespuesta(response.data, response.paciente, response.historial, response.can);
          this.loading.set(false);

          if (response.data !== null && response.data.can.consent_view) {
            this.loadConsentimientos(response.data.id);
          }
        },
        error: (apiError: FichaIngresoApiError) => {
          this.loading.set(false);

          if (apiError.status === 404) {
            this.notFound.set(true);
            return;
          }

          if (apiError.status === 503 && apiError.code === 'AUDIT_UNAVAILABLE') {
            this.error.set({ ...apiError, message: this.texts.auditUnavailable });
            return;
          }

          this.error.set(apiError);
        },
      });
  }

  loadBranches(): void {
    this.pacientes
      .getOptions()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response: { data: PacienteOptions }) => {
          this.sucursalOptions.set(
            response.data.sucursales.map((sucursal) => ({
              value: String(sucursal.id),
              label: sucursal.nombre,
            })),
          );
          this.ensureBranchOption();
        },
        error: () => this.ensureBranchOption(),
      });
  }

  save(): void {
    if (!this.canEdit() || this.saving()) {
      return;
    }

    this.form.markAllAsTouched();

    if (this.form.invalid) {
      this.error.set({
        status: 422,
        message: this.texts.requiredFields,
        errors: {
          motivo_consulta_inicial: [this.texts.motive],
          fecha_ingreso: [this.texts.date],
        },
      });
      return;
    }

    const value = this.form.getRawValue();
    const ficha = this.ficha();
    const creating = ficha === null;
    const payload = this.payload(value);

    this.saving.set(true);
    this.error.set(null);

    const request = ficha
      ? this.service.update(this.pacienteId, ficha.id, {
          motivo_consulta_inicial: payload.motivo_consulta_inicial,
          fecha_ingreso: payload.fecha_ingreso,
          composicion_familiar: payload.composicion_familiar,
          antecedentes_familiares: payload.antecedentes_familiares,
          observaciones: payload.observaciones,
          derivado_por: payload.derivado_por,
          derivado_por_institucion: payload.derivado_por_institucion,
          derivado_por_contacto: payload.derivado_por_contacto,
          version: ficha.version,
        })
      : this.service.create(this.pacienteId, payload as FichaIngresoCreatePayload);

    request.pipe(takeUntilDestroyed(this.destroyRef)).subscribe({
      next: (response) => {
        this.saving.set(false);
        this.aplicarRespuesta(response.data, this.paciente(), this.historial(), response.can);

        if (creating && response.data !== null && response.data.can.consent_view) {
          this.loadConsentimientos(response.data.id);
        }

        this.toast.success(this.texts.saved, 'Los cambios quedaron registrados.');
      },
      error: (apiError: FichaIngresoApiError) => this.handleError(apiError),
    });
  }

  complete(): void {
    const ficha = this.ficha();

    if (ficha === null || !ficha.can.complete || this.saving()) {
      return;
    }

    this.saving.set(true);
    this.error.set(null);

    this.service
      .complete(this.pacienteId, ficha.id, ficha.version)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.saving.set(false);
          this.aplicarRespuesta(response.data, this.paciente(), this.historial(), response.can);
          this.toast.success(this.texts.completed, 'La ficha quedó completa.');
        },
        error: (apiError: FichaIngresoApiError) => this.handleError(apiError),
      });
  }

  openArchive(): void {
    if (this.ficha() === null || !this.ficha()!.can.archive) {
      return;
    }

    this.estadoAccion.set('archivar');
    this.estadoModalOpen.set(true);
  }

  openReactivate(): void {
    if (this.ficha() === null || !this.ficha()!.can.reactivate) {
      return;
    }

    this.estadoAccion.set('reactivar');
    this.estadoModalOpen.set(true);
  }

  closeEstadoModal(): void {
    this.estadoModalOpen.set(false);
  }

  onEstadoSaved(ficha: FichaIngreso): void {
    this.estadoModalOpen.set(false);

    if (this.estadoAccion() === 'archivar') {
      this.toast.success(this.texts.archivedToast, 'La ficha anterior quedó en el historial.');
      this.load();
      return;
    }

    this.ficha.set(ficha);
    this.historial.update((items) => items.filter((item) => item.id !== ficha.id));
    this.fillForm(ficha);
    this.form.enable({ emitEvent: false });

    if (ficha.can.consent_view) {
      this.loadConsentimientos(ficha.id);
    }

    this.toast.success(this.texts.reactivatedToast, 'La ficha volvió a estar vigente.');
  }

  openRevisions(): void {
    const ficha = this.ficha();

    if (ficha === null) {
      return;
    }

    this.revisionesOpen.set(true);
    this.revisionesLoading.set(true);
    this.revisiones.set([]);

    this.service
      .revisions(this.pacienteId, ficha.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.revisiones.set(response.data);
          this.revisionesLoading.set(false);
        },
        error: (apiError: FichaIngresoApiError) => {
          this.revisionesLoading.set(false);
          this.error.set(apiError);
        },
      });
  }

  closeRevisions(): void {
    this.revisionesOpen.set(false);
  }

  loadConsentimientos(fichaId: number): void {
    this.consentimientosLoading.set(true);

    this.service
      .consentimientos(this.pacienteId, fichaId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.consentimientosVigentes.set(response.data.vigentes);
          this.consentimientosHistorial.set(response.data.historial);
          this.consentimientoTipos.set(response.tipos);
          this.consentimientoTutores.set(response.tutores);
          this.consentimientosLoading.set(false);
        },
        error: (apiError: FichaIngresoApiError) => {
          this.consentimientosLoading.set(false);

          if (apiError.status !== 404) {
            this.error.set(apiError);
          }
        },
      });
  }

  openConsentForm(): void {
    if (this.canRegisterConsent()) {
      this.consentimientoFormOpen.set(true);
    }
  }

  closeConsentForm(): void {
    this.consentimientoFormOpen.set(false);
  }

  onConsentSaved(consentimiento: Consentimiento): void {
    this.consentimientoFormOpen.set(false);
    const ficha = this.ficha();

    if (ficha !== null) {
      this.loadConsentimientos(ficha.id);
    }

    this.toast.success(
      this.texts.consentSaved,
      `${consentimiento.tipo?.nombre ?? 'Consentimiento'} · ${consentimiento.resultado_label}`,
    );
  }

  openConsentRevoke(consentimiento: Consentimiento): void {
    if (!this.canRevokeConsent()) {
      return;
    }

    this.consentimientoSelected.set(consentimiento);
    this.consentimientoRevokeOpen.set(true);
  }

  closeConsentRevoke(): void {
    this.consentimientoRevokeOpen.set(false);
  }

  onConsentRevoked(consentimiento: Consentimiento): void {
    this.consentimientoRevokeOpen.set(false);
    this.consentimientoSelected.set(null);
    const ficha = this.ficha();

    if (ficha !== null) {
      this.loadConsentimientos(ficha.id);
    }

    this.toast.success(
      this.texts.consentRevokedToast,
      consentimiento.tipo?.nombre ?? 'Consentimiento',
    );
  }

  openConsentTipos(): void {
    if (this.canManageConsentCatalog()) {
      this.consentimientoTiposOpen.set(true);
    }
  }

  closeConsentTipos(): void {
    this.consentimientoTiposOpen.set(false);
  }

  onConsentTiposChanged(): void {
    const ficha = this.ficha();

    if (ficha !== null) {
      this.loadConsentimientos(ficha.id);
    }
  }

  consentResultColor(resultado: Consentimiento['resultado']): 'success' | 'warning' | 'gray' {
    return resultado === 'otorgado' ? 'success' : resultado === 'negado' ? 'warning' : 'gray';
  }

  openArchived(fichaId: number): void {
    this.service
      .getById(this.pacienteId, fichaId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.ficha.set(response.data);
          this.fillForm(response.data);

          if (response.data.can.consent_view) {
            this.loadConsentimientos(response.data.id);
          } else {
            this.clearConsentimientos();
          }

          if (this.canEdit()) {
            this.form.enable({ emitEvent: false });
          } else {
            this.form.disable({ emitEvent: false });
          }
        },
        error: (apiError: FichaIngresoApiError) => this.error.set(apiError),
      });
  }

  openPatientContacts(): void {
    void this.router.navigate(['/pacientes', this.pacienteId]);
  }

  back(): void {
    void this.router.navigate(['/pacientes', this.pacienteId]);
  }

  toggleSensibles(): void {
    if (this.canViewSensitive()) {
      this.mostrarSensibles.update((value) => !value);
    }
  }

  estadoColor(estado: FichaIngreso['estado']): 'success' | 'warning' | 'gray' {
    return estado === 'completa' ? 'success' : estado === 'borrador' ? 'warning' : 'gray';
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

  fieldError(field: string): string | null {
    return this.error()?.errors?.[field]?.[0] ?? null;
  }

  private aplicarRespuesta(
    ficha: FichaIngreso | null,
    paciente: Paciente | null,
    historial: FichaIngresoHistorial[],
    can: FichaIngresoAbilities,
  ): void {
    this.ficha.set(ficha);
    this.paciente.set(paciente ?? this.paciente());
    this.historial.set(historial);
    this.can.set(can);

    if (ficha === null) {
      this.clearConsentimientos();
    }

    this.ensureBranchOption();
    this.fillForm(ficha);

    if (this.canEdit()) {
      this.form.enable({ emitEvent: false });
    } else {
      this.form.disable({ emitEvent: false });
    }
  }

  private clearConsentimientos(): void {
    this.consentimientosVigentes.set([]);
    this.consentimientosHistorial.set([]);
    this.consentimientoTipos.set([]);
    this.consentimientoTutores.set([]);
    this.consentimientoFormOpen.set(false);
    this.consentimientoRevokeOpen.set(false);
    this.consentimientoSelected.set(null);
  }

  private fillForm(ficha: FichaIngreso | null): void {
    if (ficha !== null) {
      this.form.reset({
        fecha_ingreso: ficha.fecha_ingreso,
        motivo_consulta_inicial: ficha.motivo_consulta_inicial ?? '',
        sucursal_ingreso_id: ficha.sucursal_ingreso ? String(ficha.sucursal_ingreso.id) : '',
        composicion_familiar: ficha.composicion_familiar ?? '',
        antecedentes_familiares: ficha.antecedentes_familiares ?? '',
        observaciones: ficha.observaciones ?? '',
        derivado_por: ficha.derivado_por ?? '',
        derivado_por_institucion: ficha.derivado_por_institucion ?? '',
        derivado_por_contacto: ficha.derivado_por_contacto ?? '',
      });
      return;
    }

    this.form.reset({
      fecha_ingreso: new Date().toISOString().slice(0, 10),
      motivo_consulta_inicial: '',
      sucursal_ingreso_id: this.paciente()?.sucursal_registro.id
        ? String(this.paciente()!.sucursal_registro.id)
        : '',
      composicion_familiar: '',
      antecedentes_familiares: '',
      observaciones: '',
      derivado_por: '',
      derivado_por_institucion: '',
      derivado_por_contacto: '',
    });
  }

  private ensureBranchOption(): void {
    const branch = this.paciente()?.sucursal_registro;

    if (branch === undefined) {
      return;
    }

    const options = this.sucursalOptions();

    if (!options.some((option) => option.value === String(branch.id))) {
      this.sucursalOptions.set([
        { value: String(branch.id), label: branch.nombre || `Sucursal ${branch.id}` },
        ...options,
      ]);
    }

    if (this.form.controls.sucursal_ingreso_id.value === '') {
      this.form.controls.sucursal_ingreso_id.setValue(String(branch.id), { emitEvent: false });
    }
  }

  private payload(
    value: ReturnType<PacienteFichaIngresoPageComponent['form']['getRawValue']>,
  ): Partial<FichaIngresoCreatePayload> {
    const payload: Partial<FichaIngresoCreatePayload> = {
      motivo_consulta_inicial: value.motivo_consulta_inicial.trim(),
      fecha_ingreso: value.fecha_ingreso,
      sucursal_ingreso_id: Number(value.sucursal_ingreso_id) || this.paciente()!.sucursal_registro.id,
      composicion_familiar: value.composicion_familiar.trim() || null,
      antecedentes_familiares: value.antecedentes_familiares.trim() || null,
      observaciones: value.observaciones.trim() || null,
      derivado_por: value.derivado_por.trim() || null,
      derivado_por_institucion: value.derivado_por_institucion.trim() || null,
      derivado_por_contacto: value.derivado_por_contacto.trim() || null,
    };

    if (!this.canViewSensitive()) {
      delete payload.motivo_consulta_inicial;
      delete payload.composicion_familiar;
      delete payload.antecedentes_familiares;
      delete payload.observaciones;
      delete payload.derivado_por_contacto;
    }

    return payload;
  }

  private handleError(apiError: FichaIngresoApiError): void {
    this.saving.set(false);

    if (apiError.code === 'FICHA_VERSION_CONFLICT') {
      this.error.set({ ...apiError, message: this.texts.versionConflict });
      return;
    }

    if (apiError.status === 503 && apiError.code === 'AUDIT_UNAVAILABLE') {
      this.error.set({ ...apiError, message: this.texts.auditUnavailable });
      return;
    }

    this.error.set(apiError);
  }
}
