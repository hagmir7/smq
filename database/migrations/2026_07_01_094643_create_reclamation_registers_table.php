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
        Schema::create('reclamation_registers', function (Blueprint $table) {
            $table->id();
                 // Date Réclamation
            $table->date('complaint_date')->nullable()
                ->comment('Date de la réclamation');

            // Date d'enregistrement
            $table->date('registration_date')->nullable()
                ->comment("Date d'enregistrement de la réclamation");

            // Client
            $table->string('client_name')->nullable()
                ->comment('Nom du client');

            // Objet
            $table->string('subject')->nullable()
                ->comment('Objet de la réclamation');

            // Actions proposées
            $table->text('proposed_actions')->nullable()
                ->comment('Actions proposées pour traiter la réclamation');

            // Date prévisionnelle
            $table->date('planned_date')->nullable()
                ->comment('Date prévisionnelle de réalisation');

            // Date réelle de réalisation
            $table->date('actual_completion_date')->nullable()
                ->comment('Date réelle de réalisation');

            // N° de la fiche d'amélioration
            $table->string('improvement_sheet_number')->nullable()
                ->comment("Numéro de la fiche d'amélioration");

            // Date de clôture
            $table->date('closing_date')->nullable()
                ->comment('Date de clôture de la réclamation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamation_registers');
    }
};
