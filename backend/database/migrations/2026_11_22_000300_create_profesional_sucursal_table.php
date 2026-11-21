<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sucursales de trabajo del profesional (03/RF-E06, 03/RN-07).
     *
     * La relación es independiente de la especialidad (03/RN-10). Se activa
     * y desactiva con motivo, sin borrado físico. El índice parcial evita
     * duplicar una relación activa (03/RF-E07).
     */
    public function up(): void
    {
        Schema::create('profesional_sucursal', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesional_id')->constrained('profesional')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursal')->restrictOnDelete();
            $table->boolean('activo')->default(true);
            $table->text('motivo')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();

            $table->index('sucursal_id');
            $table->index('profesional_id');
            $table->index(['sucursal_id', 'activo']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX profesional_sucursal_activa_uk
                ON profesional_sucursal (profesional_id, sucursal_id)
                WHERE activo'
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('profesional_sucursal');
    }
};
