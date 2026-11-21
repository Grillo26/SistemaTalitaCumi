import { Component, DestroyRef, OnInit, inject, signal } from '@angular/core';
import { takeUntilDestroyed } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Router } from '@angular/router';
import { ToastService } from '../../core/services/toast.service';
import { PageBreadcrumbComponent } from '../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { ComponentCardComponent } from '../../shared/components/common/component-card/component-card.component';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { BadgeComponent } from '../../shared/components/ui/badge/badge.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import {
  PacientesApiError,
  TUTORES_TEXTS,
  Tutor,
  TutorOptions,
  TutorPaciente,
} from './pacientes.models';
import { PacientesService } from './pacientes.service';
import { TutorFormModalComponent } from './tutor-form-modal.component';
import { TutorStatusModalComponent } from './tutor-status-modal.component';

@Component({
  selector: 'app-tutor-ficha-page',
  imports: [
    PageBreadcrumbComponent,
    ComponentCardComponent,
    AlertComponent,
    BadgeComponent,
    ButtonComponent,
    TutorFormModalComponent,
    TutorStatusModalComponent,
  ],
  templateUrl: './tutor-ficha.page.html',
})
export class TutorFichaPageComponent implements OnInit {
  private readonly service = inject(PacientesService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  private readonly destroyRef = inject(DestroyRef);

  readonly texts = TUTORES_TEXTS;
  readonly tutor = signal<Tutor | null>(null);
  readonly options = signal<TutorOptions | null>(null);
  readonly loading = signal(true);
  readonly error = signal<PacientesApiError | null>(null);
  readonly notFound = signal(false);

  readonly editOpen = signal(false);
  readonly statusOpen = signal(false);

  private tutorId = 0;

  ngOnInit(): void {
    this.tutorId = Number(this.route.snapshot.paramMap.get('id'));
    this.loadOptions();
    this.load();
  }

  load(): void {
    this.loading.set(true);
    this.error.set(null);
    this.notFound.set(false);

    this.service
      .getTutor(this.tutorId)
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => {
          this.tutor.set(response.data);
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
      .getTutorOptions()
      .pipe(takeUntilDestroyed(this.destroyRef))
      .subscribe({
        next: (response) => this.options.set(response.data),
        error: () => undefined,
      });
  }

  back(): void {
    void this.router.navigate(['/pacientes/tutores']);
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

  onSaved(tutor: Tutor): void {
    this.tutor.set(tutor);
    this.editOpen.set(false);
    this.statusOpen.set(false);
    this.toast.success('Tutor actualizado', 'Los cambios quedaron registrados.');
  }

  openPatient(paciente: TutorPaciente): void {
    if (this.tutor()?.can.view_patients) {
      void this.router.navigate(['/pacientes', paciente.id]);
    }
  }

  estadoColor(activo: boolean): 'success' | 'gray' {
    return activo ? 'success' : 'gray';
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
