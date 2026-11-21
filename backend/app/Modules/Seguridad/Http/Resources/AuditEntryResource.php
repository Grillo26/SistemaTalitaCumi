<?php

namespace App\Modules\Seguridad\Http\Resources;

use App\Modules\Seguridad\Support\AuditModuleResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Evento de bitácora para la consulta y el detalle (02/RF-B04, RF-B05).
 */
final class AuditEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $module = $this->modulo ?? AuditModuleResolver::fromEntity($this->entidad);

        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'usuario' => $this->usuario?->name,
            'usuario_email' => $this->usuario?->email,
            'sucursal_id' => $this->sucursal_id,
            'sucursal' => $this->sucursal?->codigo,
            'sucursal_nombre' => $this->sucursal?->nombre,
            'modulo' => $module,
            'modulo_label' => AuditModuleResolver::moduleLabel($module),
            'entidad' => $this->entidad,
            'entidad_label' => AuditModuleResolver::entityLabel($this->entidad),
            'entidad_id' => $this->entidad_id,
            'accion' => $this->accion,
            'accion_label' => AuditModuleResolver::actionLabel($this->accion),
            'datos_anteriores' => $this->datos_anteriores,
            'datos_nuevos' => $this->datos_nuevos,
            'motivo' => $this->motivo,
            'ip' => $this->ip,
            'resultado' => $this->resultado,
            'resultado_label' => AuditModuleResolver::resultLabel($this->resultado),
            'critico' => (bool) $this->critico,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
