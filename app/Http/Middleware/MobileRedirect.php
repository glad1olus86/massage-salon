<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class MobileRedirect
{
    /**
     * Route mappings from desktop to mobile
     */
    private array $routeMappings = [
        'jobsi-dashboard' => 'mobile.dashboard',
        'worker' => 'mobile.workers.index',
        'worker/*' => 'mobile.workers.show',
        'hotel' => 'mobile.hotels.index',
        'hotel/*/rooms' => 'mobile.hotels.rooms',
        'room/*' => 'mobile.rooms.show',
        'work_place' => 'mobile.workplaces.index',
        'work_place/*' => 'mobile.workplaces.show',
        'vehicles' => 'mobile.vehicles.index',
        'vehicles/*' => 'mobile.vehicles.show',
        'cashbox' => 'mobile.cashbox.index',
        'cashbox/*' => 'mobile.cashbox.show',
        'documents' => 'mobile.documents.index',
        'calendar' => 'mobile.calendar.index',
        'audit_log' => 'mobile.audit.index',
        'notifications' => 'mobile.notifications.index',
        'profile' => 'mobile.profile.index',
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip if already on mobile route
        if ($request->is('mobile/*')) {
            return $next($request);
        }

        // Skip if super admin
        if (Auth::check() && Auth::user()->type === 'super admin') {
            return $next($request);
        }

        // Skip if has desktop parameter
        if ($request->has('desktop')) {
            session(['force_desktop' => true]);
            return $next($request);
        }

        // Skip if user forced desktop mode
        if (session('force_desktop')) {
            return $next($request);
        }

        // Check if mobile device and redirect to mobile route
        if ($this->isMobileDevice($request)) {
            $mobileRoute = $this->getMobileRoute($request);
            if ($mobileRoute) {
                return redirect()->route($mobileRoute['route'], $mobileRoute['params']);
            }
        }

        return $next($request);
    }

    /**
     * Get the mobile route for the current request
     */
    private function getMobileRoute(Request $request): ?array
    {
        $path = $request->path();
        
        // Direct match
        if (isset($this->routeMappings[$path])) {
            return ['route' => $this->routeMappings[$path], 'params' => []];
        }

        // Pattern matching for routes with parameters
        foreach ($this->routeMappings as $pattern => $route) {
            if (strpos($pattern, '*') !== false) {
                $regex = str_replace('*', '([^/]+)', $pattern);
                $regex = '#^' . $regex . '$#';
                
                if (preg_match($regex, $path, $matches)) {
                    array_shift($matches); // Remove full match
                    return ['route' => $route, 'params' => ['id' => $matches[0] ?? null]];
                }
            }
        }

        return null;
    }

    /**
     * Check if the request is from a mobile device
     */
    private function isMobileDevice(Request $request): bool
    {
        $userAgent = $request->header('User-Agent');
        if (!$userAgent) {
            return false;
        }

        $mobileKeywords = [
            'Mobile', 'Android', 'iPhone', 'iPad', 'iPod', 'BlackBerry',
            'Windows Phone', 'Opera Mini', 'IEMobile', 'Mobile Safari'
        ];

        foreach ($mobileKeywords as $keyword) {
            if (stripos($userAgent, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}
