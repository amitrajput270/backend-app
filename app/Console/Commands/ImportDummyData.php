<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use League\Csv\Reader;

class ImportDummyData extends Command
{
    protected $signature = 'import:dummy-data {file} {--chunk=1000}';
    protected $description = 'Import large CSV file into database efficiently';

    public function handle()
    {
        $fileName = $this->argument('file');
        $chunkSize = (int) $this->option('chunk');
        $filePath = storage_path("app/{$fileName}");

        if (!File::exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return Command::FAILURE;
        }

        $this->info("Importing data from: {$fileName}");
        $this->info("Chunk size: {$chunkSize}");

        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);

        $totalRecords = count($csv);
        $this->info("Total records to import: {$totalRecords}");

        $progressBar = $this->output->createProgressBar($totalRecords);
        $progressBar->start();

        $chunk = [];
        $imported = 0;

        foreach ($csv->getRecords() as $record) {
            $chunk[] = [
                'name' => $record['name'],
                'email' => $record['email'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($chunk) >= $chunkSize) {
                $this->insertChunk($chunk);
                $imported += count($chunk);
                $chunk = [];
            }

            $progressBar->advance();
        }

        // Insert remaining records
        if (!empty($chunk)) {
            $this->insertChunk($chunk);
            $imported += count($chunk);
        }

        $progressBar->finish();
        $this->info("\nImport completed! Total imported: {$imported}");

        return Command::SUCCESS;
    }

    private function insertChunk(array $chunk)
    {
        try {
            DB::table('dummy_users')->insert($chunk);
        } catch (\Exception $e) {
            $this->error("Error inserting chunk: " . $e->getMessage());
        }
    }
}
