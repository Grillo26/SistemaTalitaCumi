<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\Organizacion\Contracts\ParameterReaderContract;
use App\Modules\Pacientes\Contracts\TutorResumen;
use App\Modules\Pacientes\Rules\TutorPhoneRule;
use Carbon\CarbonImmutable;
use Throwable;

/**
 * Enlace wa.me para avisar el cambio al tutor.
 *
 * No envía nada: solo construye la URL que abre el frontend. El prefijo de
 * país sale de configuración y no se hardcodea. La plantilla no incluye
 * datos clínicos ni el detalle del motivo.
 */
final class WhatsappTurnoLink
{
    public const PREFIJO_PAIS = 'whatsapp.prefijo_pais';

    private const PLANTILLA = 'Hola {tutor}, le informamos que el turno de {paciente} del {fecha} a las {hora} fue {accion}. Por favor, comuníquese con el centro.';

    public function __construct(private readonly ParameterReaderContract $parametros) {}

    /**
     * @return array<string, mixed>
     */
    public function construir(
        TutorResumen $tutor,
        string $paciente,
        string $fecha,
        string $hora,
        string $accion,
    ): array {
        $telefono = TutorPhoneRule::normalize($tutor->telefono);

        if ($telefono === null) {
            return [
                'disponible' => false,
                'mensaje' => 'El tutor no tiene un teléfono registrado.',
            ];
        }

        $prefijo = $this->prefijo();

        if ($prefijo === '') {
            return [
                'disponible' => false,
                'mensaje' => 'Falta configurar el prefijo de país para WhatsApp.',
            ];
        }

        $numero = str_starts_with($telefono, $prefijo)
            ? $telefono
            : $prefijo.$telefono;

        $mensaje = str_replace(
            ['{tutor}', '{paciente}', '{fecha}', '{hora}', '{accion}'],
            [
                $tutor->nombreCompleto,
                $paciente,
                $this->fecha($fecha),
                substr($hora, 0, 5),
                $accion,
            ],
            self::PLANTILLA,
        );

        return [
            'disponible' => true,
            'telefono' => $telefono,
            'tutor_nombre' => $tutor->nombreCompleto,
            'whatsapp_url' => 'https://wa.me/'.rawurlencode($numero).'?text='.rawurlencode($mensaje),
            'mensaje' => $mensaje,
        ];
    }

    private function prefijo(): string
    {
        try {
            $valor = (string) $this->parametros->obtener(self::PREFIJO_PAIS)->valor;
        } catch (Throwable) {
            return '';
        }

        return preg_replace('/\D+/', '', $valor) ?? '';
    }

    private function fecha(string $fecha): string
    {
        $valor = CarbonImmutable::createFromFormat('!Y-m-d', $fecha);

        return $valor === false ? $fecha : $valor->format('d/m/Y');
    }
}
