<?php

namespace App\Http\Controllers;

use App\Services\FinancialDataMigrationService;
use Illuminate\Http\Request;

class FinancialMigrationController extends Controller
{
    public function migrateDueData(Request $request, FinancialDataMigrationService $migrationService)
    {
        try {
            ini_set('memory_limit', '1024M'); // Increase memory if needed
            set_time_limit(0); // Prevent timeout


            $results = $migrationService->migrateDueData();
            return response()->json([
                'success' => true,
                'message' => 'Data migration completed successfully',
                'data' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
                'error_details' => $e->getFile() . ':' . $e->getLine()
            ], 500);
        }
    }

    public function importLargeFile()
    {

        try {
            ini_set('memory_limit', '2048M');
            set_time_limit(0);

            if (request()->method() == 'GET') {
                return view('import-large-file');
            }

            // $path = request()->file('dataFile')->store('uploads');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
                'error_details' => $e->getFile() . ':' . $e->getLine()
            ], 500);
        }
    }
}
