<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CekRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, $role)
    {
        $user = $request->user() ?? Auth::user();

        if ($user && $user->role === $role && $user->status !== 'nonaktif') {
            return $next($request);
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak.',
            ], 403);
        }

        return redirect('/')->withErrors(['username' => 'Akses ditolak. Anda tidak memiliki izin ke halaman ini.']);
    }
}
