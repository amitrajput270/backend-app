<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:createUsers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $commandStatus  = null;
        $date           = date('Y-m-d H:i:s');
        $cronController = \App::make('App\Http\Controllers\CronJobController');
        $response       = $cronController->createUsers();
        $response       = json_decode($response->getContent(), true);
        if ($response['success'] === false) {
            $this->error($date . ' Error: ' . $response['message']);
            $commandStatus = Command::FAILURE;
        } else {
            $this->info($date . ' Success: ' . $response['message']);
        }
        $this->info($date . ' Command executed successfully.');
        \Log::info($date . ' Command executed successfully.');
        $commandStatus = Command::SUCCESS;
        return $commandStatus;
    }
}
