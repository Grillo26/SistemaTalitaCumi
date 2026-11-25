<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pacientes del módulo 04.01.
     *
     * Paciente global (04.01/RN-001). `sucursal_registro_id` es el ancla de
     * visibilidad, no una duplicación por sucursal. Sin SoftDeletes: el
     * paciente se retira cambiando `estado` (04.01/RF-009, 04.01/RN-006).
     */
    public function up(): void
    {
        Schema::create('paciente', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->nullable()->unique();
            $table->string('nombre', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->date('fecha_nacimiento');
            $table->enum('sexo', ['M', 'F']);
            $table->string('ci', 20)->nullable();
            $table->unsignedBigInteger('tipo_diagnostico_id')->nullable();
            $table->foreignId('sucursal_registro_id')->constrained('sucursal')->restrictOnDelete();
            $table->enum('estado', ['activo', 'en_pausa', 'retirado'])->default('activo');
            $table->text('alergias')->nullable();
            $table->text('condiciones_medicas')->nullable();
            $table->text('observaciones')->nullable();
            $table->string('nombre_normalizado', 100);
            $table->string('apellido_paterno_normalizado', 100);
            $table->string('apellido_materno_normalizado', 100)->nullable();
            $table->string('nombre_completo_normalizado', 320);
            $table->string('documento_normalizado', 30)->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();

            $table->index('estado');
            $table->index('fecha_nacimiento');
            $table->index('sucursal_registro_id');
            $table->index('tipo_diagnostico_id');
            $table->index('nombre_normalizado');
            $table->index('apellido_paterno_normalizado');
            $table->index('apellido_materno_normalizado');
            $table->index('nombre_completo_normalizado');
            $table->index('updated_at');
            $table->unique('documento_normalizado', 'paciente_documento_normalizado_uk');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paciente');
    }
};
