<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ficha de ingreso administrativa y revisiones inmutables (04.03 Fase A).
     *
     * Una ficha abierta por paciente; las anteriores quedan archivadas.
     */
    public function up(): void
    {
        Schema::create('ficha_ingreso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('paciente')->restrictOnDelete();
            $table->foreignId('sucursal_ingreso_id')->constrained('sucursal')->restrictOnDelete();
            $table->foreignId('tutor_principal_id')->nullable()->constrained('tutor')->restrictOnDelete();
            $table->text('motivo_consulta_inicial');
            $table->date('fecha_ingreso');
            $table->text('composicion_familiar')->nullable();
            $table->text('antecedentes_familiares')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('derivado_por', 200)->nullable();
            $table->string('derivado_por_institucion', 150)->nullable();
            $table->string('derivado_por_contacto', 150)->nullable();
            $table->string('estado', 20)->default('borrador');
            $table->string('estado_anterior', 20)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('usuario_registro_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('completada_en')->nullable();
            $table->foreignId('completada_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestampTz('archivada_en')->nullable();
            $table->foreignId('archivada_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('motivo_archivado')->nullable();
            $table->timestampsTz();

            $table->index('paciente_id');
            $table->index('sucursal_ingreso_id');
            $table->index('fecha_ingreso');
            $table->index(['paciente_id', 'estado']);
        });

        // Máximo una ficha no archivada por paciente. SQLite y PostgreSQL
        // soportan índices únicos parciales.
        DB::statement(
            "CREATE UNIQUE INDEX ficha_ingreso_abierta_uk
             ON ficha_ingreso (paciente_id)
             WHERE estado <> 'archivada'"
        );

        Schema::create('ficha_ingreso_revision', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ficha_ingreso_id')->constrained('ficha_ingreso')->restrictOnDelete();
            $table->unsignedInteger('version');
            $table->string('accion', 30);
            $table->json('snapshot');
            $table->json('campos_modificados')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->unique(['ficha_ingreso_id', 'version']);
            $table->index('usuario_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ficha_ingreso_revision');
        Schema::dropIfExists('ficha_ingreso');
    }
};
