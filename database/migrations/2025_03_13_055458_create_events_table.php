<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('type'); // FLT, SBY, DO, CI, CO, UNK
            $table->string('flight_number')->nullable();
            $table->string('departure')->nullable();
            $table->string('arrival')->nullable();
            $table->time('std_utc')->nullable(); // Scheduled Time Departure
            $table->time('sta_utc')->nullable(); // Scheduled Time Arrival
            $table->time('check_in_utc')->nullable();
            $table->time('check_out_utc')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
