<?php

namespace App\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->routeIs('filament-excel-download')) {
            return $next($request);
        }
        if (! $request->user()) {
            return redirect(Filament::getPanel('admin')->getLoginUrl());
        }
        abort_unless($request->user()->canAccessPanel(Filament::getPanel('admin')), 403);

        return $next($request);
    }
}
