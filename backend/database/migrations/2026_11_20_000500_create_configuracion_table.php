<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Parámetros globales del sistema — Submódulo 03.
     *
     * Catálogo clave-valor versionado por seeder. No hay borrado lógico ni
     * físico: solo se editan y restauran valores. Los valores se guardan como
     * texto serializado y se convierten según `tipo`.
     */
    public function up(): void
    {
        Schema::create('configuracion', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->string('categoria', 50);
            $table->text('descripcion')->nullable();
            $table->string('tipo', 20)->default('texto');
            $table->text('valor');
            $table->text('valor_defecto');
            $table->string('unidad', 30)->nullable();
            $table->text('minimo')->nullable();
            $table->text('maximo')->nullable();
            $table->boolean('sensible')->default(false);
            $table->boolean('editable')->default(true);
            $table->boolean('visible_recepcion')->default(false);
            $table->unsignedInteger('version')->default(1);
            $table->timestampTz('updated_at')->useCurrent();

            $table->index('categoria');
            $table->index('visible_recepcion');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "ALTER TABLE configuracion ADD CONSTRAINT configuracion_tipo_check
                 CHECK (tipo IN ('entero', 'decimal', 'hora', 'texto', 'booleano'))",
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};
