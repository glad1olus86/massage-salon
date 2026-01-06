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

    /**
     * Handle masseuse registration request.
     */
    public function storeMasseuse(Request $request)
    {
        \Log::info('Masseuse registration started', [
            'has_avatar' => $request->hasFile('avatar'),
            'photos_count' => $request->hasFile('photos') ? count($request->file('photos')) : 0,
            'all_files' => $request->allFiles(),
        ]);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', 'min:8'],
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'nationality' => 'nullable|string|max:50',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'photos' => 'nullable|array|max:8',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'services' => 'nullable|array',
            'services.*' => 'exists:massage_services,id',
            'extra_services' => 'nullable|array',
            'extra_services.*' => 'exists:massage_services,id',
            'about' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]);

        // Создаем пользователя-массажистку
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);
        $user->type = 'user';
        $user->created_by = 2; // ID владельца салона
        $user->lang = \App::getLocale();
        $user->birth_date = $request->birth_date;
        $user->nationality = $request->nationality;
        $user->bio = $request->about; // Поле "О себе" из формы регистрации
        $user->is_active = $request->is_active ?? 1;
        $user->plan = 1;

        // Обработка аватара
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars/2', 'public');
            $user->avatar = $path;
            \Log::info('Avatar uploaded', ['path' => $path]);
        }

        // Обработка фотогалереи
        $photos = [];
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $photo) {
                if (count($photos) >= 8) break;
                $path = $photo->store('employees/2', 'public');
                $photos[] = $path;
            }
            \Log::info('Photos uploaded', ['count' => count($photos), 'paths' => $photos]);
        }
        $user->photos = $photos;

        $user->save();

        // Email verification - always mark as verified
        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->save();

        // Назначаем роль masseuse
        $role = Role::findByName('masseuse');
        $user->assignRole($role);

        // Привязка услуг (обычные и экстра)
        $syncData = [];
        foreach ($request->services ?? [] as $serviceId) {
            $syncData[$serviceId] = ['is_extra' => false];
        }
        foreach ($request->extra_services ?? [] as $serviceId) {
            if (!isset($syncData[$serviceId])) {
                $syncData[$serviceId] = ['is_extra' => true];
            }
        }
        if (!empty($syncData)) {
            $user->massageServices()->sync($syncData);
        }

        \Log::info('Masseuse registration completed', ['user_id' => $user->id]);

        // Логиним пользователя
        \Auth::login($user);

        return redirect()->route('masseuse.dashboard')
            ->with('success', __('Регистрация успешно завершена! Добро пожаловать!'));
    }
}
