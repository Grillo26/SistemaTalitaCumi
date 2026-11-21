<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sucursales del sistema.
     *
     * Módulo 01 — Organización y sucursales.
     * El código y el nombre son únicos sin distinguir mayúsculas ni espacios
     * externos. La normalización de espacios internos se hace en la aplicación.
     */
    public function up(): void
    {
        Schema::create('sucursal', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 50);
            $table->string('nombre', 150);
            $table->text('direccion')->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();
        });

        // Respaldos de unicidad a nivel de base de datos (RN-001 / RF-002).
        // Se usa trim() para ignorar espacios externos; los espacios internos
        // se normalizan antes de guardar desde BranchIdentityNormalizationRule.
        DB::statement('CREATE UNIQUE INDEX sucursal_codigo_normalizado_uk ON sucursal ((lower(trim(codigo))))');
        DB::statement('CREATE UNIQUE INDEX sucursal_nombre_normalizado_uk ON sucursal ((lower(trim(nombre))))');
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursal');
    }
};
