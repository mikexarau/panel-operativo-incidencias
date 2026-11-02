<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_logs', function (Blueprint $table) {
            $table->id();
            // restrict: borrar una incidencia nunca debe poder borrar su trazabilidad.
            $table->foreignId('incident_id')->constrained()->restrictOnDelete();
            $table->string('action', 40);
            // Sin CHECK en old_value/new_value: un log conserva lo que ocurrió,
            // aunque sea incoherente, para que el diagnóstico pueda detectarlo.
            $table->string('old_value', 50)->nullable();
            $table->string('new_value', 50)->nullable();
            $table->string('user_name', 100);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // Un log es inmutable salvo por esta marca: se anula, no se borra.
            $table->timestamp('voided_at')->nullable();

            // Cubre el historial y la búsqueda del último log por incidencia.
            $table->index(['incident_id', 'created_at']);
        });

        DB::statement("ALTER TABLE incident_logs ADD CONSTRAINT incident_logs_action_check CHECK (action IN ('created', 'status_changed', 'reopened', 'status_reconciled', 'log_voided'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_logs');
    }
};
