<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('central')->table('fe_control_registros', function (Blueprint $table) {
            $table->string('estado_bizlinks', 5)->nullable()->after('estado');
            $table->string('codigo_error_bizlinks', 20)->nullable()->after('estado_bizlinks');
            $table->string('mensaje_bizlinks', 500)->nullable()->after('codigo_error_bizlinks');
            $table->timestamp('fecha_sync_bizlinks')->nullable()->after('mensaje_bizlinks');
        });
    }

    public function down(): void
    {
        Schema::connection('central')->table('fe_control_registros', function (Blueprint $table) {
            $table->dropColumn(['estado_bizlinks', 'codigo_error_bizlinks', 'mensaje_bizlinks', 'fecha_sync_bizlinks']);
        });
    }
};
