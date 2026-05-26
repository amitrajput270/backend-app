<?php

use App\Http\Controllers\FinancialMigrationController;
use App\Http\Controllers\RssFeedController;
// use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => view('users'));

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

    $a = "gqdbucbmfkpjeszrlxpebjrccqwluvojqszxewoqrrcvjzqwrfogxidwzqvdnqohbdjztvtnuxztfqdoenerhrpmbrhqrhtugbfzdziazeyrjndxtszkznnirdccokzevhdfaffroaiqwawvfoxhccvachcaztucsrpmswawkxoulpxheiucwppkdmllispmgbnrfvifzxvgfafndvmuofcqvyxesfzuuzhecspreqsuknxxmhbbwnobteoykcfkmslzcfilgvfadsumwxsjtjfisjvmiaujamazwacqgllduhaxvxbparpbkpqlbmigtbkuizvxvxgbrqsuaovlvlwkspagzdopudhklybhudmmvpefcllphlwklrqenviohpzhqdtrgavljseixuloilzfgyinvxsujkuqqyymvklgdwicuovyhgirkdkjsfuzrcgcqtgnqyqtyoivrslcwzfriyrnlgo";
    $b = "zrcgcqtgnqyqtyoivrslcwzfriyrnlgoayojctgymfmdqaazmgqqglcbmsavuzsrehajutmnsfkeuwmvitcmamuhyfejkconkncoqomjchwliiajcwivupwuukkasqwzcnmdymkkapsauhuaknktwaavqgoakzkqahabknmqwmorobcayasmufmwspooayyriictcwkcynsnumqdkmshkuavoygoysozeniauwyoawwyadusascheyassqyaqncfegkdeckvqkawuvwxelgtgpqnoaopsgmmoawvaqavmqgqmaqnaayacjclwsebomahuyuxmgewiqgnqjimwnanmjitaoykgxgdogmsaaafcvqzayimsealoayrybqxwicnocsuguucmbicagoyqccrwokjqawocsydmpsdmkazwxeikiseiccamwwucjkjwvkdarioogaxiqedeaaasygskpeoizapynw";
    if ($a === $b) {
        echo 0;
        return;
    }
    if (strlen($a) != strlen($b)) {
        echo -1;
        return;
    }
    $n = strlen($a);

    $maxMatch = 0;

    for ($i = 0; $i < $n; $i++) {

        $suffixA = '';
        for ($j = $i; $j < $n; $j++) {
            $suffixA .= $a[$j];
        }

        $prefixB = '';
        for ($j = 0; $j < $n - $i; $j++) {
            $prefixB .= $b[$j];
        }

        if ($suffixA === $prefixB) {
            $maxMatch = $n - $i;
            break;
        }
    }
    echo ($n - $maxMatch);
});
