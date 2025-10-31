<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

class RunScheduledJobs extends Command
{
    protected $signature   = 'cron:run';
    protected $description = 'Run scheduled jobs from database';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Running scheduled jobs...');
        return Command::SUCCESS;
    }
}
