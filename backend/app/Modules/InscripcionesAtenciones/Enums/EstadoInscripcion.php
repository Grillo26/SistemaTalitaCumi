<?php

namespace App\Modules\InscripcionesAtenciones\Enums;

/**
 * [Estados Paciente e Inscripción] RF-002 / RF-004 / RN-003.
 *
 * Estado de vista común para salas y paquetes. No se persiste: cada entidad
 * conserva su propio Enum. Vencida y Renovada son derivadas.
 */
enum EstadoInscripcion: string
{
    case ACTIVA = 'activa';
    case EN_PAUSA = 'en_pausa';
    case VENCIDA = 'vencida';
    case RENOVADA = 'renovada';
    case RETIRADA = 'retirada';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVA => 'Activa',
            self::EN_PAUSA => 'En pausa',
            self::VENCIDA => 'Vencida',
            self::RENOVADA => 'Renovada',
            self::RETIRADA => 'Retirada',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $estado): string => $estado->value, self::cases());
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(
            static fn (self $estado): array => [
                'value' => $estado->value,
                'label' => $estado->label(),
            ],
            self::cases(),
        );
    }

    public static function fromSala(EstadoInscripcionSala $estado): self
    {
        return match ($estado) {
            EstadoInscripcionSala::ACTIVA => self::ACTIVA,
            EstadoInscripcionSala::EN_PAUSA => self::EN_PAUSA,
            EstadoInscripcionSala::VENCIDA => self::VENCIDA,
            EstadoInscripcionSala::RENOVADA => self::RENOVADA,
            EstadoInscripcionSala::RETIRADA => self::RETIRADA,
        };
    }

    public static function fromPaquete(EstadoPaqueteSesiones $estado): self
    {
        return match ($estado) {
            EstadoPaqueteSesiones::ACTIVO => self::ACTIVA,
            EstadoPaqueteSesiones::EN_PAUSA => self::EN_PAUSA,
            EstadoPaqueteSesiones::VENCIDO => self::VENCIDA,
            EstadoPaqueteSesiones::RENOVADO => self::RENOVADA,
            EstadoPaqueteSesiones::RETIRADO => self::RETIRADA,
        };
    }

    public static function fromStored(string $tipoEntidad, string $estado): self
    {
        return $tipoEntidad === TipoEntidadVigencia::INSCRIPCION_SALA->value
            ? self::fromSala(EstadoInscripcionSala::from($estado))
            : self::fromPaquete(EstadoPaqueteSesiones::from($estado));
    }

    public function storedValueFor(TipoEntidadVigencia $tipo): string
    {
        if ($tipo === TipoEntidadVigencia::INSCRIPCION_SALA) {
            return match ($this) {
                self::ACTIVA => EstadoInscripcionSala::ACTIVA->value,
                self::EN_PAUSA => EstadoInscripcionSala::EN_PAUSA->value,
                self::VENCIDA => EstadoInscripcionSala::VENCIDA->value,
                self::RENOVADA => EstadoInscripcionSala::RENOVADA->value,
                self::RETIRADA => EstadoInscripcionSala::RETIRADA->value,
            };
        }

        return match ($this) {
            self::ACTIVA => EstadoPaqueteSesiones::ACTIVO->value,
            self::EN_PAUSA => EstadoPaqueteSesiones::EN_PAUSA->value,
            self::VENCIDA => EstadoPaqueteSesiones::VENCIDO->value,
            self::RENOVADA => EstadoPaqueteSesiones::RENOVADO->value,
            self::RETIRADA => EstadoPaqueteSesiones::RETIRADO->value,
        };
    }

    public function permiteAsistencia(): bool
    {
        return $this === self::ACTIVA;
    }

    public function permitePausa(): bool
    {
        return $this === self::ACTIVA;
    }

    public function permiteReactivacion(): bool
    {
        return $this === self::EN_PAUSA;
    }

    public function permiteRetiro(): bool
    {
        return $this === self::ACTIVA || $this === self::EN_PAUSA;
    }

    public function esDerivada(): bool
    {
        return $this === self::VENCIDA || $this === self::RENOVADA;
    }
}
