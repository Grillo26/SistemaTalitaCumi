<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Paquetes de sesiones individuales (módulo 06, submódulo 02).
     *
     * Tabla propia del schema `paquete_sesiones`. No se comparte una tabla
     * genérica con salas grupales ni con diagnósticos.
     */
    public function up(): void
    {
        Schema::create('paquete_sesiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('paciente_id')->constrained('paciente')->restrictOnDelete();
            $table->foreignId('tutor_responsable_id')->constrained('tutor')->restrictOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursal')->restrictOnDelete();
            $table->foreignId('servicio_id')->constrained('servicio')->restrictOnDelete();
            $table->foreignId('profesional_id')->nullable()->constrained('profesional')->restrictOnDelete();
            $table->foreignId('tarifa_id')->constrained('tarifa')->restrictOnDelete();
            $table->foreignId('categoria_tarifa_id')->constrained('categoria_tarifa')->restrictOnDelete();
            $table->unsignedInteger('cantidad_sesiones');
            $table->string('dias_semana', 50);
            $table->time('hora');
            $table->unsignedInteger('duracion_minutos')->default(50);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->decimal('monto_esperado', 12, 2);
            $table->string('estado', 20)->default('activo');
            $table->foreignId('paquete_origen_id')
                ->nullable()
                ->constrained('paquete_sesiones')
                ->restrictOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestampsTz();

            $table->index('estado', 'paquete_sesiones_estado_idx');
            $table->index(['fecha_inicio', 'fecha_fin'], 'paquete_sesiones_fechas_idx');
            $table->index(
                ['sucursal_id', 'fecha_inicio', 'fecha_fin'],
                'paquete_sesiones_sucursal_periodo_idx',
            );
            $table->index(['paciente_id', 'estado'], 'paquete_sesiones_paciente_estado_idx');
            $table->index(['servicio_id', 'fecha_inicio'], 'paquete_sesiones_servicio_fecha_idx');
            $table->index(['profesional_id', 'fecha_inicio'], 'paquete_sesiones_profesional_fecha_idx');
            $table->index('paquete_origen_id', 'paquete_sesiones_origen_idx');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS btree_gist');

            DB::statement(
                'ALTER TABLE paquete_sesiones '
                ."ADD CONSTRAINT paquete_sesiones_estado_valido CHECK (estado IN ('activo','en_pausa','vencido','renovado','retirado'))",
            );

            DB::statement(
                'ALTER TABLE paquete_sesiones '
                .'ADD CONSTRAINT paquete_sesiones_fechas_coherentes CHECK (fecha_fin >= fecha_inicio)',
            );

            DB::statement(
                'ALTER TABLE paquete_sesiones '
                .'ADD CONSTRAINT paquete_sesiones_monto_no_negativo CHECK (monto_esperado >= 0)',
            );

            DB::statement(
                'ALTER TABLE paquete_sesiones '
                .'ADD CONSTRAINT paquete_sesiones_cantidad_positiva CHECK (cantidad_sesiones > 0)',
            );

            DB::statement(
                'ALTER TABLE paquete_sesiones '
                .'ADD CONSTRAINT paquete_sesiones_duracion_positiva CHECK (duracion_minutos > 0)',
            );

            DB::statement(
                'ALTER TABLE paquete_sesiones '
                ."ADD CONSTRAINT paquete_sesiones_dias_validos CHECK (dias_semana ~ '^[1-6](,[1-6])*$')",
            );

            DB::statement(<<<'SQL'
                ALTER TABLE paquete_sesiones
                ADD CONSTRAINT paquete_sesiones_sin_solapamiento
                EXCLUDE USING gist (
                    paciente_id WITH =,
                    servicio_id WITH =,
                    daterange(fecha_inicio, fecha_fin, '[]') WITH &&
                )
                WHERE (estado IN ('activo', 'en_pausa'))
            SQL);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('paquete_sesiones');
    }
};
