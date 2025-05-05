<?php

namespace NTimes\RouteTimeLimits\Models;

use Illuminate\Database\Eloquent\Model;

class RouteTimeLimit extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'route_name',
        'url',
        'method',
        'max_time',
        'used_time',
        'request_count',
        'user_id',
        'user_type',
        'ip_address',
        'last_accessed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'max_time' => 'integer',
        'used_time' => 'float',
        'request_count' => 'integer',
        'last_accessed_at' => 'datetime',
    ];

    /**
     * Check if the route has exceeded its time limit.
     *
     * @return bool
     */
    public function hasExceededLimit()
    {
        return $this->used_time >= $this->max_time;
    }

    /**
     * Update the used time for this route.
     *
     * @param int $timeSpent Time spent in seconds
     * @return void
     */
    public function updateUsedTime($timeSpent)
    {
        $this->used_time += $timeSpent;
        $this->last_accessed_at = now();
        $this->save();
    }

    /**
     * Increment the request count for this route.
     *
     * @return void
     */
    public function incrementRequestCount()
    {
        $this->increment('request_count');
    }


    /**
     * Reset the used time for this route.
     *
     * @return void
     */
    public function resetUsedTime()
    {
        $this->used_time = 0;
        $this->save();
    }

    /**
     * Reset the request count for this route.
     *
     * @return void
     */
    public function resetRequestCount()
    {
        $this->request_count = 0;
        $this->save();
    }

    /**
     * Scope a query to only include records for a specific user.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param mixed $userId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId)->where('user_type', 'authenticated');
    }

    /**
     * Scope a query to only include guest records.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $ipAddress
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForGuest($query, $ipAddress = null)
    {
        $query = $query->where('user_type', 'guest');
        
        if ($ipAddress) {
            $query->where('ip_address', $ipAddress);
        }
        
        return $query;
    }
}