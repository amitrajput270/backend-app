<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTrans extends Model
{
    use HasFactory;

    protected $table = 'financial_trans';

    protected $fillable = [
        'module_id',
        'transid',
        'admno',
        'amount',
        'crdr',
        'tran_date',
        'acad_year',
        'entry_mode',
        'voucher_no',
        'branch_id',
        'type_of_concession',
        'due_amount'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_amount' => 'decimal:2',
        'tran_date' => 'date'
    ];

    // time stamps
    public $timestamps = false;

    public function details()
    {
        return $this->hasMany(FinancialTransDetail::class, 'financial_trans_id');
    }
}
