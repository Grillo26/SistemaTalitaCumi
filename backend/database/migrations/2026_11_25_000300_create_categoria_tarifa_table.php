<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categorías globales de tarifa (RF-T01 / RF-T02).
     */
    public function up(): void
    {
        Schema::create('categoria_tarifa', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
        });

        DB::statement(
            'CREATE UNIQUE INDEX categoria_tarifa_nombre_normalizado_uk '
            .'ON categoria_tarifa ((lower(trim(nombre))))',
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('categoria_tarifa');
    }
};
