<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tipos de diagnóstico (RF-D01 a RF-D05 / RN-D08).
     *
     * Es un servicio del catálogo con atributos adicionales.
     */
    public function up(): void
    {
        Schema::create('tipo_diagnostico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->unique()->constrained('servicio')->restrictOnDelete();
            $table->string('nombre', 150)->unique();
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('dias_estimados')->default(1);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
        });

        DB::statement(
            'CREATE UNIQUE INDEX tipo_diagnostico_nombre_normalizado_uk '
            .'ON tipo_diagnostico ((lower(trim(nombre))))',
        );

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE tipo_diagnostico ADD CONSTRAINT tipo_diagnostico_dias_positivos '
                .'CHECK (dias_estimados > 0)',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tipo_diagnostico');
    }
};
