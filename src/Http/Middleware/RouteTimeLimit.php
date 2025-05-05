<?php

namespace NTimes\RouteTimeLimits\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use NTimes\RouteTimeLimits\Models\RouteTimeLimit as RouteTimeLimitModel;

class RouteTimeLimit
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Check if middleware is enabled in config
        if (!config('route_time_limits.enabled', true)) {
            return $next($request);
        }

        $route = Route::current();

        // Try to get route name, if missing, fallback to route URI
        $routeName = $route ? ($route->getName() ?? $route->uri()) : null;


        // Skip if no route name or route is excluded
        if (!$routeName || in_array($routeName, config('route_time_limits.excluded_routes', []))) {
            return $next($request);
        }

        // Start timing
        $startTime = microtime(true);
        
        // Get or create route time limit record
        $routeLimit = $this->getOrCreateRouteLimit($routeName, $request);
        
        // Check if route has exceeded its time limit
        if ($routeLimit->hasExceededLimit()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Time limit exceeded for this route',
                    'route' => $routeName,
                    'limit' => $routeLimit->max_time,
                    'used' => $routeLimit->used_time,
                    'user_type' => $routeLimit->user_type,
                ], 429);
            }

            return response()->view('route-time-limits.route-limits-exceeded', [], 429);

        }
        
        // Increment the request count for this route
        $routeLimit->incrementRequestCount();
        
        // Process the request
        $response = $next($request);
        
        // Calculate time spent
        $timeSpent = microtime(true) - $startTime;
        
        // Update route time usage
        $routeLimit->updateUsedTime($timeSpent > 0 ? $timeSpent : 1);
        
        return $response;
    }
    
    /**
     * Get or create a route time limit record.
     *
     * @param string $routeName
     * @param \Illuminate\Http\Request $request
     * @return \NTimes\RouteTimeLimits\Models\RouteTimeLimit
     */
    protected function getOrCreateRouteLimit($routeName, Request $request)
    {
        $method = $request->method();
        $isAuthenticated = Auth::check();
        $userType = $isAuthenticated ? 'authenticated' : 'guest';
        
        // Get user ID if authenticated
        $userId = null;
        if ($isAuthenticated) {
            $userIdentifier = config('route_time_limits.user_identifier', 'id');
            $userId = Auth::user()->$userIdentifier;
        }
        
        // Get IP address for guest tracking if enabled
        $ipAddress = null;
        if (!$isAuthenticated && config('route_time_limits.track_guest_by_ip', true)) {
            $ipAddress = $request->ip();
        }
        
        // Build query based on user type
        $query = RouteTimeLimitModel::where('route_name', $routeName)
            ->where('method', $method);
            
        if ($isAuthenticated) {
            $query->where('user_id', $userId)
                  ->where('user_type', 'authenticated');
        } else {
            $query->where('user_type', 'guest');
            if ($ipAddress) {
                $query->where('ip_address', $ipAddress);
            }
        }
        
        $routeLimit = $query->first();
        
        if (!$routeLimit) {
            // Set default max time from config
            $customRoutes = config('route_time_limits.custom_routes', []);
            $maxTime = isset($customRoutes[$routeName][$userType]) 
                ? $customRoutes[$routeName][$userType] 
                : config('route_time_limits.default_max_time.' . $userType, 300);
                
            $routeLimit = new RouteTimeLimitModel([
                'route_name' => $routeName,
                'url' => $request->getPathInfo(),
                'method' => $method,
                'max_time' => $maxTime,
                'used_time' => 0,
                'user_id' => $userId,
                'user_type' => $userType,
                'ip_address' => $ipAddress,
                'last_accessed_at' => now(),
            ]);
            
            $routeLimit->save();
        }
        
        // Check if it's a new day, reset counter if needed
        if ($routeLimit->last_accessed_at && $routeLimit->last_accessed_at->diffInDays(now()) >= 1) {
            $routeLimit->resetUsedTime();
            $routeLimit->resetRequestCount();
        }
        
        return $routeLimit;
    }
}