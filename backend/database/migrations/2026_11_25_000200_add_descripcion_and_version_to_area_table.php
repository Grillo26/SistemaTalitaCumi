<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Completa el catálogo de áreas del módulo 05.
     *
     * `descripcion` es parte de RF-C01. `version` habilita concurrencia
     * optimista como en el resto de catálogos. El índice funcional refuerza
     * la unicidad normalizada de RN-C02/RF-C03.
     */
    public function up(): void
    {
        Schema::table('area', function (Blueprint $table) {
            $table->text('descripcion')->nullable()->after('nombre');
            $table->unsignedInteger('version')->default(1)->after('activo');
        });

        DB::statement(
            'CREATE UNIQUE INDEX area_nombre_normalizado_uk '
            .'ON area ((lower(trim(nombre))))',
        );
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS area_nombre_normalizado_uk');

        Schema::table('area', function (Blueprint $table) {
            $table->dropColumn(['descripcion', 'version']);
        });
    }
};
