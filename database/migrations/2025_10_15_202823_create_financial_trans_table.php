<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_financial_trans_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFinancialTransTable extends Migration
{
    public function up()
    {
        Schema::create('financial_trans', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->enum('module_id', ['1', '0'])->default('1');
            $table->string('transid', 50)->nullable()->unique();
            $table->string('admno', 50)->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->enum('crdr', ['D', 'C'])->default('D');
            $table->date('tran_date')->nullable();
            $table->string('acad_year', 50)->nullable();
            $table->enum('entry_mode', ['0', '1'])->default('0');
            $table->string('voucher_no', 255)->nullable();
            $table->enum('branch_id', ['1', '0'])->default('1');
            $table->enum('type_of_concession', ['1', '2'])->nullable();
            $table->decimal('due_amount', 15, 2)->default(0);

            $table->timestamps();

            // Indexes
            $table->index('voucher_no');
            $table->index('admno');
            $table->index('transid');
        });
    }

    public function down()
    {
        Schema::dropIfExists('financial_trans');
    }
}
