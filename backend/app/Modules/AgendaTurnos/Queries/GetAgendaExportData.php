<?php

namespace App\Modules\AgendaTurnos\Queries;

use App\Models\User;
use App\Modules\AgendaTurnos\Exceptions\ConfiguracionAgendaIncompletaException;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Organizacion\Exceptions\ParameterNotFoundException;
use Carbon\CarbonImmutable;

/**
 * [Agenda Semanal] RF-010, RN-008.
 *
 * Datos de la misma vista y con los mismos filtros, más los datos del centro
 * tomados de configuración. El contrato de exportación decide si puede
 * generar el PDF; mientras no exista, responde 503.
 */
final class GetAgendaExportData
{
    public function __construct(
        private readonly GetAgendaSemana $agenda,
        private readonly ParameterReaderContract $parametros,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function __invoke(User $actor, array $filters): array
    {
        $agenda = ($this->agenda)($actor, $filters);

        return [
            'centro' => [
                'nombre' => $this->parametro('nombre_centro'),
                'direccion' => $this->parametro('direccion_centro'),
                'telefono' => $this->parametro('telefono_centro'),
            ],
            'agenda' => $agenda['data'],
            'filtros' => $agenda['filtros'],
            'generado_en' => CarbonImmutable::now(
                (string) config('app.timezone', 'America/La_Paz'),
            )->toIso8601String(),
        ];
    }

    private function parametro(string $clave): string
    {
        try {
            return (string) $this->parametros->obtener($clave)->valor;
        } catch (ParameterNotFoundException) {
            throw new ConfiguracionAgendaIncompletaException(
                "Falta el dato de configuración {$clave} para exportar la agenda.",
            );
        }
    }
}
