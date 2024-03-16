<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('challenges', function (Blueprint $table) {
            // Add the start_date column
            $table->date('start_date')->default('2024-01-01')->after('group_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('challenges', function (Blueprint $table) {
            // Remove the start_date column if the migration is rolled back
            $table->dropColumn('start_date');
        });
    }
};
