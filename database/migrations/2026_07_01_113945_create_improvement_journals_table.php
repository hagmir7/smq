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

            // Link to the originating record (ImprovementSheet or ImprovementAction)
            $table->nullableMorphs('source'); // source_type + source_id

            // Description du Constat
            $table->date('date')->nullable();
            $table->string('finding_source', 255)->nullable();
            $table->text('initial_finding_description')->nullable();
            $table->text('root_cause_analysis')->nullable();

            // Description de l'action
            $table->text('action')->nullable();
            $table->string('action_type', 255)->nullable();

            // Planification de l'action
            $table->string('process', 255)->nullable();
            $table->string('responsible', 255)->nullable();
            $table->string('planned_deadline', 255)->nullable();

            // Réalisation et suivi de l'efficacité de l'action
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