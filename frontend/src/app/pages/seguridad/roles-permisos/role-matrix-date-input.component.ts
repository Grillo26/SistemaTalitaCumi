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
 * Selector de fecha (AAAA-MM-DD) con Flatpickr para consultar la vigencia
 * histórica de la matriz (02/RF-R14).
 */
@Component({
  selector: 'app-role-matrix-date-input',
  imports: [InputDirective],
  providers: [
    {
      provide: NG_VALUE_ACCESSOR,
      useExisting: forwardRef(() => RoleMatrixDateInputComponent),
      multi: true,
    },
  ],
  host: { class: 'block' },
  template: `
    <div class="relative">
      <input
        #dateInput
        app-input
        type="text"
        placeholder="dd/mm/aaaa"
        [disabled]="disabled"
        [attr.aria-label]="ariaLabel"
      />
      <span class="pointer-events-none absolute inset-y-0 end-3 flex items-center text-gray-400" aria-hidden="true">
        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6">
          <rect x="3.75" y="5.25" width="16.5" height="15" rx="2" />
          <path d="M7.5 3.25v4M16.5 3.25v4M3.75 9.25h16.5" stroke-linecap="round" />
        </svg>
      </span>
    </div>
  `,
})
export class RoleMatrixDateInputComponent
  implements AfterViewInit, OnDestroy, ControlValueAccessor
{
  @Input() ariaLabel = 'Fecha de vigencia';
  @ViewChild('dateInput', { static: true }) dateInput!: ElementRef<HTMLInputElement>;

  protected value = '';
  protected disabled = false;
  private picker: flatpickr.Instance | null = null;
  private pendingValue = '';

  private onChange: (value: string) => void = () => undefined;
  private onTouched: () => void = () => undefined;

  ngAfterViewInit(): void {
    this.picker = flatpickr(this.dateInput.nativeElement, {
      dateFormat: 'Y-m-d',
      maxDate: 'today',
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
