<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ExperienceCertificate;
use App\Models\GenerateOfferLetter;
use App\Models\JoiningLetter;
use App\Models\NOC;
use App\Models\User;
use  App\Models\Utility;
use Auth;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Spatie\Permission\Models\Role;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     *
     * @return \Illuminate\View\View
     */

  public function __construct()
    {
        $this->middleware('guest');
    }


    public function create()
    {
        // return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'min:8', Rules\Password::defaults()],
            'terms' => 'required',
        ]);

        do {
            $code = rand(100000, 999999);
        } while (User::where('referral_code', $code)->exists());

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'type' => 'company',
            'default_pipeline' => 1,
            'plan' => 1,
            'lang' => Utility::getValByName('default_language'),
            'avatar' => '',
            'referral_code'=> $code,
            'used_referral_code'=> $request->ref_code ?? '',
            'created_by' => 1,
        ]);
        \Auth::login($user);

        // Email verification disabled - always mark as verified
        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->save();
        
        $role_r = Role::findByName('company');
        $user->assignRole($role_r);
        $user->userDefaultData($user->id);
        $user->userDefaultDataRegister($user->id);
        //default bank account for new company
        $user->userDefaultBankAccount($user->id);
        // Copy theme settings from super admin
        User::copyThemeSettings($user->id);

        Utility::chartOfAccountTypeData($user->id);
        // default chart of account for new company
        Utility::chartOfAccountData1($user->id);

        GenerateOfferLetter::defaultOfferLetterRegister($user->id);
        ExperienceCertificate::defaultExpCertificatRegister($user->id);
        JoiningLetter::defaultJoiningLetterRegister($user->id);
        NOC::defaultNocCertificateRegister($user->id);

        if (isset($request->plan) && Crypt::decrypt($request->plan) && Crypt::decrypt($request->plan) != 1) {
            return redirect()->route('stripe', ['code' => $request->plan]);
        } else {
            return redirect(RouteServiceProvider::HOME);
        }

    }

    public function showRegistrationForm(Request $request, $ref = '' , $lang = '')
    {
        $settings = Utility::settings();

        if($settings['enable_signup'] == 'on')
        {
            $langList = Utility::languages()->toArray();
            $lang = array_key_exists($lang, $langList) ? $lang : 'en';

            if($lang == '')
            {
                $lang = Utility::getValByName('default_language');
            }
            \App::setLocale($lang);
            if($ref == '')
            {
                $ref = 0;
            }

            $refCode = User::where('referral_code' , '=', $ref)->first();
            if(isset($refCode) && $refCode->referral_code != $ref)
            {
                return redirect()->route('register');
            }

            $plan = null;
            if($request->plan){
                $plan = $request->plan;
            }
            return view('auth.register', compact('lang' , 'ref', 'plan'));
        }
        else
        {
            return \Redirect::to('login');
        }
    }
}
