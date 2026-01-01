<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMasseuseMiddleware
{
    /**
     * Handle an incoming request.
     * Разрешает доступ массажистам, операторам и админам.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Проверяем роль Spatie или тип пользователя
        $hasAccess = $user->hasRole('masseuse') || 
                     in_array($user->type, ['operator', 'company', 'super admin']);
        
        if (!$hasAccess) {
            return redirect('/')
                ->with('error', __('Доступ запрещён.'));
        }
        
        return $next($request);
    }
}
