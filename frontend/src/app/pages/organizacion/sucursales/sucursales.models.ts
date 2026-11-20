export interface Branch {
  id: number;
  codigo: string;
  nombre: string;
  direccion: string | null;
  telefono: string | null;
  email: string | null;
  activo: boolean;
  estado: 'activa' | 'inactiva';
  version: number;
  created_at: string | null;
  updated_at: string | null;
}

export interface BranchAbilities {
  create: boolean;
  update: boolean;
  update_contact: boolean;
  change_status: boolean;
  delete: boolean;
}

export interface BranchPaginationMeta {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from: number | null;
  to: number | null;
}

export interface BranchListResponse {
  data: Branch[];
  links: {
    first: string | null;
    last: string | null;
    prev: string | null;
    next: string | null;
  };
  meta: BranchPaginationMeta;
  can: BranchAbilities;
}

export interface BranchResponse {
  data: Branch;
  can: BranchAbilities;
}

export type BranchStatusFilter = '' | 'activa' | 'inactiva';

export type BranchSortField = 'codigo' | 'nombre' | 'activo' | 'updated_at';

export type BranchSortDirection = 'asc' | 'desc';

export interface BranchListParams {
  search?: string;
  estado?: BranchStatusFilter;
  page?: number;
  per_page?: number;
  sort?: BranchSortField;
  direction?: BranchSortDirection;
}

export interface BranchCreatePayload {
  codigo: string;
  nombre: string;
  direccion: string | null;
  telefono: string | null;
  email: string | null;
  activo: boolean;
}

export interface BranchUpdatePayload {
  codigo?: string;
  nombre?: string;
  direccion?: string | null;
  telefono?: string | null;
  email?: string | null;
  version: number;
}

export interface BranchStatusPayload {
  motivo: string;
  version: number;
}

export interface ApiError {
  status: number;
  message: string;
  code?: string;
  errors?: Record<string, string[]>;
}

export const EMPTY_BRANCH_ABILITIES: BranchAbilities = {
  create: false,
  update: false,
  update_contact: false,
  change_status: false,
  delete: false,
};
