<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('fe_tiendas', function (Blueprint $table) {
            // Fecha mínima de FECHA_DOCUMENTO a capturar.
            // CENTRAL: limita el histórico (ej. '2026-10-01' en producción).
            // TIENDA: NULL = sin límite (usa cursor para deduplicar).
            $table->date('fecha_inicio_captura')->nullable()->after('tipo_fuente');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('fe_tiendas', function (Blueprint $table) {
            $table->dropColumn('fecha_inicio_captura');
        });
    }
};
