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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // 'generic', 'invitation', 'join'
            $table->string('title')->nullable();
            $table->text('content');
            $table->unsignedBigInteger('target_user_id')->nullable(); // NULL for generic messages
            $table->timestamps();

            // Foreign key constraint (if you have a users table)
            $table->foreign('target_user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
