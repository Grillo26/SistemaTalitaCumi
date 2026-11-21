<?php

namespace App\Modules\Profesionales\Http\Resources;

use App\Modules\Profesionales\Enums\MotivoNoAsignable;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Fila de la matriz profesional–especialidad–sucursal (03/RF-E08).
 */
final class MatrizRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profesionalActivo = (bool) $this->profesional_activo;
        $especialidadActiva = (bool) $this->especialidad_activo;
        $sucursalActiva = (bool) $this->sucursal_activo;
        $asignable = $profesionalActivo && $especialidadActiva && $sucursalActiva;

        $motivos = [];

        if (! $profesionalActivo) {
            $motivos[] = $this->motivo(MotivoNoAsignable::PROFESIONAL_INACTIVO);
        }

        if (! $especialidadActiva) {
            $motivos[] = $this->motivo(MotivoNoAsignable::ESPECIALIDAD_INACTIVA);
        }

        if (! $sucursalActiva) {
            $motivos[] = $this->motivo(MotivoNoAsignable::SUCURSAL_INACTIVA);
        }

        return [
            'profesional' => [
                'id' => (int) $this->profesional_id,
                'nombre_completo' => (string) $this->profesional_nombre,
                'ci' => (string) $this->profesional_ci,
                'activo' => $profesionalActivo,
            ],
            'especialidad' => [
                'id' => (int) $this->especialidad_id,
                'nombre' => (string) $this->especialidad_nombre,
                'tipo' => (string) $this->tipo,
                'activo' => $especialidadActiva,
            ],
            'sucursal' => [
                'id' => (int) $this->sucursal_id,
                'codigo' => (string) $this->sucursal_codigo,
                'nombre' => (string) $this->sucursal_nombre,
                'activo' => $sucursalActiva,
            ],
            'relacion_sucursal_activa' => (bool) $this->relacion_sucursal_activa,
            'asignable' => $asignable,
            'motivos_no_asignable' => $motivos,
        ];
    }

    /**
     * @return array{codigo: string, mensaje: string}
     */
    private function motivo(MotivoNoAsignable $motivo): array
    {
        return [
            'codigo' => $motivo->value,
            'mensaje' => $motivo->label(),
        ];
    }
}
