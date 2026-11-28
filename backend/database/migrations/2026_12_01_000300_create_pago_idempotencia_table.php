<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Idempotencia de registro de pagos (RT-001).
     *
     * Alcance: (usuario, sucursal, clave). Misma clave y mismo contenido
     * devuelven el grupo original; misma clave con otro contenido responde 409.
     */
    public function up(): void
    {
        Schema::create('pago_idempotencia', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursal')->restrictOnDelete();
            $table->string('clave', 64);
            $table->char('hash_solicitud', 64);
            $table->uuid('grupo_pago_id');
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['usuario_id', 'sucursal_id', 'clave'], 'pago_idempotencia_alcance_uk');
            $table->index('grupo_pago_id', 'pago_idempotencia_grupo_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_idempotencia');
    }
};
