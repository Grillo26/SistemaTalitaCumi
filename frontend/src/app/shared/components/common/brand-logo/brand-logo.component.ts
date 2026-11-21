import { Component, computed, input } from '@angular/core';

export type BrandLogoSize = 'sm' | 'md' | 'lg';

const FULL_HEIGHT: Record<BrandLogoSize, number> = {
  sm: 48,
  md: 72,
  lg: 104,
};

const ISOTYPE_HEIGHT: Record<BrandLogoSize, number> = {
  sm: 32,
  md: 40,
  lg: 52,
};

/**
 * Logo oficial de TalitaCumi.
 *
 * El logo completo tiene texto oscuro y el isotipo tiene colores vivos; ambos
 * se muestran sobre una tarjeta blanca para que se vean igual en modo claro,
 * modo oscuro y en el panel morado del login.
 *
 * <app-brand-logo />
 * <app-brand-logo size="lg" />
 * <app-brand-logo [compact]="true" />
 */
@Component({
  selector: 'app-brand-logo',
  host: { class: 'inline-flex shrink-0' },
  template: `
    @if (compact()) {
      <span
        class="inline-flex items-center justify-center rounded-xl bg-white p-1.5 shadow-theme-xs"
      >
        <img
          src="/images/logos/logo-talitacumi-isotipo-web.png"
          alt="TalitaCumi"
          [style.height.px]="isotypeHeight()"
          class="block w-auto"
        />
      </span>
    } @else {
      <span
        class="inline-flex items-center justify-center rounded-xl bg-white px-3 py-2 shadow-theme-xs"
      >
        <img
          src="/images/logos/logo-talitacumi-web.png"
          alt="TalitaCumi — Centro Integral de Desarrollo Infantil"
          [style.height.px]="fullHeight()"
          class="block w-auto"
        />
      </span>
    }
  `,
})
export class BrandLogoComponent {
  readonly compact = input(false);
  readonly size = input<BrandLogoSize>('md');

  readonly fullHeight = computed(() => FULL_HEIGHT[this.size()]);
  readonly isotypeHeight = computed(() => ISOTYPE_HEIGHT[this.size()]);
}
