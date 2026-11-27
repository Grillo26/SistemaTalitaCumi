<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Snapshot de especialidades requeridas aplicado al diagnóstico (RN-D11).
     */
    public function up(): void
    {
        Schema::create('atencion_diagnostico_requisito', function (Blueprint $table) {
            $table->foreignId('atencion_diagnostico_id')
                ->constrained('atencion_diagnostico')
                ->cascadeOnDelete();
            $table->foreignId('especialidad_id')
                ->constrained('especialidad')
                ->restrictOnDelete();
            $table->string('especialidad_nombre', 150);
            $table->unsignedSmallInteger('cantidad_minima')->default(1);
            $table->timestampTz('created_at')->useCurrent();

            $table->primary(
                ['atencion_diagnostico_id', 'especialidad_id'],
                'atencion_diagnostico_requisito_pk',
            );
            $table->index('especialidad_id', 'atencion_diagnostico_requisito_especialidad_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE atencion_diagnostico_requisito '
                .'ADD CONSTRAINT atencion_diagnostico_requisito_cantidad_positiva CHECK (cantidad_minima > 0)',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('atencion_diagnostico_requisito');
    }
};
