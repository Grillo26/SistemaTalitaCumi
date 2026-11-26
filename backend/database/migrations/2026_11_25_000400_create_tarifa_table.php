<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tarifas con vigencia y sucursal opcional (RF-T03 a RF-T13).
     */
    public function up(): void
    {
        Schema::create('tarifa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('servicio_id')->constrained('servicio')->restrictOnDelete();
            $table->foreignId('categoria_tarifa_id')->constrained('categoria_tarifa')->restrictOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursal')->restrictOnDelete();
            $table->decimal('monto', 12, 2);
            $table->date('vigente_desde');
            $table->date('vigente_hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();

            $table->index(['servicio_id', 'categoria_tarifa_id'], 'tarifa_servicio_categoria_idx');
            $table->index('sucursal_id', 'tarifa_sucursal_idx');
            $table->index(
                ['servicio_id', 'sucursal_id', 'categoria_tarifa_id', 'vigente_desde', 'vigente_hasta'],
                'tarifa_solapamiento_idx',
            );
            $table->index(['vigente_desde', 'vigente_hasta'], 'tarifa_vigencia_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE tarifa ADD CONSTRAINT tarifa_monto_no_negativo '
                .'CHECK (monto >= 0)',
            );
            DB::statement(
                'ALTER TABLE tarifa ADD CONSTRAINT tarifa_vigencia_coherente '
                .'CHECK (vigente_hasta IS NULL OR vigente_hasta >= vigente_desde)',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tarifa');
    }
};
