<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('fe_tiendas', function (Blueprint $table) {
            // Serie SUNAT para NC/ND (FC01..FC99). Independiente de la serie FAC/BOL
            // porque NC usa prefijo de 2 letras y solo quedan 2 dígitos en los 4 chars del serie SUNAT.
            $table->smallInteger('numero_serie_nc')->nullable()->after('ultimo_idtransaccion_capturado');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('fe_tiendas', function (Blueprint $table) {
            $table->dropColumn('numero_serie_nc');
        });
    }
};
