<?php

namespace Tests\Support;

use App\Modules\Profesionales\Contracts\ProfessionalDirectoryContract;
use LogicException;

final class ThrowingProfessionalDirectoryContract implements ProfessionalDirectoryContract
{
    public function existe(int $profesionalId): bool
    {
        throw $this->error();
    }

    public function estaActivo(int $profesionalId): bool
    {
        throw $this->error();
    }

    public function nombres(array $profesionalIds): array
    {
        throw $this->error();
    }

    public function nombresPorSucursal(int $sucursalId, bool $soloActivos = true): array
    {
        throw $this->error();
    }

    public function asegurarActivo(int $profesionalId): void
    {
        throw $this->error();
    }

    private function error(): LogicException
    {
        return new LogicException('Módulo Profesionales no disponible en este test.');
    }
}
