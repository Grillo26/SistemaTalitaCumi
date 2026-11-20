import { ChangeDetectionStrategy, Component, input } from '@angular/core';
import { RouterModule } from '@angular/router';
import { GridShapeComponent } from '../grid-shape/grid-shape.component';

/**
 * Full-width layout used by the 500/503, maintenance and success pages.
 *
 * Usage:
 *   <app-error-page-layout
 *     title="ERROR 500"
 *     message="..."
 *     imageLight="/images/error/500.svg"
 *     imageDark="/images/error/500-dark.svg"
 *   />
 */
@Component({
  selector: 'app-error-page-layout',
  standalone: true,
  imports: [RouterModule, GridShapeComponent],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="relative z-1 flex min-h-screen flex-col items-center justify-center overflow-hidden p-6">
      <app-grid-shape />

      <div class="mx-auto w-full max-w-[242px] text-center sm:max-w-[492px]">
        @if (titleAbove()) {
          <h1 class="mb-8 text-title-md font-bold text-gray-800 xl:text-title-2xl dark:text-white/90">
            {{ title() }}
          </h1>
        }

        <div [class]="imageWrapperClass()">
          <img [src]="imageLight()" [alt]="imageAlt()" class="w-full dark:hidden" />
          <img [src]="imageDark()" [alt]="imageAlt()" class="hidden w-full dark:block" />
        </div>

        @if (!titleAbove()) {
          <h1 class="mb-2 mt-10 text-title-md font-bold text-gray-800 xl:text-title-2xl dark:text-white/90">
            {{ title() }}
          </h1>
        }

        <p class="mb-6 mt-6 text-base text-gray-700 sm:text-lg dark:text-gray-400">
          {{ message() }}
        </p>

        <a
          routerLink="/"
          class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-3.5 text-sm font-medium text-gray-700 shadow-theme-xs transition-colors hover:bg-gray-50 hover:text-gray-800 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-white/[0.03] dark:hover:text-gray-200"
        >
          Volver al inicio
        </a>
      </div>

      <p class="absolute inset-x-0 bottom-6 text-center text-sm text-gray-500 dark:text-gray-400">
        &copy; {{ year }} - TalitaCumi
      </p>
    </div>
  `,
  styles: ``,
})
export class ErrorPageLayoutComponent {
  readonly title = input('ERROR');
  readonly message = input('');
  readonly imageLight = input('/images/error/500.svg');
  readonly imageDark = input('/images/error/500-dark.svg');
  readonly imageAlt = input('');
  readonly imageWrapperClass = input('mx-auto w-full');
  readonly titleAbove = input(true);

  readonly year = new Date().getFullYear();
}
