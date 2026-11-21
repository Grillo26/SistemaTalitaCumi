import { A11yModule } from '@angular/cdk/a11y';
import { DOCUMENT } from '@angular/common';
import {
  Component,
  EventEmitter,
  HostListener,
  Inject,
  Input,
  OnChanges,
  OnDestroy,
  OnInit,
  Output,
  SimpleChanges,
} from '@angular/core';
import { ButtonComponent } from '../button/button.component';

export type ModalSize = 'sm' | 'md' | 'lg' | 'xl';

let nextModalId = 0;

/**
 * Modal compartido del sistema.
 *
 * Centra el diálogo, limita la altura al 90% del viewport y deja el cuerpo
 * con scroll propio. El header y el footer quedan fijos.
 *
 * Uso nuevo:
 *
 * ```html
 * <app-modal [open]="open()" title="Título" size="lg" (closed)="close()">
 *   <div modal-body>...</div>
 *   <div modal-footer>...</div>
 * </app-modal>
 * ```
 *
 * Para páginas con header propio se puede usar `[customHeader]="true"` y
 * marcar el header existente con `modal-header`.
 */
@Component({
  selector: 'app-modal',
  imports: [A11yModule, ButtonComponent],
  host: { class: 'contents' },
  template: `
    @if (open) {
      <div
        class="fixed inset-0 z-[99999] flex items-center justify-center overflow-y-auto bg-gray-900/60 p-4 sm:p-6"
        (click)="onBackdrop()"
      >
        <div
          class="my-8 flex max-h-[90vh] w-full flex-col overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900"
          [class.max-w-md]="size === 'sm'"
          [class.max-w-xl]="size === 'md'"
          [class.max-w-3xl]="size === 'lg'"
          [class.max-w-5xl]="size === 'xl'"
          role="dialog"
          aria-modal="true"
          [attr.aria-labelledby]="title ? titleId : null"
          [attr.aria-label]="title ? null : ariaLabel"
          cdkTrapFocus
          [cdkTrapFocusAutoCapture]="true"
          (click)="$event.stopPropagation()"
        >
          @if (customHeader) {
            <div class="shrink-0">
              <ng-content select="[modal-header]"></ng-content>
            </div>
          } @else if (showHeader) {
            <div
              class="flex shrink-0 items-start justify-between gap-4 border-b border-gray-100 p-6 pb-4 dark:border-gray-800"
            >
              <div class="min-w-0">
                @if (title) {
                  <h2
                    [id]="titleId"
                    class="text-lg font-semibold text-gray-800 dark:text-white/90"
                  >
                    {{ title }}
                  </h2>
                }
                @if (subtitle) {
                  <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ subtitle }}</p>
                }
                <ng-content select="[modal-header-extra]"></ng-content>
              </div>

              @if (showClose) {
                <button
                  app-button
                  variant="ghost"
                  size="icon-sm"
                  type="button"
                  [ariaLabel]="'Cerrar ' + (title || 'diálogo')"
                  (click)="close()"
                >
                  ✕
                </button>
              }
            </div>
          }

          <div
            class="min-h-0 flex-1 overflow-y-auto"
            [class.p-6]="padded"
            [class.pt-5]="padded"
          >
            <ng-content></ng-content>
            <ng-content select="[modal-body]"></ng-content>
          </div>

          @if (customFooter) {
            <div class="shrink-0">
              <ng-content select="[modal-footer]"></ng-content>
            </div>
          } @else if (showFooter) {
            <div
              class="shrink-0 border-t border-gray-100 p-6 pt-4 dark:border-gray-800"
            >
              <ng-content select="[modal-footer]"></ng-content>
            </div>
          }
        </div>
      </div>
    }
  `,
})
export class ModalComponent implements OnInit, OnChanges, OnDestroy {
  private static openCount = 0;

  private locked = false;

  readonly titleId = `app-modal-title-${++nextModalId}`;

  @Input() open = true;

  @Input() title = '';

  @Input() subtitle = '';

  @Input() size: ModalSize = 'md';

  @Input() ariaLabel = 'Diálogo';

  @Input() showHeader = true;

  @Input() showClose = true;

  @Input() customHeader = false;

  @Input() showFooter = false;

  @Input() customFooter = false;

  @Input() closeOnBackdrop = true;

  @Input() closeOnEscape = true;

  @Input() padded = true;

  @Output() closed = new EventEmitter<void>();

  constructor(@Inject(DOCUMENT) private readonly document: Document) {}

  ngOnInit(): void {
    this.syncScrollLock();
  }

  ngOnChanges(changes: SimpleChanges): void {
    if (changes['open']) {
      this.syncScrollLock();
    }
  }

  ngOnDestroy(): void {
    this.releaseScrollLock();
  }

  @HostListener('document:keydown.escape', ['$event'])
  onEscape(event: Event): void {
    if (!this.open || !this.closeOnEscape) {
      return;
    }

    event.preventDefault();
    this.close();
  }

  onBackdrop(): void {
    if (this.closeOnBackdrop) {
      this.close();
    }
  }

  close(): void {
    this.closed.emit();
  }

  private syncScrollLock(): void {
    if (this.open) {
      this.acquireScrollLock();
      return;
    }

    this.releaseScrollLock();
  }

  private acquireScrollLock(): void {
    if (this.locked) {
      return;
    }

    ModalComponent.openCount++;
    this.document.body.classList.add('overflow-hidden');
    this.locked = true;
  }

  private releaseScrollLock(): void {
    if (!this.locked) {
      return;
    }

    ModalComponent.openCount = Math.max(0, ModalComponent.openCount - 1);

    if (ModalComponent.openCount === 0) {
      this.document.body.classList.remove('overflow-hidden');
    }

    this.locked = false;
  }
}
