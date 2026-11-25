<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Home branch, parentesco de referencia y unicidad nombre+teléfono (04.02).
     */
    public function up(): void
    {
        Schema::table('tutor', function (Blueprint $table) {
            $table->foreignId('sucursal_registro_id')
                ->nullable()
                ->after('id')
                ->constrained('sucursal')
                ->restrictOnDelete();
            $table->foreignId('parentesco_id')
                ->nullable()
                ->after('direccion')
                ->constrained('parentesco')
                ->restrictOnDelete();

            $table->index('sucursal_registro_id');
            $table->index('parentesco_id');
        });

        // Backfill del home branch: primer paciente asociado; si no hay,
        // última fila CREAR de bitácora con sucursal (flujo de alta de paciente).
        DB::table('tutor')->orderBy('id')->each(function (object $tutor): void {
            $sucursalId = DB::table('paciente_tutor')
                ->join('paciente', 'paciente.id', '=', 'paciente_tutor.paciente_id')
                ->where('paciente_tutor.tutor_id', $tutor->id)
                ->orderBy('paciente.id')
                ->value('paciente.sucursal_registro_id');

            if ($sucursalId === null) {
                $sucursalId = DB::table('bitacora')
                    ->where('entidad', 'tutor')
                    ->where('entidad_id', $tutor->id)
                    ->where('accion', 'CREAR')
                    ->whereNotNull('sucursal_id')
                    ->orderByDesc('id')
                    ->value('sucursal_id');
            }

            if ($sucursalId !== null) {
                DB::table('tutor')->where('id', $tutor->id)->update([
                    'sucursal_registro_id' => $sucursalId,
                ]);
            }
        });

        // Refuerzo de unicidad por nombre completo + teléfono principal.
        // La regla de aplicación ya lo valida; el índice protege carreras.
        DB::statement(
            'CREATE UNIQUE INDEX tutor_nombre_telefono_uk ON tutor (nombre_completo_normalizado, telefono_normalizado)'
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS tutor_nombre_telefono_uk');

        Schema::table('tutor', function (Blueprint $table) {
            $table->dropForeign(['sucursal_registro_id']);
            $table->dropForeign(['parentesco_id']);
            $table->dropIndex(['sucursal_registro_id']);
            $table->dropIndex(['parentesco_id']);
            $table->dropColumn(['sucursal_registro_id', 'parentesco_id']);
        });
    }
};
