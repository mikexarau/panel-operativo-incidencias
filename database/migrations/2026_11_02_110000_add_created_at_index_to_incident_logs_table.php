<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * La actividad reciente ordena todos los logs por fecha, sin filtrar por incidencia,
     * y el índice (incident_id, created_at) no sirve para eso.
     */
    public function up(): void
    {
        Schema::table('incident_logs', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table('incident_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
        });
    }
};
