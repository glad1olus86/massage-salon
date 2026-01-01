<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleAdminMiddleware
{
    /**
     * Handle an incoming request.
     * Разрешает доступ только операторам и админам (company).
     * Массажисты перенаправляются на свой дашборд.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();
        
        if (!$user) {
            return redirect()->route('login');
        }
        
        // Массажисты не имеют доступа к admin панели (проверяем роль Spatie)
        if ($user->hasRole('masseuse') || $user->type == 'masseuse') {
            // Тихий редирект без сообщения об ошибке
            return redirect('/masseuse');
        }
        
        // Разрешаем доступ операторам и админам
        if (!in_array($user->type, ['operator', 'company', 'super admin', 'user'])) {
            return redirect('/')
                ->with('error', __('Доступ запрещён.'));
        }
        
        return $next($request);
    }
}
