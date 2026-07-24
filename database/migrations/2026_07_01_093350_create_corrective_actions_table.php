<?php

use App\Models\CorrectiveAction;
use App\Models\Reclamation;
use App\Models\Service;
use App\Models\User;
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
        Schema::create('corrective_actions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->longText('description');
            $table->string('type', 30)->default('Action corrective');
            $table->string('effectiveness_criteria', 500)->nullable(); // critères d'efficacité
            $table->date('due_date')->nullable(); // date d'échéance
            $table->date('completion_date')->nullable(); // Date de réalisation
            $table->string('status')->nullable();

            $table->string('effectiveness', 20)->nullable(); // Efficacité
            $table->foreignIdFor(Reclamation::class)->constrained()->cascadeOnDelete();
            $table->date('closing_date')->nullable();

            $table->foreignIdFor(Service::class)
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            $table->foreignIdFor(User::class, 'responsable_id')->nullable();
            $table->foreignIdFor(User::class)
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignIdFor(CorrectiveAction::class, 'parent_id')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('corrective_actions');
    }
};
