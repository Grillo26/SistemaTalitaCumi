<?php

namespace App\Modules\InscripcionesAtenciones\Queries;

use App\Models\User;
use App\Modules\InscripcionesAtenciones\Contracts\PacienteEstadoContract;
use App\Modules\InscripcionesAtenciones\Support\EstadoInscripcionQueryBuilder;
use App\Modules\Pacientes\Enums\EstadoPaciente;

/**
 * [Estados Paciente e Inscripción] RF-012 / CA-008 / CA-009.
 */
final class ReporteEstadosQuery
{
    public function __construct(
        private readonly PacienteEstadoContract $pacientes,
        private readonly EstadoInscripcionQueryBuilder $inscripciones,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function __invoke(
        array $filters,
        User $actor,
        int $page = 1,
        int $perPage = 20,
        string $sort = 'fecha_fin',
        string $direction = 'asc',
    ): array {
        $tipo = ($filters['tipo'] ?? 'inscripciones') === 'pacientes'
            ? 'pacientes'
            : 'inscripciones';
        $page = max(1, $page);
        $perPage = max(1, min($perPage, 50));

        $baseFilters = $filters;
        unset($baseFilters['estado'], $baseFilters['tipo']);
        $conteosPacientes = [];

        foreach (EstadoPaciente::cases() as $estado) {
            $conteosPacientes[$estado->value] = $this->pacientes
                ->listarPorEstado(array_merge($baseFilters, [
                    'estado' => $estado->value,
                    'per_page' => 1,
                ]), $actor->id)
                ->total();
        }

        $conteosInscripciones = $this->inscripciones->conteos($baseFilters, $actor);

        if ($tipo === 'pacientes') {
            $filters['estado'] = $this->patientState($filters['estado'] ?? null);
            $patientSorts = ['apellido', 'nombre_completo', 'codigo', 'fecha_nacimiento', 'estado', 'updated_at'];
            $sort = in_array($sort, $patientSorts, true) ? $sort : 'nombre_completo';
            $paginator = $this->pacientes->listarPorEstado(array_merge($filters, [
                'page' => $page,
                'per_page' => $perPage,
                'sort' => $sort,
                'direction' => $direction,
            ]), $actor->id);

            $items = $paginator->getCollection()
                ->map(static fn ($paciente): array => $paciente->toArray())
                ->all();
        } else {
            $filters['estado'] = $this->inscriptionState($filters['estado'] ?? null);
            $paginator = $this->inscripciones->paginate(
                filters: $filters,
                actor: $actor,
                page: $page,
                perPage: $perPage,
                sort: $sort,
                direction: $direction,
            );

            $items = $paginator->items();
        }

        return [
            'tipo' => $tipo,
            'conteos' => [
                'pacientes' => $conteosPacientes,
                'inscripciones' => $conteosInscripciones,
            ],
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ];
    }

    private function patientState(mixed $estado): ?string
    {
        if (! is_string($estado) || $estado === '') {
            return null;
        }

        return match ($estado) {
            'activa', 'activo' => 'activo',
            'en_pausa' => 'en_pausa',
            'retirada', 'retirado' => 'retirado',
            default => $estado,
        };
    }

    private function inscriptionState(mixed $estado): ?string
    {
        if (! is_string($estado) || $estado === '') {
            return null;
        }

        return match ($estado) {
            'activo', 'activa' => 'activa',
            'en_pausa' => 'en_pausa',
            'vencido', 'vencida' => 'vencida',
            'renovado', 'renovada' => 'renovada',
            'retirado', 'retirada' => 'retirada',
            default => $estado,
        };
    }
}
