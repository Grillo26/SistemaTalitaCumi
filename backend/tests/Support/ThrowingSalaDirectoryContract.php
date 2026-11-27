<?php

namespace Tests\Support;

use App\Modules\Organizacion\Contracts\SalaDirectoryContract;
use LogicException;

final class ThrowingSalaDirectoryContract implements SalaDirectoryContract
{
    public function existe(int $salaId): bool
    {
        throw $this->error();
    }

    public function estaActiva(int $salaId): bool
    {
        throw $this->error();
    }

    public function nombres(array $salaIds): array
    {
        throw $this->error();
    }

    public function nombresPorSucursal(int $sucursalId, bool $soloActivas = true): array
    {
        throw $this->error();
    }

    public function perteneceASucursal(int $salaId, int $sucursalId): bool
    {
        throw $this->error();
    }

    public function asegurarActivaEnSucursal(int $salaId, int $sucursalId): void
    {
        throw $this->error();
    }

    private function error(): LogicException
    {
        return new LogicException('Módulo Salas no disponible en este test.');
    }
}
