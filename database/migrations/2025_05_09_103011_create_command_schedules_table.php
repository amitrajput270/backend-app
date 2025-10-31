<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommandSchedulesTable extends Migration
{
    public function up(): void
    {
        Schema::create('command_schedules', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['LARAVEL_COMMAND', 'SHELL_COMMAND'])
                ->default('LARAVEL_COMMAND');
            $table->text('environments')
                ->nullable()
                ->collation('utf8mb4_bin');
            $table->string('command', 255)
                ->unique();
            $table->string('schedule', 255);
            $table->boolean('is_active')
                ->default(true);
            $table->boolean('is_overlapping')
                ->default(false);
            $table->string('ping_url_before', 255)
                ->nullable();
            $table->string('ping_url_after', 255)
                ->nullable();
            $table->text('monitor_emails')
                ->nullable()
                ->collation('utf8mb4_bin');
            $table->timestamp('processed_at')
                ->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('command_schedules');
    }
}
