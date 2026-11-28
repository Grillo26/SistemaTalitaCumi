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
     * Catálogo configurable de feriados (RF-016 / RN-008 / RT-003). No se
     * precarga ningún feriado: la tabla empieza vacía y la generación
     * funciona igual sin registros.
     */
    public function up(): void
    {
        Schema::create('feriado', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('descripcion', 200);
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursal')->restrictOnDelete();
            $table->boolean('activo')->default(true);
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestampsTz();

            $table->index(['fecha', 'activo'], 'idx_feriado_fecha_activo');
            $table->index('sucursal_id', 'idx_feriado_sucursal');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(<<<'SQL'
                CREATE UNIQUE INDEX feriado_global_unico
                ON feriado (fecha)
                WHERE sucursal_id IS NULL
            SQL);

            DB::statement(<<<'SQL'
                CREATE UNIQUE INDEX feriado_sucursal_unico
                ON feriado (fecha, sucursal_id)
                WHERE sucursal_id IS NOT NULL
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feriado');
    }
};
