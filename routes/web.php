<?php

use App\Http\Controllers\FinancialMigrationController;
use App\Http\Controllers\RssFeedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('users');
});

Route::any('import-large-file', [\App\Http\Controllers\ManageLargeData::class, 'uploadLargeFile']);
Route::any('yield-test', [\App\Http\Controllers\ManageLargeData::class, 'processWithYield']);
// Route::get('test', \App\Http\Livewire\Counter::class, 'test');
Route::any('migrate-financial-data', [FinancialMigrationController::class, 'migrateDueData']);
Route::any('search-user', \App\Http\Livewire\SearchUser::class)->name('search-user');
Route::get('users-export', [\App\Http\Livewire\SearchUser::class, 'export'])->name('users.export');
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

Route::get('rss', [RssFeedController::class, 'index']);
Route::get('trait-test', [RssFeedController::class, 'traitTest']);















Route::get('test', function () {
    $arr  = [5, 1, 6, 2, 2, 3, 4, 4, 5];
    $uniqueArr = [];
    foreach ($arr as $key => $value) {
        $uniqueArr[$value] = $value;
    }

    $uniqueArr = array_values($uniqueArr);
    sort($uniqueArr);
    $max = max($uniqueArr);
    $min = min($uniqueArr);
    return response()->json([
        'success'    => true,
        'message'    => 'Interview question solved successfully',
        'data'       => [
            'unique' => $uniqueArr,
            'max'    => $max,
            'min'    => $min,
        ]
    ]);
});
