<?php

namespace App\Http\Livewire;

use Livewire\Component;

class Counter extends Component
{
    public $count = 0;



    public function test()
    {
        echo "test";
    }

    public function increment()
    {
        $this->count++;
    }

    public function decrement()
    {
        $this->count--;
    }

    public function resetValue()
    {
        $this->count = 0;
    }

    public function incrementByAmount($amount)
    {
        $this->count += $amount;
    }

    public function render()
    {
        return view('livewire.counter');
    }
}
