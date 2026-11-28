<?php

namespace App\Modules\AgendaTurnos\Support;

use App\Modules\AgendaTurnos\Exceptions\WhatsappDiagnosticoNoDisponibleException;
use App\Modules\Pacientes\Contracts\TutorResumen;
use App\Modules\Pacientes\Rules\TutorPhoneRule;
use Carbon\CarbonImmutable;

/**
 * 07.05/RF-014 / 07.05/CA-010.
 *
 * Construye el enlace wa.me con el calendario de turnos programados. No envía
 * mensajes y no incluye tipo de diagnóstico ni datos clínicos. El teléfono
 * solo debe exponerse en la respuesta HTTP cuando el actor tiene
 * `tutor.ver_contacto`; el llamador se encarga de esa decisión.
 */
final class WhatsappDiagnosticoLink
{
    public function __construct(private readonly DiagnosticoParametros $parametros) {}

    /**
     * @param  list<array{fecha: string, hora_inicio: string, profesional_nombre: string}>  $turnos
     * @return array<string, mixed>
     */
    public function construir(TutorResumen $tutor, string $paciente, array $turnos): array
    {
        if ($turnos === []) {
            throw new WhatsappDiagnosticoNoDisponibleException(
                'El diagnóstico no tiene turnos programados para avisar.',
            );
        }

        $telefono = TutorPhoneRule::normalize($tutor->telefono);

        if ($telefono === null) {
            throw new WhatsappDiagnosticoNoDisponibleException(
                'El tutor principal no tiene un teléfono registrado.',
            );
        }

        $prefijo = $this->parametros->prefijoPais();
        $numero = str_starts_with($telefono, $prefijo) ? $telefono : $prefijo.$telefono;

        $calendario = array_map(
            fn (array $turno): string => sprintf(
                '%s %s con %s',
                $this->fecha($turno['fecha']),
                substr($turno['hora_inicio'], 0, 5),
                $turno['profesional_nombre'],
            ),
            $turnos,
        );

        $mensaje = sprintf(
            'Hola %s, le informamos el calendario del diagnóstico de %s: %s. Por favor, comuníquese con el centro.',
            $tutor->nombreCompleto,
            $paciente,
            implode('; ', $calendario),
        );

        return [
            'disponible' => true,
            'telefono' => $telefono,
            'tutor_nombre' => $tutor->nombreCompleto,
            'whatsapp_url' => 'https://wa.me/'.rawurlencode($numero).'?text='.rawurlencode($mensaje),
            'mensaje' => $mensaje,
        ];
    }

    private function fecha(string $fecha): string
    {
        $valor = CarbonImmutable::createFromFormat('!Y-m-d', $fecha);

        return $valor === false ? $fecha : $valor->format('d/m/Y');
    }
}
