<?php
return [
    // Default maximum time allowed for all routes (in seconds)
    'default_max_time' => [
        'guest' => 300, // 5 minutes for guests
        'authenticated' => 600, // 10 minutes for authenticated users
    ],

    // How many days of data to keep in the database
    'data_retention_days' => 1,

    // Custom route configurations
    // Format: 'route_name' => ['guest' => time_in_seconds, 'authenticated' => time_in_seconds]
    'custom_routes' => [
        // Examples:
        // 'admin.dashboard' => [
        //     'guest' => 0, // No access for guests
        //     'authenticated' => 900, // 15 minutes for authenticated users
        // ],
        // 'api.users.index' => [
        //     'guest' => 60, // 1 minute for guests
        //     'authenticated' => 300, // 5 minutes for authenticated users
        // ],
    ],
    
    // Whether to enable the middleware globally
    'enabled' => true,
    
    // Routes to exclude from time tracking
    'excluded_routes' => [
        // Examples:
        // 'login',
        // 'register',
    ],
    
    // Whether to track by IP address for guest users
    'track_guest_by_ip' => true,
    
    // Identify user by this field (default: id)
    'user_identifier' => 'id',
];