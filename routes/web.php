<?php

use App\Http\Controllers\FinancialMigrationController;
use App\Http\Controllers\RssFeedController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('users');
});

Route::get('test', \App\Http\Livewire\Counter::class, 'test');
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

Route::get('rss', [RssFeedController::class, 'index']);

Route::get('trait-test', [RssFeedController::class, 'traitTest']);




Route::any('migrate-financial-data', [FinancialMigrationController::class, 'migrateDueData']);
