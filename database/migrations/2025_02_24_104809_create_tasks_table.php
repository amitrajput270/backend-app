<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date');
            $table->time('due_time');
            $table->boolean('is_reminder')->default(false);
            $table->dateTime('reminder_datetime')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->enum('recurring_type', ['daily', 'weekly', 'monthly', 'yearly'])->nullable();
            $table->date('recurring_until')->nullable();
            $table->boolean('is_priority')->default(false);
            $table->enum('priority', ['low', 'medium', 'high'])->nullable();
            $table->boolean('is_starred')->default(false);
            $table->boolean('is_trash')->default(false);
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_archived')->default(false);
            $table->boolean('is_public')->default(false);
            $table->string('color')->nullable();
            $table->string('icon')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tasks');
    }
}
