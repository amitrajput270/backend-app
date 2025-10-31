<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\Csv\Reader;

class ManageLargeData extends Controller
{
    public function uploadLargeFile(Request $request)
    {
        if ($request->method() == 'GET') {
            return view('import-large-file');
        }
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);
        $file = $request->file('file');
        if (!$file || !$file->isValid()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing file upload.',
            ], 422);
        }
        $chunkSize = 2000;
        $filePath = $file->getRealPath();
        if (!File::exists($filePath)) {
            return response()->json([
                'status' => 'error',
                'message' => "File not found: {$filePath}"
            ], 404);
        }
        DB::connection()->disableQueryLog();
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        $chunk = [];
        $imported = 0;
        foreach ($csv->getRecords() as $record) {
            $chunk[] = $record;
            if (count($chunk) >= $chunkSize) {
                $this->insertChunk($chunk);
                $imported += count($chunk);
                $chunk = [];
            }
        }
        if (!empty($chunk)) {
            $this->insertChunk($chunk);
            $imported += count($chunk);
        }
        return response()->json([
            'status' => 'success',
            'imported_records' => $imported
        ]);
    }

    private function insertChunk(array $chunk)
    {
        try {
            DB::table('fees_data')->insert($chunk);
        } catch (\Exception $e) {
            \Log::error("Insert error: " . $e->getMessage());
        }
    }


    public function index()
    {
        ini_set('memory_limit', '1024M'); // Increase memory if needed
        set_time_limit(0); // Prevent timeout

        try {
            DB::connection()->disableQueryLog();
        } catch (\Throwable $e) { /* ignore if unavailable */
        }

        $chunkSize = 1000; // batch size for inserts
        $parentChunk = [];
        $childChunk = [];

        // Stream rows ordered by voucher_no so we can group on the fly without loading everything
        $cursor = DB::table('temporary_data')
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
            ->orderBy('voucher_no')
            ->orderBy('id')
            ->cursor();

        $currentVoucher = null;
        $currentGroup = [];

        $flushGroup = function () use (&$currentGroup, &$parentChunk, &$childChunk, $chunkSize) {
            if (empty($currentGroup)) {
                return;
            }

            // Build parent and child payloads for this voucher group
            $first = $currentGroup[0];
            $sumAmount = 0;
            foreach ($currentGroup as $r) {
                $sumAmount += (float) $r->amount;
            }
            $tranid = 'TXN' . random_int(10000000, 99999999);

            $parentChunk[] = [
                'tranid' => $tranid,
                'admno' => $first->admno,
                'voucher_no' => $first->voucher_no,
                'acad_year' => $first->acad_year,
                'tran_date' => date('Y-m-d', strtotime($first->tran_date)),
                'amount' => $sumAmount,
            ];

            foreach ($currentGroup as $child) {
                $childChunk[] = [
                    'financial_tran_id' => $tranid,
                    'head_id' => 1,
                    'branch_id' => 1,
                    'amount' => $child->amount,
                    'head_name' => $child->head_name,
                ];
            }

            // Flush if batch sizes are reached
            if (count($parentChunk) >= $chunkSize) {
                $this->insertParentChunk($parentChunk);
                $parentChunk = [];
            }
            if (count($childChunk) >= $chunkSize) {
                $this->insertChildChunk($childChunk);
                $childChunk = [];
            }

            // reset group buffer
            $currentGroup = [];
        };

        foreach ($cursor as $row) {
            if ($currentVoucher === null) {
                $currentVoucher = $row->voucher_no;
            }

            if ($row->voucher_no !== $currentVoucher) {
                // voucher changed, flush previous group
                $flushGroup();
                $currentVoucher = $row->voucher_no;
            }

            $currentGroup[] = $row;
        }

        // Flush the last group if any
        $flushGroup();

        // Flush any remaining batched payloads
        if (!empty($parentChunk)) {
            $this->insertParentChunk($parentChunk);
        }
        if (!empty($childChunk)) {
            $this->insertChildChunk($childChunk);
        }

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
