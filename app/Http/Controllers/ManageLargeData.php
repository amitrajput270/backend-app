-- Active: 1749118852752@@127.0.0.1@3306@backend-app
<?php

namespace App\Http\Controllers;

use App\Exports\FeesExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use League\Csv\Reader;
use Maatwebsite\Excel\Facades\Excel;

class ManageLargeData extends Controller
{

    public function processWithYield(Request $request)
    {
        // Generator that processes and yields results
        $processor = function () use ($request) {
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

            $filePath = $file->getRealPath();
            dd($filePath);

            if (!File::exists($filePath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => "File not found: {$filePath}"
                ], 404);
            }

            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            DB::connection()->disableQueryLog();
            foreach ($csv->getRecords() as $record) {
                yield $record;

                // Optional: sleep to simulate processing time
                // if ($i % 10 === 0) {
                //     sleep(1);
                // }
            }
            // for ($i = 0; $i < 1000000; $i++) {
            //     // Simulate processing
            //     $result = [
            //         'batch' => $i,
            //         'processed' => $i * 100,
            //         'memory' => memory_get_usage() / 1024 / 1024 . ' MB'
            //     ];

            //     yield $result;

            // Optional: sleep to simulate processing time
            // if ($i % 10 === 0) {
            //     sleep(1);
            // }
            // }
        };

        // return response()->json([
        //     'status' => 'success',
        //     'message' => 'Processing started',
        //     'data' => [
        //         'info' => 'Use the /yield-test endpoint to stream results as they are processed.'
        //     ]
        // ], 200);

        // Return as JSON stream
        return response()->stream(function () use ($processor) {
            foreach ($processor() as $data) {
                dd($data);
                echo json_encode($data) . "\n";
                flush();
            }
        });
    }


    public function uploadLargeFile(Request $request)
    {
        $processor = function () use ($request) {
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

            $filePath = $file->getRealPath();

            if (!File::exists($filePath)) {
                return response()->json([
                    'status' => 'error',
                    'message' => "File not found: {$filePath}"
                ], 404);
            }
            $csv = Reader::createFromPath($filePath, 'r');
            $csv->setHeaderOffset(0);
            DB::connection()->disableQueryLog();
            foreach ($csv->getRecords() as $record) {
                yield $record;
            }
        };

        $chunk = [];
        $chunkSize = 1000;
        $imported = 0;
        return response()->stream(function () use ($processor, $chunkSize, &$imported, &$chunk) {
            DB::beginTransaction();
            try {
                foreach ($processor() as $data) {
                    // dd($data);
                    $chunk[] = $data;
                    $chunkCount = count($chunk);
                    if ($chunkCount === $chunkSize) {
                        $this->insertChunk($chunk);
                        $imported += $chunkSize;
                        $chunk = [];
                    }
                    flush();
                }
                if (!empty($chunk)) {
                    $this->insertChunk($chunk);
                    $imported += $chunkCount;
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
                echo json_encode([
                    'status' => 'error',
                    'message' => $e->getMessage(),
                ]);
            }
            echo json_encode([
                'status' => 'success',
                'imported_records' => $imported
            ]);
        });
    }

    public function uploadLargeFileNew(Request $request)
    {
        if ($request->isMethod('get')) {
            return view('import-large-file');
        }

        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');

        if (! $file || ! $file->isValid()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or missing file upload.',
            ], 422);
        }

        $filePath = $file->getRealPath();

        if (! File::exists($filePath)) {
            return response()->json([
                'status' => 'error',
                'message' => "File not found: {$filePath}",
            ], 404);
        }

        DB::disableQueryLog();

        $chunkSize = 1000;
        $chunk = [];
        $imported = 0;

        foreach ($this->csvGenerator($filePath) as $row) {

            $chunk[] = $row;
            if (count($chunk) === $chunkSize) {
                $this->insertChunk($chunk);
                $imported += $chunkSize;
                $chunk = [];
            }
        }

        if (! empty($chunk)) {
            $this->insertChunk($chunk);
            $imported += count($chunk);
        }

        return response()->json([
            'status' => 'success',
            'imported_records' => $imported,
        ]);
    }

    private function csvGenerator(string $filePath): \Generator
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        foreach ($csv->getRecords() as $record) {
            yield $record;
        }
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
