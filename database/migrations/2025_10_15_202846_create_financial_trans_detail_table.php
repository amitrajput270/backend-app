<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_financial_trans_detail_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancialTransDetailTable extends Migration
{
    public function up()
    {
        Schema::create('financial_transdetail', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('financial_trans_id');
            $table->enum('module_id', ['1', '0'])->default('1');
            $table->decimal('amount', 15, 2)->default(0);
            $table->unsignedBigInteger('head_id')->nullable();
            $table->enum('crdr', ['D', 'C'])->default('D');
            $table->unsignedBigInteger('branch_id')->default(1);
            $table->string('head_name', 255)->nullable();
            $table->string('transid', 50)->nullable();

            $table->timestamps();

            // Foreign key
            $table->foreign('financial_trans_id')
                ->references('id')
                ->on('financial_trans')
                ->onDelete('cascade');

            // Indexes
            $table->index('financial_trans_id');
            $table->index('transid');
        });
    }

    public function down()
    {
        Schema::dropIfExists('financial_transdetail');
    }
}
