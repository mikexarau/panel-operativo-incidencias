<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cola de inconsistencias que el diagnostico no corrige solo porque no hay
     * informacion suficiente. Las abre incidents:diagnose --fix y las cierra una
     * decision humana con incidents:resolve-issue.
     */
    public function up(): void
    {
        Schema::create('incident_data_issues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->restrictOnDelete();
            $table->string('rule', 40);
            // Identifica el problema concreto: hace idempotentes las ejecuciones repetidas.
            $table->string('fingerprint', 120)->unique();
            $table->json('details');
            $table->timestamp('resolved_at')->nullable();
            $table->string('resolved_by', 100)->nullable();
            $table->string('resolution', 30)->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['incident_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_data_issues');
    }
};
