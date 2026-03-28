<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Cache;

class RecordDemoLogin
{
    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        if (config('demo.enabled')) {
            Cache::put('demo:last_login_at', now());
        }
    }
}
