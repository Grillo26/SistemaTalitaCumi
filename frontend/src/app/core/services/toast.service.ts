import {
  ApplicationRef,
  ComponentRef,
  EnvironmentInjector,
  Injectable,
  Injector,
  createComponent,
} from '@angular/core';
import { Subscription } from 'rxjs';
import {
  ToastComponent,
  ToastVariant,
} from '../../shared/components/ui/toast/toast.component';

interface ToastInstance {
  id: number;
  cmp: ComponentRef<ToastComponent>;
  timeout: ReturnType<typeof setTimeout>;
  closeSub: Subscription;
}

/**
 * Global toast service. Creates standalone `ToastComponent` instances
 * programmatically and stacks them in a fixed container.
 *
 * Usage:
 *   private readonly toast = inject(ToastService);
 *   this.toast.success('Saved', 'Changes stored successfully.');
 */
@Injectable({ providedIn: 'root' })
export class ToastService {
  private nextId = 1;
  private readonly instances = new Map<number, ToastInstance>();

  constructor(
    private readonly appRef: ApplicationRef,
    private readonly injector: Injector,
    private readonly envInjector: EnvironmentInjector,
  ) {}

  private createToast(
    variant: ToastVariant,
    title: string,
    message: string,
    duration = 3000,
  ): number {
    const id = this.nextId++;

    const cmpRef = createComponent(ToastComponent, {
      environmentInjector: this.envInjector,
      elementInjector: this.injector,
    });

    cmpRef.setInput('variant', variant);
    cmpRef.setInput('title', title);
    cmpRef.setInput('message', message);
    cmpRef.setInput('show', false);
    cmpRef.setInput('closing', false);

    const closeSub = cmpRef.instance.close.subscribe(() =>
      this.destroyToast(id),
    );

    this.appRef.attachView(cmpRef.hostView);

    const hostEl = cmpRef.location.nativeElement as HTMLElement;
    hostEl.style.pointerEvents = 'auto';
    this.getContainer().appendChild(hostEl);
    cmpRef.changeDetectorRef.detectChanges();

    // Trigger the enter animation on the next tick.
    setTimeout(() => cmpRef.setInput('show', true), 20);

    const timeout = setTimeout(() => {
      cmpRef.setInput('closing', true);
      setTimeout(() => this.destroyToast(id), 320);
    }, duration);

    this.instances.set(id, { id, cmp: cmpRef, timeout, closeSub });
    return id;
  }

  private getContainer(): HTMLElement {
    let container = document.getElementById('app-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'app-toast-container';
      container.style.position = 'fixed';
      container.style.insetInlineEnd = '1rem';
      container.style.bottom = '1rem';
      container.style.zIndex = '999999';
      container.style.display = 'flex';
      container.style.flexDirection = 'column';
      container.style.gap = '0.5rem';
      container.style.alignItems = 'flex-end';
      container.style.pointerEvents = 'none';
      document.body.appendChild(container);
    }
    return container;
  }

  private destroyToast(id: number): void {
    const instance = this.instances.get(id);
    if (!instance) return;

    clearTimeout(instance.timeout);
    instance.closeSub.unsubscribe();

    const hostEl = instance.cmp.location.nativeElement as HTMLElement;
    hostEl.parentNode?.removeChild(hostEl);

    this.appRef.detachView(instance.cmp.hostView);
    instance.cmp.destroy();
    this.instances.delete(id);
  }

  show(
    variant: ToastVariant,
    title: string,
    message: string,
    duration = 3000,
  ): void {
    this.createToast(variant, title, message, duration);
  }

  success(title: string, message: string, duration = 3000): void {
    this.show('success', title, message, duration);
  }

  warning(title: string, message: string, duration = 3000): void {
    this.show('warning', title, message, duration);
  }

  error(title: string, message: string, duration = 3000): void {
    this.show('error', title, message, duration);
  }

  info(title: string, message: string, duration = 3000): void {
    this.show('info', title, message, duration);
  }

  // Backwards-compatible aliases.
  showSuccess(title: string, message: string): void {
    this.success(title, message);
  }

  showError(title: string, message: string): void {
    this.error(title, message);
  }

  showWarning(title: string, message: string): void {
    this.warning(title, message);
  }

  showInfo(title: string, message: string): void {
    this.info(title, message);
  }
}
