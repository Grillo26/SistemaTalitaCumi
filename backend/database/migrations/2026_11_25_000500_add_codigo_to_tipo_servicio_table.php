<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Código estable para identificar los tipos de servicio base (RN-D08).
     *
     * El nombre sigue siendo configurable; el código se usa para validar que
     * un servicio sea del tipo diagnóstico/evaluación.
     */
    public function up(): void
    {
        Schema::table('tipo_servicio', function (Blueprint $table) {
            $table->string('codigo', 50)->nullable()->after('nombre')->unique();
        });

        foreach ([
            'Entrevista' => 'entrevista',
            'Diagnóstico / Evaluación' => 'diagnostico_evaluacion',
            'Terapia individual' => 'terapia_individual',
            'Sala grupal' => 'sala_grupal',
        ] as $nombre => $codigo) {
            DB::table('tipo_servicio')
                ->where('nombre', $nombre)
                ->update(['codigo' => $codigo]);
        }
    }

    public function down(): void
    {
        Schema::table('tipo_servicio', function (Blueprint $table) {
            $table->dropUnique(['codigo']);
            $table->dropColumn('codigo');
        });
    }
};
