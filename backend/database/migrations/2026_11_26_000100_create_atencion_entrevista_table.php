<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Entrevista con los padres como atención (módulo 06, submódulo 03).
     */
    public function up(): void
    {
        Schema::create('atencion_entrevista', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('paciente')->restrictOnDelete();
            $table->foreignId('tutor_id')->constrained('tutor')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursal')->restrictOnDelete();
            $table->foreignId('servicio_id')->constrained('servicio')->restrictOnDelete();
            $table->foreignId('tarifa_id')->constrained('tarifa')->restrictOnDelete();
            $table->foreignId('categoria_tarifa_id')->constrained('categoria_tarifa')->restrictOnDelete();
            $table->date('fecha');
            $table->decimal('monto_esperado', 12, 2);
            $table->string('estado', 20)->default('activa');
            $table->text('notas_admin')->nullable();
            $table->timestampsTz();

            $table->index('estado', 'atencion_entrevista_estado_idx');
            $table->index(['sucursal_id', 'fecha'], 'atencion_entrevista_sucursal_fecha_idx');
            $table->index(['paciente_id', 'estado'], 'atencion_entrevista_paciente_estado_idx');
            $table->index('tutor_id', 'atencion_entrevista_tutor_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE atencion_entrevista '
                ."ADD CONSTRAINT atencion_entrevista_estado_valido CHECK (estado IN ('activa','retirada'))",
            );
            DB::statement(
                'ALTER TABLE atencion_entrevista '
                .'ADD CONSTRAINT atencion_entrevista_monto_no_negativo CHECK (monto_esperado >= 0)',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('atencion_entrevista');
    }
};
