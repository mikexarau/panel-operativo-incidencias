<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->text('description');
            // string + enum de PHP en lugar de ENUM de MySQL: anadir un estado no exige
            // reescribir la tabla, y los CHECK de abajo mantienen la defensa en base de datos.
            $table->string('status', 20)->default('open');
            $table->string('priority', 20)->default('medium');
            $table->string('requester_name', 100);
            // Nombre libre: no hay tabla de usuarios porque la autenticación queda fuera de alcance.
            $table->string('assigned_to', 100)->nullable();
            $table->timestamps();

            // Filtros habituales del listado, siempre ordenado por fecha. Con estado y
            // prioridad a la vez, MySQL usa uno de estos y filtra el resto sobre pocas filas.
            $table->index(['status', 'created_at']);
            $table->index(['priority', 'created_at']);
            $table->index('created_at');
        });

        // Valores literales a propósito: una migración es una foto del esquema en su momento.
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_status_check CHECK (status IN ('open', 'in_review', 'blocked', 'resolved'))");
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_priority_check CHECK (priority IN ('low', 'medium', 'high', 'critical'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
