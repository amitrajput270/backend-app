<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScheduledTaskLog extends Model
{
    use HasFactory;

    protected $table   = 'command_schedules_logs';
    public $timestamps = true;
    protected $guarded = ['id'];

    public function __construct()
    {
        parent::__construct(...func_get_args());
    }

}
