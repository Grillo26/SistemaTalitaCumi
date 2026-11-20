import { CommonModule } from '@angular/common';
import { Component, Input } from '@angular/core';
import { RouterModule } from '@angular/router';
import { SignInFormFieldsComponent } from './form.component';
import { SignInHeaderComponent } from './header.component';
import { BrandLogoComponent } from '../../../common/brand-logo/brand-logo.component';

@Component({
  selector: 'app-sign-in-content',
  imports: [
    CommonModule,
    RouterModule,
    SignInHeaderComponent,
    SignInFormFieldsComponent,
    BrandLogoComponent,
  ],
  templateUrl: './sign-in.component.html',
  host: {
    class: 'contents',
  },
  styles: [
    `
      :host {
        display: contents;
      }
    `,
  ],
})
export class SignInContentComponent {
  @Input() className: string = '';
}
