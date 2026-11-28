<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Contador de correlativos de pagos por sucursal (RF-002 / RN-005 / RT-003).
     *
     * No es una secuencia de PostgreSQL: una secuencia deja huecos al revertir
     * una transacción. La Action incrementa esta fila con `lockForUpdate()`
     * dentro de la misma transacción que inserta el pago.
     */
    public function up(): void
    {
        Schema::create('secuencia_pago', function (Blueprint $table) {
            $table->foreignId('sucursal_id')->primary()->constrained('sucursal')->restrictOnDelete();
            $table->unsignedInteger('ultimo_numero')->default(0);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'ALTER TABLE secuencia_pago ADD CONSTRAINT secuencia_pago_no_negativa CHECK (ultimo_numero >= 0)',
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('secuencia_pago');
    }
};
