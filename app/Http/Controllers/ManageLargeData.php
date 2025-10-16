<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManageLargeData extends Controller
{
    public function index()
    {
        ini_set('memory_limit', '1024M'); // Increase memory if needed
        set_time_limit(0); // Prevent timeout

        $chunkSize = 1000;
        $parentChunk = [];
        $childChunk = [];


        // $d =  DB::table('temporary_data')
        //     ->select(
        //         'id',
        //         'date as tran_date',
        //         'academic_year as acad_year',
        //         'admno_uniqueid as admno',
        //         'due_amount as amount',
        //         'voucher_no',
        //         'fee_head as head_name'
        //     )
        //     ->where('voucher_type', 'DUE')
        //     ->orderBy('voucher_no') // keep data sorted for grouping
        //     ->groupBy('voucher_no');



        //Step 1: Chunk data from DB instead of loading all at once
        DB::table('temporary_data')
            ->select(
                'id',
                'date as tran_date',
                'academic_year as acad_year',
                'admno_uniqueid as admno',
                'due_amount as amount',
                'voucher_no',
                'fee_head as head_name'
            )
            ->where('voucher_type', 'DUE')
            ->orderBy('voucher_no') // keep data sorted for grouping
            // ->groupBy('voucher_no')
            ->chunk($chunkSize, function ($tempData) use (&$parentChunk, &$childChunk, $chunkSize) {
                $tranid = 'TXN' . rand(10000000, 99999999);
                $parentChunk[] = [
                    'tranid' => $tranid,
                    'admno' => $tempData->first()->admno,
                    'voucher_no' => $tempData->first()->voucher_no,
                    'acad_year' => $tempData->first()->acad_year,
                    'tran_date' => date('Y-m-d', strtotime($tempData->first()->tran_date)),
                    'amount' => $tempData->sum('amount'),
                ];
                if (count($parentChunk) >= $chunkSize) {
                    $this->insertParentChunk($parentChunk);
                    $parentChunk = [];
                }

                // $grouped = $tempData->groupBy('voucher_no');
                // foreach ($grouped as $voucherNo => $rows) {
                //     $tranid = 'TXN' . rand(10000000, 99999999);

                //     // parent data
                //     $parentChunk[] = [
                //         'tranid' => $tranid,
                //         'admno' => $rows->first()->admno,
                //         'voucher_no' => $voucherNo,
                //         'acad_year' => $rows->first()->acad_year,
                //         'tran_date' => date('Y-m-d', strtotime($rows->first()->tran_date)),
                //         'amount' => $rows->sum('amount'),
                //     ];

                //     // child data
                //     foreach ($rows as $childData) {
                //         $childChunk[] = [
                //             'financial_tran_id' => $tranid,
                //             'head_id' => 1,
                //             'branch_id' => 1,
                //             'amount' => $childData->amount,
                //             'head_name' => $childData->head_name,
                //         ];

                //         if (count($childChunk) >= $chunkSize) {
                //             $this->insertChildChunk($childChunk);
                //             $childChunk = [];
                //         }
                //     }

                //     if (count($parentChunk) >= $chunkSize) {
                //         $this->insertParentChunk($parentChunk);
                //         $parentChunk = [];
                //     }
                // }
                if (!empty($childChunk)) {
                    $this->insertChildChunk($childChunk);
                }
                if (!empty($parentChunk)) {
                    $this->insertParentChunk($parentChunk);
                }
                unset($parentChunk, $childChunk);
                gc_collect_cycles();
            });
        return 'completed';
    }

    private function insertChildChunk(array $chunk)
    {
        try {
            DB::table('financial_transdetail')->insertOrIgnore($chunk);
        } catch (\Exception $e) {
            \Log::error("Child insert error: " . $e->getMessage());
        }
    }

    private function insertParentChunk(array $chunk)
    {
        try {
            DB::table('financial_trans')->insertOrIgnore($chunk);
        } catch (\Exception $e) {
            \Log::error("Parent insert error: " . $e->getMessage());
        }
    }
}
