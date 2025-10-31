<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $table   = 'password_resets';
    public $primaryKey = 'email';
    public $timestamps = false;

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'email', 'email');
    }
}
