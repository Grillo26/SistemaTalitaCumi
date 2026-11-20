import { Component } from '@angular/core';
import { PaginationComponent } from '../../../../../shared/components/ui/pagination/pagination.component';

interface PatientRow {
  id: number;
  name: string;
  image: string;
  room: string;
  location: string;
  age: number;
  admissionDate: string;
  fee: number;
}

type SortKey = 'name' | 'room' | 'location' | 'age' | 'admissionDate' | 'fee';
type SortDirection = 'asc' | 'desc';

@Component({
  selector: 'app-data-table-one',
  standalone: true,
  imports: [PaginationComponent],
  template: `
    <div
      class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]"
    >
      <div class="max-w-full overflow-x-auto">
        <table class="w-full min-w-[900px]">
          <thead>
            <tr class="border-b border-gray-200 dark:border-gray-800">
              <th class="px-6 py-3 text-start">
                <input
                  type="checkbox"
                  class="size-5 cursor-pointer rounded border-gray-300 accent-brand-500 dark:border-gray-700"
                  [checked]="allSelected"
                  (change)="toggleAll($event)"
                  aria-label="Seleccionar página"
                />
              </th>
              @for (column of columns; track column.key) {
                <th class="px-6 py-3 text-start">
                  <button
                    type="button"
                    class="inline-flex items-center gap-1.5 text-theme-sm font-medium text-gray-500 transition-colors hover:text-brand-600 dark:text-gray-400 dark:hover:text-brand-300"
                    (click)="sortBy(column.key)"
                    [attr.aria-sort]="
                      sortKey === column.key
                        ? sortDirection === 'asc'
                          ? 'ascending'
                          : 'descending'
                        : 'none'
                    "
                  >
                    {{ column.label }}
                    <span class="flex flex-col leading-none" aria-hidden="true">
                      <svg
                        class="size-2.5 transition-colors"
                        [class.text-brand-500]="sortKey === column.key && sortDirection === 'asc'"
                        [class.text-gray-300]="sortKey !== column.key || sortDirection !== 'asc'"
                        viewBox="0 0 10 6"
                        fill="currentColor"
                      >
                        <path d="M5 0 10 6H0L5 0Z" />
                      </svg>
                      <svg
                        class="mt-0.5 size-2.5 transition-colors"
                        [class.text-brand-500]="sortKey === column.key && sortDirection === 'desc'"
                        [class.text-gray-300]="sortKey !== column.key || sortDirection !== 'desc'"
                        viewBox="0 0 10 6"
                        fill="currentColor"
                      >
                        <path d="M5 6 0 0h10L5 6Z" />
                      </svg>
                    </span>
                  </button>
                </th>
              }
            </tr>
          </thead>
          <tbody>
            @for (patient of pagedPatients; track patient.id) {
              <tr class="border-b border-gray-100 dark:border-gray-800">
                <td class="px-6 py-4">
                  <input
                    type="checkbox"
                    class="size-5 cursor-pointer rounded border-gray-300 accent-brand-500 dark:border-gray-700"
                    [checked]="selected.has(patient.id)"
                    (change)="toggle(patient.id)"
                    [attr.aria-label]="'Seleccionar ' + patient.name"
                  />
                </td>
                <td class="px-6 py-4">
                  <div class="flex items-center gap-3">
                    <img
                      [src]="patient.image"
                      [alt]="patient.name"
                      class="size-10 rounded-full object-cover"
                    />
                    <span class="text-sm font-medium text-gray-800 dark:text-white/90">
                      {{ patient.name }}
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400">
                  {{ patient.room }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400">
                  {{ patient.location }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400">
                  {{ patient.age }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-700 dark:text-gray-400">
                  {{ formatDate(patient.admissionDate) }}
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-800 dark:text-white/90">
                  Bs {{ formatFee(patient.fee) }}
                </td>
              </tr>
            } @empty {
              <tr>
                <td
                  colspan="7"
                  class="px-6 py-10 text-center text-sm text-gray-500 dark:text-gray-400"
                >
                  No hay pacientes para mostrar.
                </td>
              </tr>
            }
          </tbody>
        </table>
      </div>

      <div class="flex flex-col gap-3 border-t border-gray-100 px-4 py-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-800">
        <p class="text-sm text-gray-500 dark:text-gray-400">
          Mostrando {{ pageStart }}–{{ pageEnd }} de {{ patients.length }} registros
        </p>
        <app-pagination
          [currentPage]="page"
          [totalPages]="totalPages"
          variant="text-icon"
          (pageChange)="goToPage($event)"
        />
      </div>
    </div>
  `,
})
export class DataTableOneComponent {
  readonly columns: { key: SortKey; label: string }[] = [
    { key: 'name', label: 'Paciente' },
    { key: 'room', label: 'Sala' },
    { key: 'location', label: 'Sucursal' },
    { key: 'age', label: 'Edad' },
    { key: 'admissionDate', label: 'Ingreso' },
    { key: 'fee', label: 'Cuota' },
  ];

  readonly patients: PatientRow[] = [
    {
      id: 1,
      name: 'Abram Schleifer',
      image: '/images/user/user-20.jpg',
      room: 'Sala Ositos',
      location: 'Yacuiba',
      age: 3,
      admissionDate: '2025-02-14',
      fee: 850,
    },
    {
      id: 2,
      name: 'Charlotte Anderson',
      image: '/images/user/user-21.jpg',
      room: 'Sala Soles',
      location: 'Yacuiba',
      age: 4,
      admissionDate: '2024-03-08',
      fee: 920,
    },
    {
      id: 3,
      name: 'Ethan Brown',
      image: '/images/user/user-22.jpg',
      room: 'Sala Estrellas',
      location: 'El Carmen',
      age: 5,
      admissionDate: '2023-01-22',
      fee: 1000,
    },
    {
      id: 4,
      name: 'Sophia Martinez',
      image: '/images/user/user-23.jpg',
      room: 'Sala Ositos',
      location: 'Yacuiba',
      age: 2,
      admissionDate: '2026-06-15',
      fee: 780,
    },
    {
      id: 5,
      name: 'James Wilson',
      image: '/images/user/user-24.jpg',
      room: 'Sala Soles',
      location: 'El Carmen',
      age: 4,
      admissionDate: '2025-09-20',
      fee: 900,
    },
    {
      id: 6,
      name: 'Olivia Johnson',
      image: '/images/user/user-25.jpg',
      room: 'Sala Estrellas',
      location: 'Yacuiba',
      age: 5,
      admissionDate: '2024-08-03',
      fee: 980,
    },
    {
      id: 7,
      name: 'Mateo Quispe',
      image: '/images/user/user-20.jpg',
      room: 'Sala Ositos',
      location: 'Yacuiba',
      age: 3,
      admissionDate: '2026-01-12',
      fee: 850,
    },
    {
      id: 8,
      name: 'Valentina Rojas',
      image: '/images/user/user-22.jpg',
      room: 'Sala Soles',
      location: 'El Carmen',
      age: 4,
      admissionDate: '2025-11-05',
      fee: 900,
    },
    {
      id: 9,
      name: 'Lucas Méndez',
      image: '/images/user/user-24.jpg',
      room: 'Sala Estrellas',
      location: 'Yacuiba',
      age: 2,
      admissionDate: '2026-07-19',
      fee: 780,
    },
  ];

  readonly pageSize = 5;
  page = 1;
  sortKey: SortKey = 'name';
  sortDirection: SortDirection = 'asc';
  selected = new Set<number>();

  get sortedPatients(): PatientRow[] {
    return [...this.patients].sort((a, b) => {
      const left = a[this.sortKey];
      const right = b[this.sortKey];
      const comparison =
        typeof left === 'number' && typeof right === 'number'
          ? left - right
          : String(left).localeCompare(String(right), 'es');

      return this.sortDirection === 'asc' ? comparison : comparison * -1;
    });
  }

  get totalPages(): number {
    return Math.max(1, Math.ceil(this.patients.length / this.pageSize));
  }

  get pagedPatients(): PatientRow[] {
    const start = (this.page - 1) * this.pageSize;
    return this.sortedPatients.slice(start, start + this.pageSize);
  }

  get pageStart(): number {
    if (this.patients.length === 0) return 0;
    return (this.page - 1) * this.pageSize + 1;
  }

  get pageEnd(): number {
    return Math.min(this.page * this.pageSize, this.patients.length);
  }

  get allSelected(): boolean {
    return (
      this.pagedPatients.length > 0 &&
      this.pagedPatients.every((patient) => this.selected.has(patient.id))
    );
  }

  sortBy(key: SortKey): void {
    if (this.sortKey === key) {
      this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
    } else {
      this.sortKey = key;
      this.sortDirection = 'asc';
    }
    this.page = 1;
  }

  goToPage(page: number): void {
    this.page = Math.min(Math.max(1, page), this.totalPages);
  }

  toggle(id: number): void {
    if (this.selected.has(id)) {
      this.selected.delete(id);
    } else {
      this.selected.add(id);
    }
  }

  formatDate(isoDate: string): string {
    return new Intl.DateTimeFormat('es-BO', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
      timeZone: 'UTC',
    }).format(new Date(`${isoDate}T00:00:00Z`));
  }

  formatFee(value: number): string {
    return new Intl.NumberFormat('es-BO', {
      minimumFractionDigits: 0,
      maximumFractionDigits: 2,
    }).format(value);
  }

  toggleAll(event: Event): void {
    const checked = (event.target as HTMLInputElement).checked;
    this.pagedPatients.forEach((patient) => {
      if (checked) {
        this.selected.add(patient.id);
      } else {
        this.selected.delete(patient.id);
      }
    });
  }
}
