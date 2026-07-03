<?php
use App\Models\CorrectiveAction;
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
        Schema::create('improvement_sheets', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();
            $table->foreignIdFor(CorrectiveAction::class)->nullable();
            $table->string('finding_source', 100)->default('Action corrective');

            $table->longText('description')->nullable();
            $table->longText('cause_analysis')->nullable();

            $table->string('title', 500)->nullable();
            $table->foreignIdFor(User::class, 'responsable_id')->nullable();
            $table->foreignIdFor(Service::class)->nullable();
            $table->string('impact', 20)->nullable();
            $table->string('statut', 30)->default('Planifié');
            // Evaluation
            $table->boolean('closed')->nullable();
            $table->boolean('effectiveness')->nullable();
            $table->longText('observation_description')->nullable();
            $table->date('observation_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('improvement_sheets');
    }
};
