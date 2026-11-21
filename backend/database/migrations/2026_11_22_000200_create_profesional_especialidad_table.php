<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Especialidades del profesional (03/RF-D09, 03/RF-E04, 03/RF-E05, 03/RN-02).
     *
     * Única fuente de verdad de la especialidad principal. `activo` conserva
     * el historial sin borrado físico. Los índices parciales garantizan:
     * - una sola relación activa por profesional–especialidad (03/RF-E07);
     * - exactamente una principal activa por profesional (03/RN-02).
     */
    public function up(): void
    {
        Schema::create('profesional_especialidad', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesional_id')->constrained('profesional')->restrictOnDelete();
            $table->foreignId('especialidad_id')->constrained('especialidad')->restrictOnDelete();
            $table->enum('tipo', ['principal', 'secundaria'])->default('principal');
            $table->boolean('activo')->default(true);
            $table->timestampsTz();

            $table->index('especialidad_id');
            $table->index('profesional_id');
            $table->index(['profesional_id', 'activo']);
        });

        DB::statement(
            'CREATE UNIQUE INDEX profesional_especialidad_activa_uk
                ON profesional_especialidad (profesional_id, especialidad_id)
                WHERE activo'
        );

        DB::statement(
            "CREATE UNIQUE INDEX profesional_especialidad_principal_uk
                ON profesional_especialidad (profesional_id)
                WHERE tipo = 'principal' AND activo"
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('profesional_especialidad');
    }
};
