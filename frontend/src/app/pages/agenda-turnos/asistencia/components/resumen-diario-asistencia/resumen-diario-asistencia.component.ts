import { Component, OnInit, computed, inject, signal } from '@angular/core';
import { AuthService } from '../../../../../core/services/auth.service';
import { AlertComponent } from '../../../../../shared/components/ui/alert/alert.component';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { InputDirective } from '../../../../../shared/components/ui/input/input.directive';
import { SelectFieldComponent } from '../../../../../shared/components/ui/select/select-field.component';
import {
  ASISTENCIA_TEXTS,
  fechaAsistencia,
  type AsistenciaApiError,
  type AsistenciaResumenDiarioData,
} from '../../../asistencia-turno/asistencia-turno.models';
import { AsistenciaTurnoService } from '../../../asistencia-turno/asistencia-turno.service';

@Component({
  selector: 'app-resumen-diario-asistencia',
  imports: [AlertComponent, ButtonComponent, InputDirective, SelectFieldComponent],
  templateUrl: './resumen-diario-asistencia.component.html',
})
export class ResumenDiarioAsistenciaComponent implements OnInit {
  private readonly service = inject(AsistenciaTurnoService);
  private readonly auth = inject(AuthService);

  readonly texts = ASISTENCIA_TEXTS;
  readonly fechaLegible = fechaAsistencia;

  readonly fecha = signal(this.today());
  readonly sucursalId = signal('');
  readonly data = signal<AsistenciaResumenDiarioData | null>(null);
  readonly loading = signal(false);
  readonly error = signal<string | null>(null);

  readonly sucursalOpciones = computed(() =>
    (this.auth.user()?.branches ?? []).map((branch) => ({
      value: String(branch.id),
      label: branch.nombre,
    })),
  );

  ngOnInit(): void {
    const first = this.auth.user()?.branches?.[0];

    if (first !== undefined) {
      this.sucursalId.set(String(first.id));
      this.cargar();
    }
  }

  cargar(): void {
    if (this.sucursalId() === '') {
      this.error.set('Seleccioná una sucursal.');
      return;
    }

    this.loading.set(true);
    this.error.set(null);

    this.service.getResumenDiario({
      fecha: this.fecha(),
      sucursal_id: Number(this.sucursalId()),
    }).subscribe({
      next: (response) => {
        this.loading.set(false);
        this.data.set(response.data);
      },
      error: (error: AsistenciaApiError) => {
        this.loading.set(false);
        this.error.set(error.message);
      },
    });
  }

  private today(): string {
    const now = new Date();
    const mes = String(now.getMonth() + 1).padStart(2, '0');
    const dia = String(now.getDate()).padStart(2, '0');

    return `${now.getFullYear()}-${mes}-${dia}`;
  }
}
