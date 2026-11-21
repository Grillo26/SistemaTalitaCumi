<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivote usuario–sucursal (RF-U05, RF-U16 / RN-03).
     *
     * Se usa el nombre documentado en database.md y los README.
     */
    public function up(): void
    {
        Schema::create('usuario_sucursal', function (Blueprint $table) {
            $table->foreignId('usuario_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursal')->cascadeOnDelete();

            $table->primary(['usuario_id', 'sucursal_id']);
            $table->index('sucursal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuario_sucursal');
    }
};
