<?php

namespace App\Modules\Pagos\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * RN-004 / RF-010 / CA-003: el total supera el saldo pendiente. Devuelve el máximo permitido.
 */
final class PagoSaldoExcedidoException extends PagosDomainException
{
    public function __construct(public readonly string $saldoMaximo)
    {
        parent::__construct(sprintf(
            'El pago supera el saldo pendiente. El máximo permitido es Bs. %s.',
            $saldoMaximo,
        ));
    }

    public function status(): int
    {
        return 409;
    }

    public function errorCode(): string
    {
        return 'PAGO_SALDO_EXCEDIDO';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'saldo_maximo' => $this->saldoMaximo,
        ], $this->status());
    }
}
