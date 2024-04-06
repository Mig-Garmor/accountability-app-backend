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
        Schema::create('completed_tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedInteger('day');
            $table->timestamps();

            // Assuming you have a tasks table, you should add a foreign key constraint.
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');

            // Unique constraint to prevent duplicate entries for the same task and day.
            $table->unique(['task_id', 'day'], 'task_day_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('completed_tasks');
    }
};
