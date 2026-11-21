<?php

namespace App\Modules\Pacientes\Support;

use App\Models\User;
use App\Modules\Pacientes\Models\Tutor;
use Illuminate\Database\Eloquent\Builder;

/**
 * Exportación CSV compatible con Excel (04.02/RF-016).
 *
 * Usa punto y coma y BOM UTF-8 para que Excel en español abra el archivo en
 * columnas. El PDF queda pendiente de infraestructura.
 */
final class TutorCsvExporter
{
    public const DELIMITER = ';';

    public function count(Builder $query): int
    {
        return (clone $query)->count();
    }

    /**
     * @param  resource  $handle
     */
    public function write(Builder $query, User $actor, mixed $handle): void
    {
        $fullContact = $actor->can(PacientesPermission::TUTOR_VIEW_CONTACT);

        fwrite($handle, "\xEF\xBB\xBF");
        fputcsv($handle, [
            'ID',
            'Nombre completo',
            'Documento',
            'Teléfono principal',
            'Teléfono alternativo',
            'Correo',
            'Dirección',
            'Parentesco',
            'Sucursal de registro',
            'Estado',
            'Creado',
            'Última modificación',
        ], self::DELIMITER, '"', '');

        foreach ($query->lazy(200) as $tutor) {
            /** @var Tutor $tutor */
            fputcsv($handle, [
                (int) $tutor->id,
                $this->safe($tutor->nombre_completo),
                $this->safe($tutor->ci),
                $this->safe($fullContact ? $tutor->telefono : PhoneMask::mask($tutor->telefono)),
                $this->safe($fullContact
                    ? $tutor->telefono_alternativo
                    : PhoneMask::mask($tutor->telefono_alternativo)),
                $this->safe($fullContact ? $tutor->email : null),
                $this->safe($fullContact ? $tutor->direccion : null),
                $this->safe($tutor->parentescoCatalogo?->nombre ?? $tutor->parentesco),
                $this->safe($tutor->sucursalRegistro?->nombre),
                $tutor->estado,
                $tutor->created_at?->format('Y-m-d H:i:s'),
                $tutor->updated_at?->format('Y-m-d H:i:s'),
            ], self::DELIMITER, '"', '');
        }
    }

    /**
     * Evita que Excel interprete texto como fórmula.
     */
    private function safe(?string $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@]/', $value) === 1 ? "'".$value : $value;
    }
}
