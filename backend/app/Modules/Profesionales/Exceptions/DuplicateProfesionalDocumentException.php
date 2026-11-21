<?php

namespace App\Modules\Profesionales\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DuplicateProfesionalDocumentException extends ProfesionalesDomainException
{
    public function __construct()
    {
        parent::__construct('Ya existe un profesional con el mismo documento de identidad.');
    }

    public function status(): int
    {
        return 422;
    }

    public function errorCode(): string
    {
        return 'PROFESIONAL_DOCUMENT_DUPLICATED';
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message' => $this->getMessage(),
            'code' => $this->errorCode(),
            'errors' => ['ci' => [$this->getMessage()]],
        ], $this->status());
    }
}
