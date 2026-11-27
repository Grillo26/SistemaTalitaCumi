import { Component, EventEmitter, Input, OnInit, Output, inject } from '@angular/core';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { AlertComponent } from '../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';
import { ModalComponent } from '../../../shared/components/ui/modal/modal.component';
import { SelectFieldComponent } from '../../../shared/components/ui/select/select-field.component';
import { TextareaDirective } from '../../../shared/components/ui/textarea/textarea.directive';
import { InscripcionDateInputComponent } from '../salas-grupales/inscripcion-date-input.component';
import {
  ESTADOS_TEXTS,
  MotivoOption,
  PrechequeoPaciente,
  PreviewRetiroPaciente,
  TipoEntidad,
  formatDate,
} from './estados.models';

export interface RetiroPacienteModalResult {
  motivo: string;
  motivo_detalle: string | null;
  fecha_baja: string;
  inscripciones: Array<{
    tipo_entidad: TipoEntidad;
    id: number;
    estado_esperado: string;
  }>;
}

@Component({
  selector: 'app-retiro-paciente-modal',
  imports: [
    ReactiveFormsModule,
    ModalComponent,
    AlertComponent,
    ButtonComponent,
    SelectFieldComponent,
    TextareaDirective,
    InscripcionDateInputComponent,
  ],
  templateUrl: './retiro-paciente-modal.component.html',
})
export class RetiroPacienteModalComponent implements OnInit {
  private readonly fb = inject(FormBuilder);

  @Input({ required: true }) preview!: PreviewRetiroPaciente;
  @Input() precheck: PrechequeoPaciente | null = null;
  @Input() motiveOptions: MotivoOption[] = [];
  @Input() saving = false;
  @Input() error: string | null = null;
  @Input() maxDate = '';

  @Output() confirmed = new EventEmitter<RetiroPacienteModalResult>();
  @Output() closed = new EventEmitter<void>();

  readonly texts = ESTADOS_TEXTS;
  readonly formatDate = formatDate;
  readonly form = this.fb.nonNullable.group({
    motivo: [''],
    motivo_detalle: [''],
    fecha_baja: [''],
  });

  ngOnInit(): void {
    this.form.controls.fecha_baja.setValue(this.maxDate);
  }

  get otherSelected(): boolean {
    return this.form.controls.motivo.value === 'otro';
  }

  get turnosCount(): number {
    if (!this.precheck?.turnos.disponible) {
      return 0;
    }

    return this.precheck.turnos.detalle.reduce(
      (total, item) => total + Number(item.data?.['cantidad'] ?? 0),
      0,
    );
  }

  get saldoPendiente(): boolean {
    return (
      this.precheck?.pago.disponible === true &&
      this.precheck.pago.detalle.some((item) => Boolean(item.alerta))
    );
  }

  submit(): void {
    if (this.saving) {
      return;
    }

    const raw = this.form.getRawValue();

    if (raw.motivo === '') {
      this.error = this.texts.reasonRequired;
      return;
    }

    if (this.otherSelected && raw.motivo_detalle.trim() === '') {
      this.error = this.texts.otherRequired;
      return;
    }

    if (raw.fecha_baja === '') {
      this.error = this.texts.date;
      return;
    }

    this.confirmed.emit({
      motivo: raw.motivo,
      motivo_detalle: raw.motivo_detalle.trim() || null,
      fecha_baja: raw.fecha_baja,
      inscripciones: this.preview.inscripciones.map((item) => ({
        tipo_entidad: item.tipo_entidad,
        id: item.id,
        estado_esperado: item.estado_efectivo,
      })),
    });
  }
}
