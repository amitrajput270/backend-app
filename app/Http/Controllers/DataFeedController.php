<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class DataFeedController extends Controller
{

    private $statesApiUrl = 'https://countriesnow.space/api/v0.1/countries/states';
    private $countriesApiUrl = 'https://countriesnow.space/api/v0.1/countries/iso';

    public function index()
    {
        $response = Http::post($this->countriesApiUrl, [
            'country' => 'India',
            'state' => 'Uttar Pradesh'
        ]);

        $data = $response->json();
        return response()->json($data);

    }
}
