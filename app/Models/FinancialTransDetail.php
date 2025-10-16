<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransDetail extends Model
{
    use HasFactory;

    protected $table = 'financial_transdetail';

    protected $fillable = [
        'financial_trans_id',
        'module_id',
        'amount',
        'head_id',
        'crdr',
        'branch_id',
        'head_name',
        'transid'
    ];

    protected $casts = [
        'amount' => 'decimal:2'
    ];

    public $timestamps = false;


    public function financialTrans()
    {
        return $this->belongsTo(FinancialTrans::class, 'financial_trans_id');
    }
}
