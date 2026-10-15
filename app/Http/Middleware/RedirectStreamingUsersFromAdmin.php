<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectStreamingUsersFromAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasRole('user_stream') && ! $user->hasRole('super_admin')) {
            return redirect('/');
        }

        return $next($request);
    }
}
