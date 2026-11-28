<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pagos (módulo 08, submódulo 01).
     *
     * Documento: no se edita ni se borra. Solo cambia a `anulado` (submódulo 8.4,
     * RN-006). Una fila por línea de pago; un grupo (`grupo_pago_id`) agrupa las
     * líneas de un pago combinado (CU-01 FA-01).
     *
     * Los usuarios se referencian en `users` (tabla real de autenticación).
     * La entidad pagable va en FKs nullable con CHECK de "exactamente una".
     * `venta_material_id` queda sin FK hasta que exista 10.3.
     */
    public function up(): void
    {
        Schema::create('pago', function (Blueprint $table) {
            $table->id();
            $table->string('numero_correlativo', 30);
            $table->foreignId('sucursal_id')->constrained('sucursal')->restrictOnDelete();
            $table->string('sucursal_codigo_snapshot', 50);
            $table->string('sucursal_nombre_snapshot', 150);
            $table->foreignId('paciente_id')->constrained('paciente')->restrictOnDelete();
            $table->string('paciente_nombre_snapshot', 255);
            $table->foreignId('tutor_id')->constrained('tutor')->restrictOnDelete();
            $table->string('tutor_nombre_snapshot', 255);
            $table->foreignId('inscripcion_sala_id')->nullable()->constrained('inscripcion_sala')->restrictOnDelete();
            $table->foreignId('paquete_sesiones_id')->nullable()->constrained('paquete_sesiones')->restrictOnDelete();
            $table->foreignId('atencion_entrevista_id')->nullable()->constrained('atencion_entrevista')->restrictOnDelete();
            $table->foreignId('atencion_diagnostico_id')->nullable()->constrained('atencion_diagnostico')->restrictOnDelete();
            $table->unsignedBigInteger('venta_material_id')->nullable();
            $table->string('entidad_referencia_snapshot', 255);
            $table->decimal('monto_esperado_snapshot', 12, 2);
            $table->decimal('monto', 12, 2);
            $table->decimal('saldo_resultante', 12, 2);
            $table->string('medio_pago', 20);
            $table->string('referencia_pago', 100)->nullable();
            $table->uuid('grupo_pago_id');
            $table->date('fecha_pago');
            $table->boolean('fecha_futura_autorizada')->default(false);
            $table->text('motivo_fecha_futura')->nullable();
            $table->foreignId('autorizado_por_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->string('estado', 20)->default('vigente');
            $table->timestampTz('anulado_en')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('motivo_anulacion')->nullable();
            $table->foreignId('usuario_registro_id')->constrained('users')->restrictOnDelete();
            $table->timestampsTz();

            $table->unique(['sucursal_id', 'numero_correlativo'], 'pago_sucursal_numero_uk');
            $table->index(['sucursal_id', 'fecha_pago'], 'pago_sucursal_fecha_idx');
            $table->index(['sucursal_id', 'estado', 'fecha_pago'], 'pago_sucursal_estado_fecha_idx');
            $table->index(['paciente_id', 'fecha_pago'], 'pago_paciente_fecha_idx');
            $table->index('grupo_pago_id', 'pago_grupo_idx');
            $table->index('inscripcion_sala_id', 'pago_inscripcion_idx');
            $table->index('paquete_sesiones_id', 'pago_paquete_idx');
            $table->index('atencion_entrevista_id', 'pago_entrevista_idx');
            $table->index('atencion_diagnostico_id', 'pago_diagnostico_idx');
            $table->index('medio_pago', 'pago_medio_idx');
            $table->index('usuario_registro_id', 'pago_usuario_idx');
        });

        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE pago ADD CONSTRAINT pago_medio_pago_fk FOREIGN KEY (medio_pago) REFERENCES medio_pago(codigo) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE pago ADD CONSTRAINT pago_monto_positivo CHECK (monto > 0)');
        DB::statement('ALTER TABLE pago ADD CONSTRAINT pago_monto_esperado_no_negativo CHECK (monto_esperado_snapshot >= 0)');
        DB::statement('ALTER TABLE pago ADD CONSTRAINT pago_saldo_no_negativo CHECK (saldo_resultante >= 0)');
        DB::statement("ALTER TABLE pago ADD CONSTRAINT pago_estado_valido CHECK (estado IN ('vigente','anulado'))");
        DB::statement(<<<'SQL'
            ALTER TABLE pago ADD CONSTRAINT pago_exactamente_una_entidad CHECK (
                (inscripcion_sala_id IS NOT NULL)::int +
                (paquete_sesiones_id IS NOT NULL)::int +
                (atencion_entrevista_id IS NOT NULL)::int +
                (atencion_diagnostico_id IS NOT NULL)::int = 1
            )
        SQL);
        DB::statement('ALTER TABLE pago ADD CONSTRAINT pago_venta_material_pendiente CHECK (venta_material_id IS NULL)');
        DB::statement('ALTER TABLE pago ADD CONSTRAINT pago_motivo_futuro_obligatorio CHECK (fecha_futura_autorizada = FALSE OR (motivo_fecha_futura IS NOT NULL AND char_length(trim(motivo_fecha_futura)) > 0))');
        DB::statement("ALTER TABLE pago ADD CONSTRAINT pago_referencia_qr_obligatoria CHECK (medio_pago <> 'qr' OR (referencia_pago IS NOT NULL AND char_length(trim(referencia_pago)) > 0))");

        DB::unprepared(<<<'SQL'
            CREATE OR REPLACE FUNCTION fn_pago_inmutable()
            RETURNS TRIGGER AS $$
            BEGIN
                IF TG_OP = 'DELETE' THEN
                    RAISE EXCEPTION 'Los pagos no se eliminan: se anulan con motivo (RN-006).';
                END IF;

                IF OLD.estado = 'vigente' AND NEW.estado = 'anulado'
                   AND (NEW.numero_correlativo, NEW.sucursal_id, NEW.paciente_id, NEW.tutor_id,
                        NEW.inscripcion_sala_id, NEW.paquete_sesiones_id,
                        NEW.atencion_entrevista_id, NEW.atencion_diagnostico_id,
                        NEW.monto_esperado_snapshot, NEW.monto, NEW.saldo_resultante,
                        NEW.medio_pago, NEW.fecha_pago, NEW.grupo_pago_id,
                        NEW.usuario_registro_id)
                       IS NOT DISTINCT FROM
                       (OLD.numero_correlativo, OLD.sucursal_id, OLD.paciente_id, OLD.tutor_id,
                        OLD.inscripcion_sala_id, OLD.paquete_sesiones_id,
                        OLD.atencion_entrevista_id, OLD.atencion_diagnostico_id,
                        OLD.monto_esperado_snapshot, OLD.monto, OLD.saldo_resultante,
                        OLD.medio_pago, OLD.fecha_pago, OLD.grupo_pago_id,
                        OLD.usuario_registro_id) THEN
                    RETURN NEW;
                END IF;

                RAISE EXCEPTION 'Los pagos son inmutables: solo pueden anularse con motivo (RN-006).';
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER tg_pago_inmutable
            BEFORE UPDATE OR DELETE ON pago
            FOR EACH ROW EXECUTE FUNCTION fn_pago_inmutable();
        SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS tg_pago_inmutable ON pago');
            DB::unprepared('DROP FUNCTION IF EXISTS fn_pago_inmutable()');
        }

        Schema::dropIfExists('pago');
    }
};
