<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo 07 — Programación de turnos (7.2).
     *
     * Registro de cada lote de generación o regeneración. La clave de
     * idempotencia es única a nivel global y es la defensa principal contra
     * el doble envío (RT-001). `origen_id` es una referencia lógica: el
     * origen puede ser una inscripción a sala o un paquete.
     */
    public function up(): void
    {
        Schema::create('turno_lote_generacion', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_origen', 30);
            $table->unsignedBigInteger('origen_id');
            $table->foreignId('sucursal_id')->constrained('sucursal')->restrictOnDelete();
            $table->string('accion', 20);
            $table->string('clave_idempotencia', 100)->unique();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip', 45)->nullable();
            $table->unsignedInteger('cantidad_creados')->default(0);
            $table->unsignedInteger('cantidad_cancelados')->default(0);
            $table->unsignedInteger('cantidad_preservados')->default(0);
            $table->jsonb('advertencias_confirmadas')->nullable();
            $table->timestampsTz();

            $table->index(['tipo_origen', 'origen_id', 'created_at'], 'idx_lote_generacion_origen');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE turno_lote_generacion '
                ."ADD CONSTRAINT turno_lote_tipo_origen_valido CHECK (tipo_origen IN ('inscripcion_sala','paquete_sesiones'))",
            );

            DB::statement(
                'ALTER TABLE turno_lote_generacion '
                ."ADD CONSTRAINT turno_lote_accion_valida CHECK (accion IN ('generar','regenerar'))",
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('turno_lote_generacion');
    }
};
