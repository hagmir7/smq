<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('improvement_journals', function (Blueprint $table) {
            $table->id();
            // Description du Constat
            $table->date('date')->nullable();
            $table->string('finding_source')->nullable(); // ex: MRQ (Matrice des Risques et Opportunités)
            $table->text('initial_finding_description')->nullable(); // NC, R/O, Amélioration...
            $table->text('root_cause_analysis')->nullable();

            // Description de l'action
            $table->text('action')->nullable();
            $table->string('action_type')->nullable(); // ex: A. face aux R/O

            // Planification de l'action
            $table->string('process')->nullable(); // ex: HSE (Hygiène & Sécurité)
            $table->string('responsible')->nullable(); // ex: Pilote HSE
            $table->string('planned_deadline')->nullable(); // ex: Selon plan de formation validé

            // Réalisation et suivi de l'éfficacité de l'action
            $table->date('actual_date')->nullable();
            $table->text('effectiveness_criteria')->nullable();
            $table->boolean('effectiveness')->nullable();
            $table->date('closure_date')->nullable();

            // Observations
            $table->text('observations')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('improvement_journals');
    }
};
