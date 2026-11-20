<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitácora de auditoría (solo inserción).
     *
     * Módulo 01 — Organización y sucursales / RF-011 / RNF-013.
     * Mientras el módulo 02 no formalice la tabla `usuario`, la FK de usuario
     * apunta a la tabla real de autenticación `users`.
     */
    public function up(): void
    {
        Schema::create('bitacora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursal')->nullOnDelete();
            $table->string('accion', 100);
            $table->string('entidad', 100)->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->jsonb('datos_anteriores')->nullable();
            $table->jsonb('datos_nuevos')->nullable();
            $table->text('motivo')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestampTz('created_at')->useCurrent();

            $table->index('usuario_id');
            $table->index('created_at');
            $table->index(['entidad', 'entidad_id'], 'idx_bitacora_entidad');
            $table->index('accion');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION fn_bitacora_inmutable()
                RETURNS TRIGGER AS $$
                BEGIN
                    RAISE EXCEPTION 'La bitácora es inmutable';
                END;
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER tg_bitacora_inmutable
                BEFORE UPDATE OR DELETE ON bitacora
                FOR EACH ROW EXECUTE FUNCTION fn_bitacora_inmutable();
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS tg_bitacora_inmutable ON bitacora');
            DB::unprepared('DROP FUNCTION IF EXISTS fn_bitacora_inmutable()');
        }

        Schema::dropIfExists('bitacora');
    }
};
