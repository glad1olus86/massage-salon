<?php

namespace App\Http\Controllers\Auth;

use App\Events\VerifyReCaptchaToken;
use App\Models\Customer;
use App\Models\LoginDetail;
use App\Models\Plan;
use App\Models\Vender;
use  App\Models\Utility;
use  App\Models\User;
use Carbon\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use DateTime;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     *
     * @return \Illuminate\View\View
     */


    public function __construct()
    {
        if(!file_exists(storage_path() . "/installed"))
        {
            header('location:install');
            die;
        }
    }

    public function create()
    {
        // return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * @param \App\Http\Requests\Auth\LoginRequest $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */


    // protected function authenticated(Request $request)
    //    {


    //             $user = Auth::user();
    //        if($user->delete_status == 0)
    //        {
    //            auth()->logout();
    //        }

    //        if($user->is_active == 0)
    //        {
    //            auth()->logout();
    //        }
    //    }


    public function store(LoginRequest $request)
    {

        $user = User::where('email',$request->email)->first();
        if($user != null)
        {
            $companyUser = User::where('id' , $user->created_by)->first();
        }

        if($user != null && $user->is_disable == 0 && $user->type != 'company' && $user->type != 'super admin')
        {
            return redirect()->back()->with('status', __('Your Account is disable,please contact your Administrator.'));
        }

        if(($user != null && $user->is_enable_login == 0 || (isset($companyUser) && $companyUser != null) && $companyUser->is_enable_login == 0)  && $user->type != 'super admin')
        {
            return redirect()->back()->with('status', __('Your Account is disable from company.'));
        }

        $settings = Utility::settings();
        \App::setLocale($settings['default_language']);

        //ReCpatcha
        $validation = [];

        if(isset($settings['recaptcha_module']) && $settings['recaptcha_module'] == 'on')
        {
            if($settings['google_recaptcha_version'] == 'v2-checkbox'){
                $validation['g-recaptcha-response'] = 'required|captcha';
            }
            elseif($settings['google_recaptcha_version'] == 'v3'){
                $result = event(new VerifyReCaptchaToken($request));

                if (!isset($result[0]['status']) || $result[0]['status'] != true) {
                    $key = 'g-recaptcha-response';
                    $request->merge([$key => null]); // Set the key to null

                    $validation['g-recaptcha-response'] = 'required';
                }
            }else{
                $validation = [];
            }
        }
        else{
            $validation = [];
        }
        $this->validate($request, $validation);
        User::defaultEmail();
        if($user != null) {
            $user->userDefaultDataRegister($user->id);
        }

        $request->authenticate();
        $request->session()->regenerate();
        $user = Auth::user();

        $companyUser = User::find($user->created_by);
        $status = $companyUser ? $companyUser->delete_status : 1;

        if($user->delete_status == 0 || $status == 0)
        {
            auth()->logout();
            return redirect()->back()->with('status', __('Your Account is deleted by admin,please contact your Administrator.'));
        }

        if($user->is_active == 0)
        {
            auth()->logout();
            return redirect()->back()->with('status', __('Your Account is deactive by admin,please contact your Administrator.'));
        }

        $user = \Auth::user();
        if(isset($user->type) && $user->type == 'company')
        {
            $plan = Plan::find($user->plan);
            if($plan)
            {
                if($plan->duration != 'lifetime')
                {
                    $datetime1 = new \DateTime($user->plan_expire_date);
                    $datetime2 = new \DateTime(date('Y-m-d'));
                    //                    $interval  = $datetime1->diff($datetime2);
                    $interval = $datetime2->diff($datetime1);
                    $days     = $interval->format('%r%a');
                    if($days <= 0)
                    {
                        $user->assignPlan(1);

                        return redirect()->intended(RouteServiceProvider::HOME)->with('error', __('Your Plan is expired.'));
                    }
                }

                if($user->trial_expire_date != null)
                {
                    if(\Auth::user()->trial_expire_date < date('Y-m-d'))
                    {
                        $user->assignPlan(1);

                        return redirect()->intended(RouteServiceProvider::HOME)->with('error', __('Your Trial plan Expired.'));
                    }
                }
            }
        }

        if(isset($user->type) && $user->type == 'super admin') {
            Utility::migrationEntryDelete();
        }

        $setting = Utility::settingsById($user->creatorId());

        $timezone = $setting['timezone'] ? $setting['timezone'] : 'UTC';
        date_default_timezone_set($timezone);

        // Update Last Login Time and Language
        $updateData = [
            'last_login_at' => Carbon::now()->toDateTimeString(),
        ];
        
        // Save selected language from session
        $selectedLang = session('selected_lang');
        if ($selectedLang && in_array($selectedLang, ['en', 'ru', 'uk', 'cs'])) {
            $updateData['lang'] = $selectedLang;
            \App::setLocale($selectedLang);
        }
        
        $user->update($updateData);

        //start for user log
        if($user->type != 'company' && $user->type != 'super admin')
        {
//            $ip = '49.36.83.154'; // This is static ip address
            $ip = $_SERVER['REMOTE_ADDR']; // your ip address here
            $query = @unserialize(file_get_contents('http://ip-api.com/php/' . $ip));

            $whichbrowser = new \WhichBrowser\Parser($_SERVER['HTTP_USER_AGENT']);
            if ($whichbrowser->device->type == 'bot') {
                return;
            }
            $referrer = isset($_SERVER['HTTP_REFERER']) ? parse_url($_SERVER['HTTP_REFERER']) : null;

            /* Detect extra details about the user */
            $query['browser_name'] = $whichbrowser->browser->name ?? null;
            $query['os_name'] = $whichbrowser->os->name ?? null;
            $query['browser_language'] = isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2) : null;
            $query['device_type'] = get_device_type($_SERVER['HTTP_USER_AGENT']);
            $query['referrer_host'] = !empty($referrer['host']);
            $query['referrer_path'] = !empty($referrer['path']);

            isset($query['timezone'])?date_default_timezone_set($query['timezone']):'';

            $json = json_encode($query);

            $login_detail = new LoginDetail();
            $login_detail->user_id = Auth::user()->id;
            $login_detail->ip = $ip;
            $login_detail->date = date('Y-m-d H:i:s');
            $login_detail->Details = $json;
            $login_detail->created_by = \Auth::user()->creatorId();
            $login_detail->save();

        }
        //end for user log

        if($user->type =='company' || $user->type =='super admin' || $user->type =='client')
        {
            return redirect()->intended(RouteServiceProvider::HOME);

        }
        elseif($user->type == 'masseuse' || $user->hasRole('masseuse'))
        {
            // Массажисты всегда идут на свой дашборд (без intended чтобы не редиректило на admin)
            return redirect('/masseuse');
        }
        elseif($user->hasRole('operator'))
        {
            // Операторы идут на свой дашборд (будет создан позже)
            return redirect()->intended('/operator');
        }
        else
        {
            return redirect()->intended(RouteServiceProvider::EMPHOME);
        }

    }
    /**
     * Destroy an authenticated session.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        // Save language BEFORE logout (user->lang is the source of truth)
        $lang = 'en';
        if (\Auth::check() && \Auth::user()->lang) {
            $lang = \Auth::user()->lang;
        }
        
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        // Redirect to login with saved language
        return redirect('/login/' . $lang)->withCookie(cookie('selected_lang', $lang, 60 * 24 * 30)); // 30 days
    }

    public function showLoginForm($lang = '')
    {
        if($lang == '')
        {
            // Check cookie first, then session, then default
            $lang = request()->cookie('selected_lang') ?? session('selected_lang', Utility::getValByName('default_language'));
        }

        $langList = Utility::languages()->toArray();
        $allowedLangs = ['en', 'ru', 'uk', 'cs'];
        $lang = (array_key_exists($lang, $langList) && in_array($lang, $allowedLangs)) ? $lang : 'en';

        // Save to session and cookie for persistence
        session(['selected_lang' => $lang]);
        
        \App::setLocale($lang);

        $settings = Utility::settings();

        // Return view with cookie
        return response()
            ->view('auth.login-infinity', compact('lang','settings'))
            ->withCookie(cookie('selected_lang', $lang, 60 * 24 * 30)); // 30 days
    }

    public function showLinkRequestForm($lang = '')
    {
        if($lang == '')
        {
            // Check cookie first, then session, then default
            $lang = request()->cookie('selected_lang') ?? session('selected_lang', Utility::getValByName('default_language'));
        }

        $langList = Utility::languages()->toArray();
        $allowedLangs = ['en', 'ru', 'uk', 'cs'];
        $lang = (array_key_exists($lang, $langList) && in_array($lang, $allowedLangs)) ? $lang : 'en';

        \App::setLocale($lang);

        $settings = Utility::settings();

        return view('auth.forgot-password', compact('lang', 'settings'));
    }

    public function showResetForm(Request $request, $token = null)
    {

        return view('auth.passwords.reset')->with(
            [
                'token' => $token,
                'email' => $request->email,
            ]
        );
    }

}

//for user log
if (!function_exists('get_device_type')) {
    function get_device_type($user_agent)
    {
        $mobile_regex = '/(?:phone|windows\s+phone|ipod|blackberry|(?:android|bb\d+|meego|silk|googlebot) .+? mobile|palm|windows\s+ce|opera mini|avantgo|mobilesafari|docomo)/i';
        $tablet_regex = '/(?:ipad|playbook|(?:android|bb\d+|meego|silk)(?! .+? mobile))/i';
        if (preg_match_all($mobile_regex, $user_agent)) {
            return 'mobile';
        } else {
            if (preg_match_all($tablet_regex, $user_agent)) {
                return 'tablet';
            } else {
                return 'desktop';
            }
        }
    }
}
