import { Component, Input } from '@angular/core';
import { BadgeComponent } from '../../../shared/components/ui/badge/badge.component';
import { estadoColor } from './estados.models';

@Component({
  selector: 'app-estado-badge',
  imports: [BadgeComponent],
  host: { class: 'inline-flex' },
  template: `
    <app-badge [color]="color" variant="light" size="sm">{{ label }}</app-badge>
  `,
})
export class EstadoBadgeComponent {
  @Input({ required: true }) estado = '';
  @Input({ required: true }) label = '';

  get color(): 'success' | 'warning' | 'error' | 'info' | 'gray' {
    return estadoColor(this.estado);
  }
}
