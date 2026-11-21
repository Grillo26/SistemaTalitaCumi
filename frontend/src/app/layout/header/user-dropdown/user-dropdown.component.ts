import {
  Component,
  ElementRef,
  HostListener,
  computed,
  inject,
} from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterModule } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';

@Component({
  selector: 'app-user-dropdown',
  templateUrl: './user-dropdown.component.html',
  imports: [ButtonComponent, CommonModule, RouterModule],
})
export class UserDropdownComponent {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly elementRef = inject(ElementRef<HTMLElement>);

  readonly user = this.auth.user;
  readonly canSeeConfiguration = computed(
    () =>
      this.auth.hasPermission('parametro.ver') ||
      this.auth.hasPermission('parametro.ver_operativos'),
  );
  isOpen = false;

  get initials(): string {
    const name = this.user()?.name?.trim();
    if (!name) return 'TC';

    return name
      .split(/\s+/)
      .slice(0, 2)
      .map((part) => part.charAt(0).toUpperCase())
      .join('');
  }

  toggleDropdown(event?: Event): void {
    event?.stopPropagation();
    this.isOpen = !this.isOpen;
  }

  closeDropdown(): void {
    this.isOpen = false;
  }

  async logout(): Promise<void> {
    await this.auth.logout();
    this.closeDropdown();
    await this.router.navigateByUrl('/login');
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    if (this.isOpen && !this.elementRef.nativeElement.contains(event.target)) {
      this.closeDropdown();
    }
  }
}
