<?php
namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Stringable;

class Kernel extends ConsoleKernel
{
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }

    protected function schedule(Schedule $schedule)
    {
        $commandSchedules = \App\Models\CommandSchedule::active()->get();
        foreach ($commandSchedules as $commandSchedule) {
            if ($commandSchedule->type === 'LARAVEL_COMMAND') {
                $scheduling = $schedule->command($commandSchedule->command);
            } elseif ($commandSchedule->type === 'SHELL_COMMAND') {
                $scheduling = $schedule->exec($commandSchedule->command);
            }

            $scheduling->cron($commandSchedule->schedule)
                ->withoutOverlapping($commandSchedule->is_overlapping ? $commandSchedule->is_overlapping : 60)
                ->runInBackground()
                ->environments(explode(',', $commandSchedule->environments))
                ->before(function () use ($commandSchedule) {
                    $this->logTaskStart($commandSchedule->command);
                })
                ->after(function (Stringable $output) use ($commandSchedule) {
                    \App\Models\CommandSchedule::where('id', $commandSchedule->id)->update([
                        'processed_at' => date('Y-m-d H:i:s'),
                    ]);
                    $this->logTaskSuccess($commandSchedule->command, $output);
                })
                ->onFailure(function (Stringable $output) use ($commandSchedule) {
                    $this->handleTaskFailure($commandSchedule->command, $output);
                })
                ->pingBeforeIf(
                    $commandSchedule->ping_url_before,
                    $commandSchedule->ping_url_before
                )
                ->thenPingIf(
                    $commandSchedule->ping_url_after,
                    $commandSchedule->ping_url_after
                );
            // ->appendOutputTo(storage_path('logs/cron.log'));
        }

    }

    private function logTaskStart(string $command)
    {
        \App\Models\ScheduledTaskLog::create([
            'command'    => $command,
            'started_at' => now(),
        ]);
    }

    private function logTaskSuccess(string $command, Stringable $output)
    {
        \App\Models\ScheduledTaskLog::where('command', $command)
            ->latest()
            ->first()
            ->update([
                'completed_at' => now(),
                'success'      => true,
                'output'       => $output,
            ]);
    }

    private function handleTaskFailure(string $command, Stringable $output)
    {
        \App\Models\ScheduledTaskLog::where('command', $command)
            ->latest()
            ->first()
            ->update([
                'completed_at' => now(),
                'success'      => false,
                'output'       => $output,
                'exception'    => $output,
            ]);
    }
}
