<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Campos de negocio del usuario (módulo 02 — Fase 1).
     *
     * Todos son nullable o tienen default para no romper instalaciones
     * existentes ni la factory de tests. `documento` y `username` se exigen
     * a nivel de aplicación al crear o editar usuarios.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 100)->nullable()->unique();
            $table->string('documento', 20)->nullable()->unique();
            $table->boolean('activo')->default(true);
            $table->text('motivo_estado')->nullable();
            $table->timestampTz('estado_cambiado_en')->nullable();
            $table->timestampTz('ultimo_acceso')->nullable();
            $table->unsignedSmallInteger('intentos_fallidos')->default(0);
            $table->timestampTz('bloqueado_hasta')->nullable();
            $table->boolean('debe_cambiar_password')->default(false);
            $table->timestampTz('password_actualizada_en')->nullable();
            $table->unsignedInteger('version')->default(1);

            $table->index('activo');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropUnique(['documento']);
            $table->dropIndex(['activo']);
            $table->dropColumn([
                'username',
                'documento',
                'activo',
                'motivo_estado',
                'estado_cambiado_en',
                'ultimo_acceso',
                'intentos_fallidos',
                'bloqueado_hasta',
                'debe_cambiar_password',
                'password_actualizada_en',
                'version',
            ]);
        });
    }
};
