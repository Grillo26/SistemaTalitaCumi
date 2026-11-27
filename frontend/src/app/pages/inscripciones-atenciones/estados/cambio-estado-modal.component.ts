import { Component, EventEmitter, Input, OnInit, Output, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule, Validators } from '@angular/forms';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../../shared/components/ui/modal/modal.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { TextareaDirective } from '../../../shared/components/ui/textarea/textarea.directive';
import { InscripcionDateInputComponent } from '../salas-grupales/inscripcion-date-input.component';
import {
  ESTADOS_TEXTS,
  MotivoOption,
  PrechequeoEstado,
} from './estados.models';

export interface CambioEstadoModalResult {
  motivo: string | null;
  motivo_detalle: string | null;
  fecha_baja: string | null;
}

@Component({
  selector: 'app-cambio-estado-modal',
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    AlertComponent,
    ButtonComponent,
    SelectFieldComponent,
    TextareaDirective,
    InscripcionDateInputComponent,
  ],
  templateUrl: './cambio-estado-modal.component.html',
})
export class CambioEstadoModalComponent implements OnInit {
  private readonly fb = inject(FormBuilder);

  @Input({ required: true }) title = '';
  @Input() subtitle = '';
  @Input({ required: true }) actionLabel = '';
  @Input({ required: true }) currentStateLabel = '';
  @Input() motiveOptions: MotivoOption[] = [];
  @Input() requiresMotive = false;
  @Input() requiresDate = false;
  @Input() maxDate = '';
  @Input() precheck: PrechequeoEstado | null = null;
  @Input() saving = false;
  @Input() error: string | null = null;

  @Output() confirmed = new EventEmitter<CambioEstadoModalResult>();
  @Output() closed = new EventEmitter<void>();

  readonly texts = ESTADOS_TEXTS;
  readonly form = this.fb.nonNullable.group({
    motivo: [''],
    motivo_detalle: [''],
    fecha_baja: [''],
  });

  ngOnInit(): void {
    if (this.requiresDate) {
      this.form.controls.fecha_baja.setValue(this.maxDate);
    }
  }

  get otherSelected(): boolean {
    return this.form.controls.motivo.value === 'otro';
  }

  submit(): void {
    if (this.saving) {
      return;
    }

    const raw = this.form.getRawValue();

    if (this.requiresMotive && raw.motivo === '') {
      this.error = this.texts.reasonRequired;
      return;
    }

    if (this.otherSelected && raw.motivo_detalle.trim() === '') {
      this.error = this.texts.otherRequired;
      return;
    }

    if (this.requiresDate && raw.fecha_baja === '') {
      this.error = this.texts.date;
      return;
    }

    this.confirmed.emit({
      motivo: raw.motivo || null,
      motivo_detalle: raw.motivo_detalle.trim() || null,
      fecha_baja: this.requiresDate ? raw.fecha_baja : null,
    });
  }
}
