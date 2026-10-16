<?php

namespace App\Listeners;

use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\DB;

class CheckDatabaseHealth
{
    public function handle(DiagnosingHealth $event): void
    {
        foreach (['users', 'sessions', 'cache', 'cache_locks'] as $table) {
            DB::table($table)->exists();
        }
    }
}
