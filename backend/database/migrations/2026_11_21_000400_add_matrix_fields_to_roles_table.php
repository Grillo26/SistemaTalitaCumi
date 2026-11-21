<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Metadatos de la matriz de permisos (RF-R08, RF-R13, RN-16).
     *
     * `version` habilita concurrencia optimista por rol.
     * `es_superadmin` identifica el rol protegido sin decidir por nombre.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->unsignedInteger('version')->default(1);
            $table->boolean('es_superadmin')->default(false);

            $table->index('es_superadmin');
        });

        $driver = DB::getDriverName();

        if ($driver === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX roles_es_superadmin_unique ON roles (es_superadmin) WHERE es_superadmin = true');
        } elseif ($driver === 'sqlite') {
            DB::statement('CREATE UNIQUE INDEX roles_es_superadmin_unique ON roles (es_superadmin) WHERE es_superadmin = 1');
        }
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS roles_es_superadmin_unique');
        }

        Schema::table('roles', function (Blueprint $table) {
            $table->dropIndex(['es_superadmin']);
            $table->dropColumn(['version', 'es_superadmin']);
        });
    }
};
