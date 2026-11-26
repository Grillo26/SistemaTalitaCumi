<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo global de servicios (RF-C05 a RF-C13).
     *
     * La tabla no existía en migraciones; el .sql de referencia la incluía
     * sin tipo de servicio. Se agrega `tipo_servicio_id` y `version`.
     */
    public function up(): void
    {
        Schema::create('servicio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->constrained('area')->restrictOnDelete();
            $table->foreignId('tipo_servicio_id')->constrained('tipo_servicio')->restrictOnDelete();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('duracion_minutos')->default(50);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();

            $table->index('area_id');
            $table->index('tipo_servicio_id');
            $table->index('activo');
            $table->index('updated_at');
        });

        DB::statement(
            'CREATE UNIQUE INDEX servicio_area_nombre_normalizado_uk '
            .'ON servicio (area_id, (lower(trim(nombre))))',
        );

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE servicio ADD CONSTRAINT servicio_duracion_positiva '
                .'CHECK (duracion_minutos > 0)',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio');
    }
};
