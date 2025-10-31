<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FinancialDataMigrationService;

class MigrateFinancialData extends Command
{
    protected $signature = 'finance:migrate-due-data';
    protected $description = 'Migrate DUE data from temporary_data to financial transaction tables';

    public function handle(FinancialDataMigrationService $migrationService)
    {
        $this->info('Starting financial data migration...');

        try {
            $results = $migrationService->migrateDueData();
            $this->info('Migration completed successfully!');
            $this->info('Results:');
            $this->table(
                ['Metric', 'Expected', 'Actual', 'Status'],
                [
                    [
                        'Parent Records',
                        $results['expected_parent'],
                        $results['parent_count'],
                        $results['parent_count'] == $results['expected_parent'] ? '✅ PASS' : '❌ FAIL'
                    ],
                    [
                        'Child Records',
                        $results['expected_child'],
                        $results['child_count'],
                        $results['child_count'] == $results['expected_child'] ? '✅ PASS' : '❌ FAIL'
                    ],
                    [
                        'Amount Sum',
                        number_format($results['expected_amount']),
                        number_format($results['parent_amount_sum']),
                        $results['parent_amount_sum'] == $results['expected_amount'] ? '✅ PASS' : '❌ FAIL'
                    ],
                ]
            );
        } catch (\Exception $e) {
            $this->error('Migration failed: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
