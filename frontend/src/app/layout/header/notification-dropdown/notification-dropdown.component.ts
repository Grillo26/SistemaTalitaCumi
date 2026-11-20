import { CommonModule } from '@angular/common';
import { Component, ElementRef, HostListener, OnDestroy, OnInit } from '@angular/core';
import { RouterModule } from '@angular/router';
import { Subscription } from 'rxjs';
import { DropdownComponent } from '../../../shared/components/ui/dropdown/dropdown.component';
import { DropdownItemComponent } from '../../../shared/components/ui/dropdown/dropdown-item/dropdown-item.component';
import { DropdownService } from '../../../core/services/dropdown.service';
import { ButtonComponent } from '../../../shared/components/ui/button/button.component';

interface NotificationItem {
  id: string;
  name: string;
  avatar: string;
  action: string;
  project: string;
  time: string;
  status: 'online' | 'offline' | 'busy';
}

@Component({
  selector: 'app-notification-dropdown',
  templateUrl: './notification-dropdown.component.html',
  imports: [ButtonComponent, CommonModule, RouterModule, DropdownComponent, DropdownItemComponent]
})
export class NotificationDropdownComponent implements OnInit, OnDestroy {
  readonly dropdownId = 'notification-dropdown';
  isOpen = false;
  notifying = true;

  notifications: NotificationItem[] = [
    {
      id: '1',
      name: 'María López',
      avatar: '/images/user/user-02.jpg',
      action: 'confirmó el turno de',
      project: 'Sala Inicial',
      time: 'hace 5 min',
      status: 'online',
    },
    {
      id: '2',
      name: 'Carlos Rojas',
      avatar: '/images/user/user-03.jpg',
      action: 'registró el pago de',
      project: 'Inscripción 2026',
      time: 'hace 12 min',
      status: 'online',
    },
    {
      id: '3',
      name: 'Ana Gutiérrez',
      avatar: '/images/user/user-04.jpg',
      action: 'solicitó reprogramar',
      project: 'Turno de terapia',
      time: 'hace 25 min',
      status: 'online',
    },
    {
      id: '4',
      name: 'Luis Fernández',
      avatar: '/images/user/user-05.jpg',
      action: 'dejó un mensaje en',
      project: 'Recepción',
      time: 'hace 1 h',
      status: 'offline',
    },
  ];

  private sub?: Subscription;

  constructor(
    private elementRef: ElementRef,
    private dropdownService: DropdownService
  ) {}

  ngOnInit(): void {
    this.sub = this.dropdownService.activeDropdown$.subscribe((activeId) => {
      this.isOpen = activeId === this.dropdownId;
    });
  }

  ngOnDestroy(): void {
    this.sub?.unsubscribe();
  }

  toggleDropdown(event?: Event): void {
    event?.stopPropagation();
    this.dropdownService.toggle(this.dropdownId);
    this.notifying = false;
  }

  closeDropdown(): void {
    this.dropdownService.close(this.dropdownId);
  }

  @HostListener('document:click', ['$event'])
  onDocumentClick(event: MouseEvent): void {
    if (this.isOpen && !this.elementRef.nativeElement.contains(event.target)) {
      this.closeDropdown();
    }
  }
}