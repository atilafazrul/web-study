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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('course')->nullable();
            $table->string('category')->default('Programming');
            $table->text('description')->nullable();
            $table->string('due_date')->default('Oct 29, 2026');
            $table->string('duration')->default('2h');
            $table->string('priority')->default('High'); // High, Medium, Low
            $table->integer('priority_score')->default(80);
            $table->string('status')->default('pending'); // pending, completed
            $table->json('subtasks')->nullable();
            $table->string('assigned_day')->nullable(); // Mon, Tue, Wed, Thu, Fri, Sat, Sun
            $table->string('assigned_time')->nullable(); // 7 PM, 8 PM, 9 PM
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
