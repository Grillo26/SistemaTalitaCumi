<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Catálogo y eventos de consentimientos (04.03 Fase B).
     *
     * El catálogo es administrable y no usa enum. Los consentimientos son
     * eventos inmutables: no se editan ni se borran; la revocación es una
     * fila nueva que referencia al consentimiento original.
     */
    public function up(): void
    {
        Schema::create('consentimiento_tipo', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 60)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->timestampsTz();

            $table->index('activo');
            $table->index('orden');
        });

        Schema::create('consentimiento', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ficha_ingreso_id')->constrained('ficha_ingreso')->restrictOnDelete();
            $table->foreignId('consentimiento_tipo_id')->constrained('consentimiento_tipo')->restrictOnDelete();
            $table->foreignId('tutor_id')->constrained('tutor')->restrictOnDelete();
            $table->string('resultado', 20);
            $table->date('fecha');
            $table->text('motivo')->nullable();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('revoca_a_id')->nullable()->constrained('consentimiento')->restrictOnDelete();
            $table->timestampTz('created_at')->useCurrent();

            $table->index(['ficha_ingreso_id', 'resultado']);
            $table->index(['consentimiento_tipo_id', 'resultado']);
            $table->index('tutor_id');
            $table->index('revoca_a_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consentimiento');
        Schema::dropIfExists('consentimiento_tipo');
    }
};
