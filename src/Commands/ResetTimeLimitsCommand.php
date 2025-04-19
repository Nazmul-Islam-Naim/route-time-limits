<?php

namespace NTimes\RouteTimeLimits\Commands;

use Illuminate\Console\Command;
use NTimes\RouteTimeLimits\Models\RouteTimeLimit;

class ResetTimeLimitsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'route-time-limits:reset
                           {--route= : Specific route name to reset}
                           {--user-id= : Specific user ID to reset}
                           {--all : Reset all time limits}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset route time limits for testing';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $route = $this->option('route');
        $userId = $this->option('user-id');
        $all = $this->option('all');
        
        $query = RouteTimeLimit::query();
        
        if ($route) {
            $query->where('route_name', $route);
        }
        
        if ($userId) {
            $query->where('user_id', $userId);
        }
        
        if (!$all && !$route && !$userId) {
            $this->error('Please specify at least one option: --route, --user-id, or --all');
            return 1;
        }
        
        $updated = $query->update(['used_time' => 0, 'last_accessed_at' => now()]);
        
        $this->info("Reset {$updated} route time limit records.");
        
        return 0;
    }
}