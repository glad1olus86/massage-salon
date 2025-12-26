<?php

namespace App\Http\Middleware;

use App\Models\LandingPageSection;
use App\Models\User;
use App\Models\Utility;
use Closure;
use Config;

class XSS
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {

        if(\Auth::check())
        {
            $settings = Utility::settingsById(\Auth::user()->creatorId());
            if (!empty($settings['timezone'])) {
                Config::set('app.timezone', $settings['timezone']);
                date_default_timezone_set(Config::get('app.timezone', 'UTC'));
            }

            \App::setLocale(\Auth::user()->lang);

            // Auto-updater disabled - migrations should be run manually via artisan
            // if(\Auth::user()->type == 'super admin')
            // {
            //     ... old updater code removed ...
            // }
        }

        $input = $request->all();


        $request->merge($input);

        return $next($request);
    }
}
