<?php
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
        Schema::create('reclamations', function (Blueprint $table) {
            $table->id();
            // Step 1
            $table->date('claimant_date');
            $table->string('code')->unique();
            $table->string('claimant_name', 200);
            $table->string('client_code');
            $table->string('client_phone', 30)->nullable();
            $table->string('client_email', 255)->nullable();
            $table->string('client_company_name', 200)->nullable();
            $table->string('reception_method', 500)->nullable();
            $table->string('object', 500);
            $table->longText('description');

            // Step 2
            $table->longText('post_analysis')->nullable();
            $table->boolean('is_recevable')->nullable();
            $table->longText('corrective_action')->nullable();

            // Step 3
            $table->longText('processing_analysis')->nullable();
            $table->boolean('is_justifiee')->nullable();
            $table->longText('cause_analysis')->nullable();
            $table->string('priority', 20)->default('Normale');
            //  outo
            $table->enum('statut', ['Ouverte', 'En cours', 'Clôturée'])->default('Ouverte');
            $table->integer('workflow_step')->default(1);
            $table->foreignIdFor(User::class, 'responsable_id')->nullable();
            $table->date('planned_closing_date')->nullable();
            $table->date('closing_date')->nullable();
            $table->date('received_at')->nullable();
            $table->foreignIdFor(User::class);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reclamations');
    }
};
