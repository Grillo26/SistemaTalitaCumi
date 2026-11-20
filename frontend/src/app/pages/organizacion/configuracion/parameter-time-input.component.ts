import {
  AfterViewInit,
  Component,
  ElementRef,
  Input,
  OnDestroy,
  ViewChild,
  forwardRef,
} from '@angular/core';
import { ControlValueAccessor, NG_VALUE_ACCESSOR } from '@angular/forms';
import flatpickr from 'flatpickr';
import { InputDirective } from '../../../shared/components/ui/input/input.directive';

/**
 * Campo de hora (HH:MM) con Flatpickr y soporte de formularios reactivos.
 * Se usa solo en la ficha de configuración.
 */
@Component({
  selector: 'app-parameter-time-input',
  imports: [InputDirective],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => ParameterTimeInputComponent),
      multi: true,
    },
  ],
  host: { class: 'block' },
  template: `
    <div class="relative">
      <input
        #timeInput
        app-input
        type="text"
        placeholder="08:00"
        [disabled]="disabled"
        [attr.aria-label]="ariaLabel"
      />
      <span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-gray-400" aria-hidden="true">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
          <circle cx="12" cy="12" r="8.25" />
          <path d="M12 7.5V12l3 1.75" stroke-linecap="round" />
        </svg>
      </span>
    </div>
  `,
})
export class ParameterTimeInputComponent
  implements AfterViewInit, OnDestroy, ControlValueAccessor
{
  @Input() ariaLabel = 'Hora';
  @ViewChild('timeInput', { static: true }) timeInput!: ElementRef<HTMLInputElement>;

  protected value = '';
  protected disabled = false;
  private picker: flatpickr.Instance | null = null;
  private pendingValue = '';

  private onChange: (value: string) => void = () => undefined;
  private onTouched: () => void = () => undefined;

  ngAfterViewInit(): void {
    this.picker = flatpickr(this.timeInput.nativeElement, {
      enableTime: true,
      noCalendar: true,
      dateFormat: 'H:i',
      time_24hr: true,
      defaultDate: this.value || this.pendingValue || undefined,
      onChange: (_dates, value) => {
        this.value = value;
        this.onChange(value);
        this.onTouched();
      },
    });
  }

  writeValue(value: string | null): void {
    this.value = value ?? '';
    this.pendingValue = this.value;

    if (this.picker !== null) {
      this.picker.setDate(this.value, false);
    }
  }

  registerOnChange(fn: (value: string) => void): void {
    this.onChange = fn;
  }

  registerOnTouched(fn: () => void): void {
    this.onTouched = fn;
  }

  setDisabledState(isDisabled: boolean): void {
    this.disabled = isDisabled;
  }

  ngOnDestroy(): void {
    this.picker?.destroy();
  }
}
