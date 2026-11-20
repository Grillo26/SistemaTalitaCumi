import { Component, inject } from '@angular/core';
import { ComponentCardComponent } from '../../../shared/components/common/component-card/component-card.component';
import { PageBreadcrumbComponent } from '../../../shared/components/common/page-breadcrumb/page-breadcrumb.component';
import { DefaultAlertComponent } from '../../../shared/components/ui/alert/default-alert/default-alert.component';
import { SuccessAlertComponent } from '../../../shared/components/ui/alert/success-alert/success-alert.component';
import { WarningAlertComponent } from '../../../shared/components/ui/alert/warning-alert/warning-alert.component';
import { ErrorAlertComponent } from '../../../shared/components/ui/alert/error-alert/error-alert.component';
import { InfoAlertComponent } from '../../../shared/components/ui/alert/info-alert/info-alert.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { ToastService } from '../../../core/services/toast.service';

@Component({
  selector: 'app-alerts',
  imports: [
    ComponentCardComponent,
    PageBreadcrumbComponent,
    DefaultAlertComponent,
    SuccessAlertComponent,
    WarningAlertComponent,
    ErrorAlertComponent,
    InfoAlertComponent,
    ButtonComponent,
  ],
  templateUrl: './alerts.component.html',
  styles: ``,
})
export class AlertsComponent {
  private readonly toast = inject(ToastService);

  showSuccess(): void {
    this.toast.success('Operación exitosa', 'Los cambios se guardaron correctamente.');
  }

  showInfo(): void {
    this.toast.info('Información', 'El turno fue reprogramado para mañana.');
  }

  showWarning(): void {
    this.toast.warning('Atención', 'La cuota del paciente está próxima a vencer.');
  }

  showError(): void {
    this.toast.error('Error', 'No se pudo completar la operación.');
  }
}
