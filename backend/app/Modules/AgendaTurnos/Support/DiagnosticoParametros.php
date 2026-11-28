<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Exceptions\ConfiguracionDiagnosticoNoDisponibleException;
use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use Throwable;

/**
 * 07.05/RF-002 / 07.05/RF-003 / 07.05/RN-010 / 07.05/RNF-011.
 *
 * Lectura fail-closed de los parámetros de agenda y duración. La duración del
 * servicio tiene prioridad; este parámetro es solo el respaldo.
 */
final class DiagnosticoParametros
{
    public const DIAS_HABILITADOS = 'agenda.dias_semana_habilitados';

    public const HORARIO_APERTURA = 'agenda.horario_apertura';

    public const HORARIO_CIERRE = 'agenda.horario_cierre';

    public const INTERVALO_MINIMO = 'agenda.intervalo_minimo_entre_turnos_minutos';

    public const DURACION_SESION = 'duracion_sesion_minutos';

    public const PREFIJO_PAIS = 'whatsapp.prefijo_pais';

    public function __construct(private readonly ParameterReaderContract $parametros) {}

    /**
     * @return list<int>
     */
    public function diasHabilitados(): array
    {
        $valor = $this->texto(self::DIAS_HABILITADOS);
        $dias = [];

        foreach (explode(',', $valor) as $parte) {
            $parte = trim($parte);

            if ($parte === '' || ! ctype_digit($parte)) {
                continue;
            }

            $dia = (int) $parte;

            if ($dia >= 1 && $dia <= 7) {
                $dias[] = $dia;
            }
        }

        $dias = array_values(array_unique($dias));

        if ($dias === []) {
            throw new ConfiguracionDiagnosticoNoDisponibleException(self::DIAS_HABILITADOS);
        }

        return $dias;
    }

    public function apertura(): string
    {
        return $this->hora(self::HORARIO_APERTURA);
    }

    public function cierre(): string
    {
        return $this->hora(self::HORARIO_CIERRE);
    }

    public function intervaloMinimo(): int
    {
        $valor = $this->texto(self::INTERVALO_MINIMO);

        if (! is_numeric($valor)) {
            throw new ConfiguracionDiagnosticoNoDisponibleException(self::INTERVALO_MINIMO);
        }

        $minutos = (int) $valor;

        if ($minutos < 0) {
            throw new ConfiguracionDiagnosticoNoDisponibleException(self::INTERVALO_MINIMO);
        }

        return $minutos;
    }

    public function duracionSesionMinutos(): int
    {
        $valor = $this->texto(self::DURACION_SESION);

        if (! is_numeric($valor) || (int) $valor <= 0) {
            throw new ConfiguracionDiagnosticoNoDisponibleException(self::DURACION_SESION);
        }

        return (int) $valor;
    }

    /**
     * Prefijo de país para wa.me. Si el parámetro no existe o está vacío se
     * usa 591 (Bolivia), tal como exige 07.05/RF-014.
     */
    public function prefijoPais(): string
    {
        try {
            $valor = (string) $this->parametros->obtener(self::PREFIJO_PAIS)->valor;
        } catch (Throwable) {
            return '591';
        }

        $digitos = preg_replace('/\D+/', '', $valor) ?? '';

        return $digitos === '' ? '591' : $digitos;
    }

    private function texto(string $clave): string
    {
        try {
            return trim((string) $this->parametros->obtener($clave)->valor);
        } catch (Throwable) {
            throw new ConfiguracionDiagnosticoNoDisponibleException($clave);
        }
    }

    private function hora(string $clave): string
    {
        $valor = $this->texto($clave);

        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $valor) !== 1) {
            throw new ConfiguracionDiagnosticoNoDisponibleException($clave);
        }

        return $valor;
    }
}
