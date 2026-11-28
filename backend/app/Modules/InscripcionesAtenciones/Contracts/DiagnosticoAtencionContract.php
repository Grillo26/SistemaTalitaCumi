<?php

namespace App\Modules\InscripcionesAtenciones\Contracts;

/**
 * Lectura y actualización acotada de diagnósticos para 07.05.
 *
 * La asociación de profesionales y la creación del diagnóstico siguen siendo
 * responsabilidad de 06.03. Este contrato solo expone lo necesario para
 * coordinar turnos y mantener `estado_general`.
 */
interface DiagnosticoAtencionContract
{
    /**
     * Diagnósticos de las sucursales indicadas, con filtros opcionales.
     *
     * `$sucursalIds` vacío es fail-closed: devuelve una página vacía.
     *
     * @param  list<int>  $sucursalIds
     */
    public function buscar(
        array $sucursalIds,
        ?string $desde,
        ?string $hasta,
        ?int $tipoDiagnosticoId,
        ?string $estadoGeneral,
        ?int $profesionalId,
        int $page,
        int $perPage,
    ): DiagnosticoAtencionPagina;

    public function resumen(int $diagnosticoId): ?DiagnosticoAtencionResumen;

    /**
     * Igual que resumen(), pero bloquea la fila para actualizarla en la misma
     * transacción (cancelación manual).
     */
    public function resumenParaActualizar(int $diagnosticoId): ?DiagnosticoAtencionResumen;

    /**
     * Actualiza `estado_general` si cambió. Devuelve true cuando hubo cambio.
     */
    public function actualizarEstadoGeneral(int $diagnosticoId, string $estadoGeneral): bool;

    /**
     * Tipos activos para el filtro del reporte.
     *
     * @return list<array{id: int, nombre: string}>
     */
    public function tiposDiagnostico(): array;
}
