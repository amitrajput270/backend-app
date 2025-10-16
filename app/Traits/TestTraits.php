<?php

namespace App\Traits;

trait TestTraits
{
    public function sayHello()
    {
        return "Hello from Trait!";
    }

    public function formatResponse($data)
    {
        return [
            'success' => true,
            'data' => $data
        ];
    }
}
