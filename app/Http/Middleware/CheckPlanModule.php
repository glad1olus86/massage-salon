<?php

namespace App\Http\Middleware;

use App\Services\PlanModuleService;
use Closure;
use Illuminate\Http\Request;

class CheckPlanModule
{
    /**
     * Handle an incoming request.
     * Проверяет доступ к модулю по плану подписки
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $module
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $module)
    {
        // Проверяем доступ к модулю
        if (!PlanModuleService::hasModule($module)) {
            // Для AJAX запросов возвращаем JSON
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'error' => __('This module is not available in your plan. Please upgrade your subscription.'),
                    'module' => $module,
                ], 403);
            }

            // Для обычных запросов редирект с сообщением
            return redirect()->route('dashboard')->with('error', 
                __('The :module module is not available in your current plan. Please upgrade your subscription.', [
                    'module' => __(ucfirst($module))
                ])
            );
        }

        return $next($request);
    }
}
