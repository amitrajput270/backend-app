<?php

namespace App\Traits;
use SweetAlert2\Laravel\Traits\WithSweetAlert;

trait AlertHelper
{
    use WithSweetAlert;

    public function alert($data = [])
    {
        $this->swalFire([
            'title' => $data['title'] ?? '',
            'text' => $data['text'] ?? '',
            'icon' => $data['icon'] ?? '',
            'confirmButtonText' => $data['confirmButtonText'] ?? '',
            'toast' => true,
            'position' => 'top-end',
            'timer' => 3000,
            'timerProgressBar' => true,
            'didOpen' => '(toast) => {
        toast.onmouseenter = Swal.stopTimer;
        toast.onmouseleave = Swal.resumeTimer;
    }',
        ]);
    }

}
