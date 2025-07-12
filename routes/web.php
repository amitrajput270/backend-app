<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('users');
});

Route::get('test', \App\Http\Livewire\Counter::class);
Route::any('search-user', \App\Http\Livewire\SearchUser::class)->name('search-user');

Route::get('demo', function (Request $request) {

    $a             = [2, 4, 67, 5, 4, 9, 10, 12, 8, 7, 4];
    $firstLargest  = 0;
    $secondLargest = 0;
    $thirdLargest  = 0;

    foreach ($a as $key => $value) {
        if ($value > $firstLargest) {
            $thirdLargest  = $secondLargest;
            $secondLargest = $firstLargest;
            $firstLargest  = $value;
        } elseif ($value > $secondLargest) {
            $thirdLargest  = $secondLargest;
            $secondLargest = $value;
        } elseif ($value > $thirdLargest) {
            $thirdLargest = $value;
        }
    }

    return response()->json([
        'firstLargest'  => $firstLargest,
        'secondLargest' => $secondLargest,
        'thirdLargest'  => $thirdLargest,
    ]);
});

Route::get('test', function () {
    $paymentReceipt = DB::table('payment_receipts as pr')
        ->select('pr.id', 'pr.reference_no', 'pr.date', 'pr.student_id')
        ->whereNotNull('reference_no')
        ->whereIn('pr.reference_no', function ($q) {
            $q->select('reference_no')
                ->from('payment_receipts')
                ->whereNotNull('reference_no')
                ->groupBy('reference_no')
                ->havingRaw('COUNT(*) > 1');
        })
        ->whereNotIn(DB::raw('(pr.reference_no, pr.date)'), function ($q) {
            $q->select(DB::raw('reference_no, date'))
                ->from('payment_receipts')
                ->whereNotNull('reference_no')
                ->groupBy('reference_no', 'date')
                ->havingRaw('COUNT(*) > 1');
        })
        ->whereNotIn(DB::raw('(pr.reference_no, pr.student_id)'), function ($q) {
            $q->select(DB::raw('reference_no, student_id'))
                ->from('payment_receipts')
                ->whereNotNull('reference_no')
                ->groupBy('reference_no', 'student_id')
                ->havingRaw('COUNT(*) > 1');
        })
        ->orderByDesc('id')
        ->limit(10)
        ->get();

    dd($paymentReceipt->toJson());
});
