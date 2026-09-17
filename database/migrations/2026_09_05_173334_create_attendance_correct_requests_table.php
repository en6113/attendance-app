<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_correct_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_record_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_direct_edit')->default(false);
            $table->date('old_date')->nullable();
            $table->dateTime('old_clock_in')->nullable();
            $table->dateTime('old_clock_out')->nullable();
            $table->string('old_comment', 255)->nullable();
            $table->json('old_breaks')->nullable();
            $table->date('new_date');
            $table->string('new_clock_in');
            $table->string('new_clock_out');
            $table->string('comment', 255);
            $table->timestamp('approved_at')->nullable();
            $table->date('application_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_correct_requests');
    }
};
