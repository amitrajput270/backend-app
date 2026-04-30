<?php

namespace App\Facades;
use Illuminate\Support\Facades\Facade;
class CustomHelper extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'GH';
    }
}