# Laravel Route Time Limits

A Laravel package that allows you to set time limits on routes and track usage times based on user authentication status. This package helps you monitor and control how much time is spent on specific routes over a defined period, with different limits for authenticated and guest users.

## Installation

Install the package via composer:

```bash
composer require yourvendor/route-time-limits
```

Publish the configuration and migrations:

```bash
php artisan vendor:publish --provider="YourVendor\RouteTimeLimits\RouteTimeLimitsServiceProvider"
```

Run the migrations:

```bash
php artisan migrate
```

## Usage

### Basic Usage

Add the middleware to your routes:

```php
// In your routes file
Route::get('/dashboard', 'DashboardController@index')
    ->name('dashboard')
    ->middleware('route.time.limit');

// Or for route groups
Route::middleware('route.time.limit')->group(function () {
    Route::get('/admin', 'AdminController@index')->name('admin.index');
    Route::get('/admin/users', 'AdminController@users')->name('admin.users');
});
```

### Configuration

You can customize the package behavior in the `config/route_time_limits.php` file:

```php
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
        'admin.dashboard' => [
            'guest' => 0, // No access for guests
            'authenticated' => 900, // 15 minutes for authenticated users
        ],
        'api.users.index' => [
            'guest' => 60, // 1 minute for guests
            'authenticated' => 300, // 5 minutes for authenticated users
        ],
    ],
    
    // Whether to enable the middleware globally
    'enabled' => true,
    
    // Routes to exclude from time tracking
    'excluded_routes' => [
        'login',
        'register',
    ],
    
    // Whether to track by IP address for guest users
    'track_guest_by_ip' => true,
    
    // Identify user by this field (default: id)
    'user_identifier' => 'id',
];
```

### Available Commands

#### Cleanup Old Records

The package includes a command to clean up old records:

```bash
# Clean up all records
php artisan route-time-limits:cleanup

# Clean up only guest records
php artisan route-time-limits:cleanup --user-type=guest

# Clean up only authenticated user records
php artisan route-time-limits:cleanup --user-type=authenticated
```

This command is automatically scheduled to run daily, but you can also run it manually.

#### Reset Time Limits

For testing or administrative purposes, you can reset time limits:

```bash
# Reset all time limits
php artisan route-time-limits:reset --all

# Reset time limits for a specific route
php artisan route-time-limits:reset --route=admin.dashboard

# Reset time limits for a specific user
php artisan route-time-limits:reset --user-id=1
```

## How It Works

1. When a user visits a route with the middleware applied, the package checks if there's an existing record for that route and user type (guest or authenticated).
2. If no record exists, it creates one with the default time limit (or a custom one if configured).
3. Each time the route is accessed, the time spent is tracked and added to the daily total.
4. If the total time exceeds the configured limit, a 429 (Too Many Requests) response is returned.
5. The time counter is reset daily.
6. Different time limits are applied based on whether the user is authenticated or a guest.
7. Guest users can be tracked by IP address if configured.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.