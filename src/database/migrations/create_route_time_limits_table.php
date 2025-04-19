<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRouteTimeLimitsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('route_time_limits', function (Blueprint $table) {
            $table->id();
            $table->string('route_name');
            $table->string('url')->nullable();
            $table->string('method')->default('GET');
            $table->integer('max_time')->comment('Maximum allowed time in seconds');
            $table->integer('used_time')->default(0)->comment('Total time used in seconds');
            $table->unsignedBigInteger('user_id')->nullable()->comment('User ID if authenticated');
            $table->string('user_type')->default('guest')->comment('Type of user: guest, authenticated');
            $table->string('ip_address')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();
            
            // Add indexes for quicker lookups
            $table->index(['route_name', 'method']);
            $table->index(['user_id', 'user_type']);
            $table->index('ip_address');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('route_time_limits');
    }
}