import { Component, EventEmitter, Input, Output } from '@angular/core';
import { JsonPipe } from '@angular/common';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../shared/components/ui/modal/modal.component';
import {
  FICHA_INGRESO_TEXTS,
  FichaIngresoRevision,
} from './ficha-ingreso.models';

@Component({
  selector: 'app-ficha-ingreso-revisiones-modal',
  imports: [JsonPipe, ModalComponent, AlertComponent, ButtonComponent],
  templateUrl: './ficha-ingreso-revisiones-modal.component.html',
})
export class FichaIngresoRevisionesModalComponent {
  @Input({ required: true }) open = false;
  @Input({ required: true }) revisions: FichaIngresoRevision[] = [];
  @Input({ required: true }) loading = false;

  @Output() closed = new EventEmitter<void>();

  readonly texts = FICHA_INGRESO_TEXTS;

  close(): void {
    this.closed.emit();
  }
}
