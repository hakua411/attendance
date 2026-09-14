<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('attendance_record_id')
                ->constrained('attendance_records')
                ->cascadeOnDelete();
            $table->time('new_clock_in')->nullable();
            $table->time('new_clock_out')->nullable();
            $table->text('comment');
            $table->unsignedTinyInteger('approval_status')->default(0);
            $table->dateTime('application_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
