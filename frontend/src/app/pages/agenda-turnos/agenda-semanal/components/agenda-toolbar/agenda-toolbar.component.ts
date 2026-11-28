import { Component, EventEmitter, Input, Output } from '@angular/core';
import { ButtonComponent } from '../../../../../shared/components/ui/button/button.component';
import { SelectFieldComponent } from '../../../../../shared/components/ui/select/select-field.component';
import { AGENDA_TEXTS, VISTA_OPTIONS } from '../../agenda-semanal.constants';
import type { AgendaVista, SucursalDisponible } from '../../agenda-semanal.models';

@Component({
  selector: 'app-agenda-toolbar',
  imports: [ButtonComponent, SelectFieldComponent],
  host: { class: 'block' },
  template: `
    <div class="flex flex-col gap-4">
      <div class="flex flex-col gap-3 xl:flex-row xl:items-end xl:justify-between">
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
          <div class="w-full sm:w-64">
            <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">
              {{ texts.branchLabel }}
            </label>
            <app-select-field
              [options]="sucursalOptions"
              [value]="sucursalValue"
              [placeholder]="texts.branchLabel"
              ariaLabel="Seleccionar sucursal"
              (valueChange)="onSucursalChange($event)"
            />
          </div>

          <div class="flex items-center gap-2">
            <button
              app-button
              variant="outline"
              size="icon-sm"
              type="button"
              [disabled]="loading"
              [ariaLabel]="texts.weekPrevious"
              (click)="previous.emit()"
            >
              ‹
            </button>
            <div class="min-w-48 text-center">
              <p class="text-sm font-semibold text-gray-800 dark:text-white/90">
                {{ weekLabel }}
              </p>
              @if (weekSubtitle) {
                <p class="text-[11px] text-gray-500 dark:text-gray-400">{{ weekSubtitle }}</p>
              }
            </div>
            <button
              app-button
              variant="outline"
              size="icon-sm"
              type="button"
              [disabled]="loading"
              [ariaLabel]="texts.weekNext"
              (click)="next.emit()"
            >
              ›
            </button>
            <button
              app-button
              variant="outline"
              size="sm"
              type="button"
              [disabled]="loading"
              (click)="today.emit()"
            >
              {{ texts.today }}
            </button>
          </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
          <button
            app-button
            variant="ghost"
            size="sm"
            type="button"
            [disabled]="loading"
            (click)="refresh.emit()"
          >
            {{ texts.refreshNow }}
          </button>

          @if (canExportar) {
            <button
              app-button
              variant="outline"
              size="sm"
              type="button"
              [disabled]="loading || exporting"
              (click)="exportar.emit()"
            >
              {{ texts.exportPdf }}
            </button>
          }

          <button app-button variant="outline" size="sm" type="button" (click)="imprimir.emit()">
            {{ texts.print }}
          </button>
        </div>
      </div>

      <div class="flex flex-wrap items-center gap-2" role="tablist" [attr.aria-label]="texts.view">
        @for (option of vistaOptions; track option.value) {
          <button
            app-button
            type="button"
            size="sm"
            [variant]="vista === option.value ? 'default' : 'outline'"
            [attr.aria-pressed]="vista === option.value"
            role="tab"
            [attr.aria-selected]="vista === option.value"
            (click)="vistaChange.emit(option.value)"
          >
            {{ option.label }}
          </button>
        }
      </div>
    </div>
  `,
})
export class AgendaToolbarComponent {
  @Input() sucursales: SucursalDisponible[] = [];
  @Input() sucursalId: number | null = null;
  @Input() weekLabel = '';
  @Input() weekSubtitle = '';
  @Input() vista: AgendaVista = 'semana';
  @Input() canExportar = false;
  @Input() loading = false;
  @Input() exporting = false;

  @Output() sucursalChange = new EventEmitter<number>();
  @Output() previous = new EventEmitter<void>();
  @Output() next = new EventEmitter<void>();
  @Output() today = new EventEmitter<void>();
  @Output() vistaChange = new EventEmitter<AgendaVista>();
  @Output() exportar = new EventEmitter<void>();
  @Output() imprimir = new EventEmitter<void>();
  @Output() refresh = new EventEmitter<void>();

  readonly texts = AGENDA_TEXTS;
  readonly vistaOptions = VISTA_OPTIONS;

  get sucursalOptions(): { value: string; label: string }[] {
    return this.sucursales.map((sucursal) => ({
      value: String(sucursal.id),
      label: sucursal.nombre,
    }));
  }

  get sucursalValue(): string | undefined {
    return this.sucursalId === null ? undefined : String(this.sucursalId);
  }

  onSucursalChange(value: string): void {
    const parsed = Number(value);

    if (Number.isInteger(parsed) && parsed > 0) {
      this.sucursalChange.emit(parsed);
    }
  }
}
