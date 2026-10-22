<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ChickAdminOrSuperAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role = auth()->user()?->role;
        if (!in_array($role, ['superadmin', 'admin'])) {
            
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('error', 'يجب تسجيل الدخول بالحساب المخصص للشيف.');        }
        return $next($request);
    }
}
