<?php

use App\Models\ImprovementSheet;
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
        Schema::create('improvement_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ImprovementSheet::class);
            $table->string('code')->unique();
            $table->longText('description');
            $table->foreignIdFor(User::class, 'responsable_id');
            $table->foreignIdFor(Service::class);
            $table->string('effectiveness_criteria', 500)->nullable();
            $table->date('due_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->string('effectiveness', 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('improvement_actions');
    }
};
