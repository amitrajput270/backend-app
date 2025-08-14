<?php

use App\Http\Controllers\RssFeedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('users');
});

Route::get('test', \App\Http\Livewire\Counter::class);
Route::any('search-user', \App\Http\Livewire\SearchUser::class)->name('search-user');
Route::get('payment-receive', function () {
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

Route::get('demo', function (Request $request) {
    echo "Hello, this is a demo route!";
});

Route::get('rss', [RssFeedController::class, 'index']);


// Route::get('rssw', function () {
//     $rss = simplexml_load_file('https://www.upwork.com/nx/search/jobs/?amount=0-99,100-499,500-999&client_hires=1-9,10-&contractor_tier=2,3&hourly_rate=5-20&payment_verified=1&proposals=0-4,5-9&q=laravel%20developer&t=0,1');
//     foreach ($rss->channel->item as $item) {
//         echo $item->title . '<br>';
//     }
//     return response()->json($rss->channel->item);
// });
