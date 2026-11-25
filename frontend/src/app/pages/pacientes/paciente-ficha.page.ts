import { Component, DestroyRef, OnInit, computed, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../core/services/auth.service';
import { ToastService } from '../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import {
  ContactoEmergencia,
  PACIENTES_TEXTS,
  Paciente,
  PacienteEstado,
  PacienteOptions,
  PacientesApiError,
  TutorResumen,
} from './pacientes.models';
import { PacientesService } from './pacientes.service';
import { ContactoEmergenciaModalComponent } from './contacto-emergencia-modal.component';
import { PacienteFormModalComponent } from './paciente-form-modal.component';
import { PacienteStatusModalComponent } from './paciente-status-modal.component';
import { PacienteTutorModalComponent } from './paciente-tutor-modal.component';

@Component({
  selector: 'app-paciente-ficha-page',
  imports: [
    PageBreadcrumbComponent,
    ComponentCardComponent,
    AlertComponent,
    BadgeComponent,
    ButtonComponent,
    PacienteFormModalComponent,
    PacienteStatusModalComponent,
    PacienteTutorModalComponent,
    ContactoEmergenciaModalComponent,
  ],
  templateUrl: './paciente-ficha.page.html',
})
export class PacienteFichaPageComponent implements OnInit {
  private readonly service = inject(PacientesService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly auth = inject(AuthService);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly canViewFichaIngreso = computed(() => this.auth.hasPermission('ficha_ingreso.ver'));

  readonly texts = PACIENTES_TEXTS;
  readonly paciente = signal<Paciente | null>(null);
  readonly options = signal<PacienteOptions | null>(null);
  readonly loading = signal(true);
  readonly error = signal<PacientesApiError | null>(null);
  readonly notFound = signal(false);

  readonly editOpen = signal(false);
  readonly statusOpen = signal(false);
  readonly tutorOpen = signal(false);
  readonly contactoOpen = signal(false);
  readonly contactoEditando = signal<ContactoEmergencia | null>(null);

  private pacienteId = 0;

  ngOnInit(): void {
    this.pacienteId = Number(this.route.snapshot.paramMap.get('id'));
    this.loadOptions();
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.error.set(null);
    this.notFound.set(false);

    this.service
      .getPaciente(this.pacienteId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.paciente.set(response.data);
          this.loading.set(false);
        },
        error: (apiError: PacientesApiError) => {
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

  loadOptions(): void {
    this.service
      .getOptions()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.options.set(response.data),
        error: () => undefined,
      });
  }

  back(): void {
    void this.router.navigate(['/pacientes']);
  }

  openEdit(): void {
    if (this.options() !== null) {
      this.editOpen.set(true);
    }
  }

  closeEdit(): void {
    this.editOpen.set(false);
  }

  openStatus(): void {
    this.statusOpen.set(true);
  }

  closeStatus(): void {
    this.statusOpen.set(false);
  }

  openResumen(): void {
    void this.router.navigate(['/pacientes', this.pacienteId, 'resumen']);
  }

  openFichaIngreso(): void {
    void this.router.navigate(['/pacientes', this.pacienteId, 'ficha-ingreso']);
  }

  openTutors(): void {
    this.tutorOpen.set(true);
  }

  closeTutors(): void {
    this.tutorOpen.set(false);
  }

  onSaved(paciente: Paciente): void {
    this.paciente.set(paciente);
    this.editOpen.set(false);
    this.statusOpen.set(false);
    this.toast.success('Ficha actualizada', 'Los cambios quedaron registrados.');
  }

  onTutorsChanged(): void {
    this.tutorOpen.set(false);
    this.toast.success('Tutores actualizados', 'La asociación quedó registrada.');
    this.load();
  }

  openNewContact(): void {
    this.contactoEditando.set(null);
    this.contactoOpen.set(true);
  }

  editContact(contacto: ContactoEmergencia): void {
    this.contactoEditando.set(contacto);
    this.contactoOpen.set(true);
  }

  closeContact(): void {
    this.contactoOpen.set(false);
    this.contactoEditando.set(null);
  }

  onContactSaved(): void {
    this.contactoOpen.set(false);
    this.contactoEditando.set(null);
    this.toast.success(this.texts.emergencyContactSaved, 'El contacto quedó registrado.');
    this.load();
  }

  removeContact(contacto: ContactoEmergencia): void {
    if (!window.confirm(`¿Eliminar el contacto de emergencia ${contacto.nombre_completo}?`)) {
      return;
    }

    this.service
      .deleteContactoEmergencia(this.pacienteId, contacto.id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.toast.success(this.texts.emergencyContactDeleted, 'La baja quedó registrada.');
          this.load();
        },
        error: (apiError: PacientesApiError) => this.error.set(apiError),
      });
  }

  makePrincipal(tutor: TutorResumen): void {
    const paciente = this.paciente();
    const principal = paciente?.tutores?.find((item) => item.es_principal);
    let motivo: string | null = null;

    if (principal && principal.tutor_id !== tutor.tutor_id) {
      const ingresado = window.prompt('Motivo del cambio de tutor principal');

      if (ingresado === null || ingresado.trim() === '') {
        return;
      }

      motivo = ingresado.trim();
    }

    this.service
      .setPrincipalTutor(this.pacienteId, tutor.tutor_id, motivo)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.toast.success('Tutor principal actualizado', 'La asociación quedó registrada.');
          this.load();
        },
        error: (apiError: PacientesApiError) => this.error.set(apiError),
      });
  }

  removeTutor(tutor: TutorResumen): void {
    if (!window.confirm(`¿Quitar a ${tutor.nombre_completo} de este paciente?`)) {
      return;
    }

    this.service
      .dissociateTutor(this.pacienteId, tutor.tutor_id)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: () => {
          this.toast.success('Tutor desasociado', 'La asociación quedó registrada.');
          this.load();
        },
        error: (apiError: PacientesApiError) => this.error.set(apiError),
      });
  }

  estadoColor(estado: PacienteEstado): 'success' | 'warning' | 'gray' {
    return estado === 'activo' ? 'success' : estado === 'en_pausa' ? 'warning' : 'gray';
  }

  formatDateOnly(value: string | null): string {
    if (!value) {
      return '—';
    }

    const [year, month, day] = value.slice(0, 10).split('-');

    return year && month && day ? `${day}/${month}/${year}` : value;
  }

  formatDateTime(value: string | null): string {
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
