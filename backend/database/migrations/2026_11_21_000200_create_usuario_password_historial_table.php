<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Historial de hashes de contraseña (RF-A02 / RN-12).
     *
     * Nunca guarda contraseñas en claro. Solo se conservan los últimos N
     * hashes previos según el parámetro password_historial_cantidad.
     */
    public function up(): void
    {
        Schema::create('usuario_password_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->string('password_hash', 255);
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['usuario_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_password_historial');
    }
};
