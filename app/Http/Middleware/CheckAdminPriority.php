<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class CheckAdminPriority
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || !Auth::user()->hasAdminPriority()) {
            return redirect()->route('login')->with('error', 'Only admins can register new users.');
        }

        return $next($request);
    }
}
