import { Component, EventEmitter, Input, OnChanges, Output, SimpleChanges, computed, inject, signal } from '@angular/core';
import { FormsModule } from '@angular/forms';
import { ModalComponent } from '../../shared/components/ui/modal/modal.component';
import { ButtonComponent } from '../../shared/components/ui/button/button.component';
import { AlertComponent } from '../../shared/components/ui/alert/alert.component';
import { InputDirective } from '../../shared/components/ui/input/input.directive';
import { SelectFieldComponent } from '../../shared/components/ui/select/select-field.component';
import { TextareaDirective } from '../../shared/components/ui/textarea/textarea.directive';
import { InscripcionDateInputComponent } from '../inscripciones-atenciones/salas-grupales/inscripcion-date-input.component';
import { ToastService } from '../../core/services/toast.service';
import {
  MONTO_PATTERN,
  PagoApiError,
  PagoHistorial,
  PagoMedio,
  PagoRegistroLinea,
  PagoRegistroPayload,
  formatBs,
} from './pagos.models';
import { PagosService } from './pagos.service';

/**
 * Registro de pago reutilizable (08.01/RF-001, CU-01, CU-02, FA-01).
 *
 * Lo abren las fichas de 06 y el listado de /pagos. La clave de idempotencia se
 * genera al abrir y se reutiliza en reintentos; se regenera solo si el backend
 * indica que la clave ya se usó con otro contenido.
 */
@Component({
  selector: 'app-pago-registro-modal',
  imports: [
    FormsModule,
    ModalComponent,
    ButtonComponent,
    AlertComponent,
    InputDirective,
    SelectFieldComponent,
    TextareaDirective,
    InscripcionDateInputComponent,
  ],
  templateUrl: './pago-registro-modal.component.html',
})
export class PagoRegistroModalComponent implements OnChanges {
  private readonly pagos = inject(PagosService);
  private readonly toast = inject(ToastService);

  @Input() open = false;
  @Input({ required: true }) tipo!: string;
  @Input({ required: true }) entidadId!: number;
  @Input() puedeFutura = false;
  @Output() closed = new EventEmitter<void>();
  @Output() registered = new EventEmitter<void>();

  readonly historial = signal<PagoHistorial | null>(null);
  readonly historialError = signal<string | null>(null);
  readonly medios = signal<PagoMedio[]>([]);
  readonly submitting = signal(false);
  readonly formError = signal<string | null>(null);
  readonly fieldErrors = signal<Record<string, string>>({});
  readonly clave = signal<string>(crypto.randomUUID());

  readonly mediosOptions = computed(() =>
    this.medios().map((medio) => ({ value: medio.codigo, label: medio.nombre })),
  );

  readonly hoy = this.today();
  readonly formatBs = formatBs;

  lineas: { medio: string; monto: string; referencia: string }[] = [
    { medio: 'efectivo', monto: '', referencia: '' },
  ];
  fechaPago = '';
  motivoFuturo = '';

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['open'] && this.open) {
      this.clave.set(crypto.randomUUID());
      this.formError.set(null);
      this.fieldErrors.set({});
      this.cargarHistorial();
      this.cargarMedios();
    }
  }

  agregarLinea(): void {
    if (this.lineas.length < 2) {
      this.lineas.push({ medio: 'qr', monto: '', referencia: '' });
    }
  }

  quitarLinea(indice: number): void {
    if (this.lineas.length > 1) {
      this.lineas.splice(indice, 1);
    }
  }

  esFutura(): boolean {
    return this.fechaPago !== '' && this.fechaPago > this.hoy;
  }

  confirmar(): void {
    if (this.submitting()) {
      return;
    }

    const errores = this.validarLocal();
    this.fieldErrors.set(errores);
    this.formError.set(null);

    if (Object.keys(errores).length > 0) {
      return;
    }

    const payload: PagoRegistroPayload = {
      tipo_entidad: this.tipo,
      entidad_id: this.entidadId,
      clave_idempotencia: this.clave(),
      lineas: this.lineas.map((linea): PagoRegistroLinea => ({
        medio_pago: linea.medio,
        monto: linea.monto.trim(),
        ...(linea.medio === 'qr' ? { referencia: linea.referencia.trim() } : {}),
      })),
    };

    if (this.fechaPago !== '') {
      payload.fecha_pago = this.fechaPago;
    }

    if (this.esFutura()) {
      payload.motivo_fecha_futura = this.motivoFuturo.trim();
    }

    this.submitting.set(true);

    this.pagos.registrar(payload).subscribe({
      next: () => {
        this.submitting.set(false);
        this.toast.success('Pago registrado', 'El pago quedó registrado con su número correlativo.');
        this.registered.emit();
        this.closed.emit();
      },
      error: (error: PagoApiError) => {
        this.submitting.set(false);

        if (error.code === 'PAGO_IDEMPOTENCIA_CONFLICTO') {
          this.clave.set(crypto.randomUUID());
        }

        if (error.code === 'PAGO_SALDO_EXCEDIDO' && error.saldo_maximo) {
          this.formError.set(`El monto supera el saldo pendiente. El máximo permitido es ${formatBs(error.saldo_maximo)}.`);
          return;
        }

        if (error.errors) {
          this.fieldErrors.set(
            Object.fromEntries(Object.entries(error.errors).map(([campo, mensajes]) => [campo, mensajes[0]])),
          );
        }

        this.formError.set(error.message);
      },
    });
  }

  close(): void {
    if (!this.submitting()) {
      this.closed.emit();
    }
  }

  private cargarHistorial(): void {
    this.historialError.set(null);
    this.pagos.historial(this.tipo, this.entidadId).subscribe({
      next: (response) => this.historial.set(response.data),
      error: (error: PagoApiError) => this.historialError.set(error.message),
    });
  }

  private cargarMedios(): void {
    this.pagos.opciones().subscribe({
      next: (response) => this.medios.set(response.data.medios),
      error: () => this.medios.set([]),
    });
  }

  private validarLocal(): Record<string, string> {
    const errores: Record<string, string> = {};

    this.lineas.forEach((linea, indice) => {
      if (!MONTO_PATTERN.test(linea.monto.trim())) {
        errores[`lineas.${indice}.monto`] = 'Ingresá un monto mayor a cero, con hasta dos decimales.';
      }

      if (linea.medio === 'qr' && linea.referencia.trim() === '') {
        errores[`lineas.${indice}.referencia`] = 'El pago por QR requiere la referencia del comprobante bancario.';
      }
    });

    if (this.esFutura() && this.motivoFuturo.trim() === '') {
      errores['motivo_fecha_futura'] = 'Indicá el motivo de la fecha futura.';
    }

    return errores;
  }

  private today(): string {
    const ahora = new Date();
    const mes = String(ahora.getMonth() + 1).padStart(2, '0');
    const dia = String(ahora.getDate()).padStart(2, '0');

    return `${ahora.getFullYear()}-${mes}-${dia}`;
  }
}
