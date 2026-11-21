<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parentesco autoritativo por paciente (04.02/RN-005).
     */
    public function up(): void
    {
        Schema::table('paciente_tutor', function (Blueprint $table) {
            $table->foreignId('parentesco_id')
                ->nullable()
                ->after('tutor_id')
                ->constrained('parentesco')
                ->restrictOnDelete();

            $table->index('parentesco_id');
        });

        // SQLite reconstruye la tabla al agregar una FK y pierde el índice
        // parcial de 04.01. Se recrea explícitamente para conservar la regla
        // de un único tutor principal por paciente.
        $this->recreatePrincipalIndex();
    }

    public function down(): void
    {
        Schema::table('paciente_tutor', function (Blueprint $table) {
            $table->dropForeign(['parentesco_id']);
            $table->dropIndex(['parentesco_id']);
            $table->dropColumn('parentesco_id');
        });

        $this->recreatePrincipalIndex();
    }

    private function recreatePrincipalIndex(): void
    {
        DB::statement('DROP INDEX IF EXISTS paciente_tutor_principal_uk');
        DB::statement(
            'CREATE UNIQUE INDEX paciente_tutor_principal_uk ON paciente_tutor (paciente_id) WHERE es_principal'
        );
    }
};
