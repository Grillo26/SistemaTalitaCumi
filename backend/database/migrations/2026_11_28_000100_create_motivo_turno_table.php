<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo 07 — Cambios de turnos (7.3).
     *
     * Catálogo configurable de motivos de reprogramación y cancelación. Los
     * valores de origen y descuenta_saldo quedan nullable a propósito: el
     * negocio debe confirmarlos y no se asumen en código ni en el seeder.
     */
    public function up(): void
    {
        Schema::create('motivo_turno', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 40)->unique();
            $table->string('nombre', 120);
            $table->boolean('aplica_reprogramar')->default(true);
            $table->boolean('aplica_cancelar')->default(true);
            $table->string('origen', 20)->nullable();
            $table->boolean('descuenta_saldo')->nullable();
            $table->boolean('requiere_texto')->default(false);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(100);
            $table->timestampsTz();

            $table->index(['activo', 'orden'], 'idx_motivo_turno_activo_orden');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE motivo_turno '
                ."ADD CONSTRAINT motivo_turno_origen_valido CHECK (origen IS NULL OR origen IN ('centro','paciente'))",
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('motivo_turno');
    }
};
