<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('fe_tiendas', function (Blueprint $table) {
            // 'TIENDA' = ERP por tienda (flujo original)
            // 'CENTRAL' = BD central (NCs y docs generados en central, sin ERP propio)
            $table->string('tipo_fuente', 10)->default('TIENDA')->after('estado');

            // Para CENTRAL: servidor_host guarda la IP del Bizlinks dedicado (10.20.3.154).
            // idsucursal_soluflex no aplica para CENTRAL (cada NC trae su propio IDSUCURSAL).
            $table->integer('idsucursal_soluflex')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('fe_tiendas', function (Blueprint $table) {
            $table->dropColumn('tipo_fuente');
            $table->integer('idsucursal_soluflex')->nullable(false)->change();
        });
    }
};
