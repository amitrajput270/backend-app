<?php

namespace App\Services;

use App\Models\FinancialTrans;
use App\Models\FinancialTransDetail;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class FinancialDataMigrationService
{
    private const CHUNK_SIZE = 1000;
    private const MODULE_ID = '1';
    private const BRANCH_ID = '1';
    private const VOUCHER_TYPE = 'DUE';
    private const ENTRY_MODE = '0';

    // Concession types
    private const CONCESSION_TYPE = '1';
    private const SCHOLARSHIP_TYPE = '2';

    /**
     * Migrate due data from temporary table to financial tables
     *
     * @throws RuntimeException If migration fails
     * @return array Migration results
     */

    public function migrateDueData(): array
    {
        try {
            DB::beginTransaction();
            Log::info('Starting due data migration process');

            $this->clearExistingData();

            // via query
            $this->createParentRecord();
            $this->createChildRecord();
            DB::commit();
            Log::info('Migration completed successfully');

            return $this->getMigrationResults();

            // via Eloquent (memory intensive)
            $voucherSummary = $this->getVoucherSummary();

            Log::info('Processing parent records', ['count' => $voucherSummary->count()]);

            $parentRecords = $voucherSummary->map(function ($voucher) {
                return [
                    'module_id' => self::MODULE_ID,
                    'transid' => (string) Str::uuid(),
                    'admno' => $voucher->admno_uniqueid,
                    'amount' => $voucher->total_amount,
                    'crdr' => 'D',
                    'tran_date' => date('Y-m-d', strtotime($voucher->min_date)),
                    'acad_year' => $voucher->academic_year,
                    'entry_mode' => self::ENTRY_MODE,
                    'voucher_no' => $voucher->voucher_no,
                    'branch_id' => self::BRANCH_ID,
                    'type_of_concession' => $voucher->type_of_concession,
                    'due_amount' => $voucher->total_amount,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->chunk(self::CHUNK_SIZE);


            foreach ($parentRecords as $chunk) {
                FinancialTrans::insert($chunk->toArray());
            }

            Log::info('Processing child records');
            $dueRecords = $this->getDueRecords();

            $childRecords = $dueRecords->map(function ($record) {
                return [
                    'financial_trans_id' => $record->financial_trans_id,
                    'module_id' => self::MODULE_ID,
                    'amount' => $record->due_amount,
                    'head_id' => null,
                    'crdr' => 'D',
                    'branch_id' => self::BRANCH_ID,
                    'head_name' => $record->fee_head,
                    'transid' => $record->transid,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->chunk(self::CHUNK_SIZE);

            foreach ($childRecords as $chunk) {
                FinancialTransDetail::insert($chunk->toArray());
            }

            DB::commit();
            Log::info('Migration completed successfully');

            return $this->getMigrationResults();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Migration failed', ['error' => $e->getMessage()]);
            throw new RuntimeException('Failed to migrate due data: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Clear existing financial data
     */
    private function clearExistingData(): void
    {
        Log::info('Clearing existing financial data');
        FinancialTransDetail::query()->delete();
        FinancialTrans::query()->delete();
    }

    /**
     * Get voucher summary from temporary data
     *
     * @return SupportCollection
     */
    private function getVoucherSummary(): SupportCollection
    {
        return DB::table('temporary_data')
            ->where('voucher_type', self::VOUCHER_TYPE)
            ->select([
                'voucher_no',
                'admno_uniqueid',
                'academic_year',
                DB::raw('MIN(date) as min_date'),
                DB::raw('SUM(CAST(due_amount AS DECIMAL(15,2))) as total_amount'),
                DB::raw("CASE
                    WHEN MAX(CASE WHEN fee_category LIKE '%concession%' THEN 1 ELSE 0 END) = 1 THEN '" . self::CONCESSION_TYPE . "'
                    WHEN MAX(CASE WHEN fee_category LIKE '%scholarship%' THEN 1 ELSE 0 END) = 1 THEN '" . self::SCHOLARSHIP_TYPE . "'
                    ELSE NULL
                END as type_of_concession")
            ])
            ->groupBy('voucher_no', 'admno_uniqueid', 'academic_year')
            ->get();
    }

    /**
     * Get due records for child table
     *
     * @return SupportCollection
     */
    private function getDueRecords(): SupportCollection
    {
        return DB::table('temporary_data as td')
            ->join('financial_trans as ft', function ($join) {
                $join->on('td.voucher_no', '=', 'ft.voucher_no')
                    ->on('td.admno_uniqueid', '=', 'ft.admno');
            })
            ->where('td.voucher_type', self::VOUCHER_TYPE)
            ->select([
                'ft.id as financial_trans_id',
                'ft.transid',
                'td.due_amount',
                'td.fee_head'
            ])
            ->get();
    }

    /**
     * Get migration results and statistics
     *
     * @return array<string, int|float>
     */
    public function getMigrationResults(): array
    {
        $results = [
            'parent_count' => FinancialTrans::count(),
            'child_count' => FinancialTransDetail::count(),
            'parent_amount_sum' => FinancialTrans::sum('amount'),
            'child_amount_sum' => FinancialTransDetail::sum('amount'),
            'temp_due_amount_sum' => DB::table('temporary_data')
                ->where('voucher_type', self::VOUCHER_TYPE)
                ->sum(DB::raw('CAST(due_amount AS DECIMAL(15,2))')),
            'expected_parent' => 224657,
            'expected_child' => 410732,
            'expected_amount' => 12654422921
        ];

        Log::info('Migration results', $results);
        return $results;
    }


    private function createParentRecord()
    {
        //         INSERT INTO financial_trans (
        //     module_id, transid, admno, amount, crdr, tran_date,
        //     acad_year, entry_mode, voucher_no, branch_id, due_amount, type_of_concession
        // )
        // SELECT
        //     '1' AS module_id,
        //     UUID() AS transid,
        //     admno_uniqueid AS admno,
        //     SUM(CAST(due_amount AS DECIMAL(15,2))) AS amount,
        //     'D' AS crdr,
        //     MIN(STR_TO_DATE(DATE, '%d-%m-%Y')) AS tran_date,
        //     academic_year AS acad_year,
        //     '0' AS entry_mode,
        //     voucher_no,
        //     '1' AS branch_id,
        //     SUM(CAST(due_amount AS DECIMAL(15,2))) AS due_amount,
        //     MAX(CASE
        //         WHEN fee_category LIKE '%concession%' THEN '1'
        //         WHEN fee_category LIKE '%scholarship%' THEN '2'
        //         ELSE NULL
        //     END) AS type_of_concession
        // FROM temporary_data
        // WHERE voucher_type = 'DUE'
        // GROUP BY voucher_no, admno_uniqueid, academic_year;

        // convert query to laravel query builder
        $sqlQuery = 'INSERT INTO financial_trans (
            module_id, transid, admno, amount, crdr, tran_date,
            acad_year, entry_mode, voucher_no, branch_id, due_amount, type_of_concession
        )
        SELECT
            \'1\' AS module_id,
            UUID() AS transid,
            admno_uniqueid AS admno,
            SUM(CAST(due_amount AS DECIMAL(15,2))) AS amount,
            \'D\' AS crdr,
            MIN(STR_TO_DATE(DATE, \'%d-%m-%Y\')) AS tran_date,
            academic_year AS acad_year,
            \'0\' AS entry_mode,
            voucher_no,
            \'1\' AS branch_id,
            SUM(CAST(due_amount AS DECIMAL(15,2))) AS due_amount,
            MAX(CASE
                WHEN fee_category LIKE \'%concession%\' THEN \'1\'
                WHEN fee_category LIKE \'%scholarship%\' THEN \'2\'
                ELSE NULL
            END) AS type_of_concession
        FROM temporary_data
        WHERE voucher_type = \'DUE\'
        GROUP BY voucher_no, admno_uniqueid, academic_year;';

        DB::statement($sqlQuery);
        return true;
    }

    private function createChildRecord()
    {
        //         INSERT INTO financial_transdetail (
        //     financial_trans_id, module_id, amount, head_id, crdr, branch_id, head_name, transid
        // )
        // SELECT
        //     ft.id as financial_trans_id,
        //     '1' as module_id,
        //     CAST(td.due_amount AS DECIMAL(15,2)) as amount,
        //     NULL as head_id,
        //     'D' as crdr,
        //     1 as branch_id,
        //     td.fee_head as head_name,
        //     ft.transid
        // FROM temporary_data td
        // INNER JOIN financial_trans ft ON td.voucher_no = ft.voucher_no AND td.admno_uniqueid = ft.admno
        // WHERE td.voucher_type = 'DUE';

        $sqlQuery = 'INSERT INTO financial_transdetail (
            financial_trans_id, module_id, amount, head_id, crdr, branch_id, head_name, transid
        )
        SELECT
            ft.id as financial_trans_id,
            \'1\' as module_id,
            CAST(td.due_amount AS DECIMAL(15,2)) as amount,
            NULL as head_id,
            \'D\' as crdr,
            1 as branch_id,
            td.fee_head as head_name,
            ft.transid
        FROM temporary_data td
        INNER JOIN financial_trans ft ON td.voucher_no = ft.voucher_no AND td.admno_uniqueid = ft.admno
        WHERE td.voucher_type = \'DUE\';';
        DB::statement($sqlQuery);
        return true;
    }
}
