<?php

namespace NTimes\RouteTimeLimits\Commands;

use Illuminate\Console\Command;
use NTimes\RouteTimeLimits\Models\RouteTimeLimit;

class CleanupTimeRecordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'route-time-limits:cleanup 
                           {--user-type= : The type of user records to clean (guest, authenticated, or all)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old route time limit records';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $days = config('route_time_limits.data_retention_days', 1);
        $userType = $this->option('user-type');
        
        $query = RouteTimeLimit::where('last_accessed_at', '<', now()->subDays($days));
        
        // Filter by user type if specified
        if ($userType && in_array($userType, ['guest', 'authenticated'])) {
            $query->where('user_type', $userType);
            $this->info("Cleaning up records for user type: {$userType}");
        }
        
        $deleted = $query->delete();
        
        $this->info("Deleted {$deleted} old route time limit records.");
        
        return 0;
    }
}