<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureSessionLogin
{
    public function handle(Request $request, Closure $next)
    {
        if (!session('username')) {
            return redirect('/');
        }

        return $next($request);
    }
}
