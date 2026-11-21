<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos nuevos de bitácora (RF-B01 / RF-B13).
     *
     * Nullable o con default para tolerar filas históricas: el trigger de
     * inmutabilidad impide rellenarlas con UPDATE. `ip` ya existía.
     */
    public function up(): void
    {
        Schema::table('bitacora', function (Blueprint $table) {
            $table->string('modulo', 50)->nullable();
            $table->string('resultado', 20)->nullable();
            $table->boolean('critico')->default(false);

            $table->index('modulo');
            $table->index('resultado');
            $table->index('critico');
            $table->index(['sucursal_id', 'created_at'], 'idx_bitacora_sucursal_fecha');
        });
    }

    public function down(): void
    {
        Schema::table('bitacora', function (Blueprint $table) {
            $table->dropIndex(['modulo']);
            $table->dropIndex(['resultado']);
            $table->dropIndex(['critico']);
            $table->dropIndex('idx_bitacora_sucursal_fecha');
            $table->dropColumn(['modulo', 'resultado', 'critico']);
        });
    }
};
