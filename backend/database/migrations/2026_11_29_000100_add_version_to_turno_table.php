<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * 07.04 — Registro de asistencia por turno.
     *
     * Versión optimista para correcciones concurrentes. El estado sigue
     * siendo la barrera principal: 07.03 escribe cancelado/reprogramado y
     * 07.04 solo permite asistió/faltó sobre turnos programados.
     */
    public function up(): void
    {
        Schema::table('turno', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1)->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('turno', function (Blueprint $table) {
            $table->dropColumn('version');
        });
    }
};
