<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo de medios de pago (módulo 08, submódulo 01 / RF-005, RN-002, RNF-011).
     *
     * El código es estable y lo referencia `pago.medio_pago`. Caja separa por
     * medio usando este código; no se hardcodean medios fuera del catálogo.
     */
    public function up(): void
    {
        Schema::create('medio_pago', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre', 60);
            $table->boolean('activo')->default(true);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medio_pago');
    }
};
