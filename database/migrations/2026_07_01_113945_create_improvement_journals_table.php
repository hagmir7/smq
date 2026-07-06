<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('improvement_journals', function (Blueprint $table) {
            $table->id();

            // Link back to the originating record (ImprovementSheet or ImprovementAction)
            $table->nullableMorphs('source'); // creates source_type + source_id, nullable

            // Description du Constat
            $table->date('date')->nullable();
            $table->string('finding_source')->nullable();
            $table->text('initial_finding_description')->nullable();
            $table->text('root_cause_analysis')->nullable();

            // Description de l'action
            $table->text('action')->nullable();
            $table->string('action_type')->nullable();

            // Planification de l'action
            $table->string('process')->nullable();
            $table->string('responsible')->nullable();
            $table->string('planned_deadline')->nullable();

            // Réalisation et suivi de l'efficacité
            $table->date('actual_date')->nullable();
            $table->text('effectiveness_criteria')->nullable();
            $table->boolean('effectiveness')->nullable();
            $table->date('closure_date')->nullable();

            // Observations
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('improvement_journals');
    }
};