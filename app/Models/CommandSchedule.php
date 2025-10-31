<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommandSchedule extends Model
{
    use HasFactory;

    protected $table   = 'command_schedules';
    protected $guarded = ['id'];

    public $timestamps = true;

    public function __construct()
    {
        parent::__construct(...func_get_args());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', 1);
    }
}
