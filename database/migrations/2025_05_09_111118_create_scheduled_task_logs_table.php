<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateScheduledTaskLogsTable extends Migration
{

    public function up()
    {
        Schema::create('command_schedules_logs', function (Blueprint $table) {
            $table->id();
            $table->string('command', 255);
            $table->text('output')->nullable();
            $table->boolean('success')->nullable();
            $table->text('exception')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('scheduled_task_logs');
    }
}
