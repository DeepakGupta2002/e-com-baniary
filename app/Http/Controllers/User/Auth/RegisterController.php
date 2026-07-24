<?php

namespace App\Http\Controllers\User\Auth;

use App\Models\User;
use App\Lib\Intended;
use App\Constants\Status;
use App\Models\UserExtra;
use App\Models\UserLogin;
use Illuminate\Http\Request;
use App\Models\AdminNotification;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{

    use RegistersUsers;

    public function __construct()
    {
        parent::__construct();
    }

    public function showRegistrationForm(Request $request)
    {
        $pageTitle = "Register";
        $refUser = null;
        $position = null;

        if ($request->filled('ref')) {
            $refUser = User::where('username', $request->ref)->first();
        }

        if ($request->filled('position')) {
            $position = strtolower($request->position) == 'right' ? Status::RIGHT : Status::LEFT;
        }

        Intended::identifyRoute();
        return view('Template::user.auth.register', compact('pageTitle', 'refUser', 'position'));
    }


    protected function validator(array $data)
    {

        $passwordValidation = Password::min(6);

        if (gs('secure_password')) {
            $passwordValidation = $passwordValidation->mixedCase()->numbers()->symbols()->uncompromised();
        }

        $agree = 'nullable';
        if (gs('agree')) {
            $agree = 'required';
        }

        $validate     = Validator::make($data, [
            'firstname' => 'required',
            'lastname'  => 'required',
            'referBy'   => 'required|string|max:160',
            'position'  => 'required|integer|in:' . Status::LEFT . ',' . Status::RIGHT,
            'email'     => 'required|string|email|unique:users',
            'password'  => ['required', 'confirmed', $passwordValidation],
            'captcha'   => 'sometimes|required',
            'agree'     => $agree
        ], [
            'firstname.required' => 'The first name field is required',
            'lastname.required' => 'The last name field is required'
        ]);

        return $validate;
    }

    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        if (!gs('registration')) {
            return back();
        }

        $request->session()->regenerateToken();

        if (!verifyCaptcha()) {
            $notify[] = ['error', 'Invalid captcha provided'];
            return back()->withNotify($notify);
        }

        event(new Registered($user = $this->create($request->all())));

        $this->guard()->login($user);

        return $this->registered($request, $user) ?: redirect($this->redirectPath());
    }



    protected function create(array $data)
    {
        $refUser = User::where('username', $data['referBy'])->first();
        $positionData = ['pos_id' => 0, 'position' => 0];

        if ($refUser) {
            $positionData = getPosition($refUser->id, $data['position']);
        }

        $user            = new User();
        $user->ref_by    = $refUser?->id ?? 0;
        $user->pos_id    = $positionData['pos_id'];
        $user->position  = $positionData['position'];
        $user->email     = strtolower($data['email']);
        $user->firstname = $data['firstname'];
        $user->lastname  = $data['lastname'];
        $user->password  = Hash::make($data['password']);
        $user->kv = gs('kv') ? Status::NO : Status::YES;
        $user->ev = gs('ev') ? Status::NO : Status::YES;
        $user->sv = gs('sv') ? Status::NO : Status::YES;
        $user->ts = Status::DISABLE;
        $user->tv = Status::ENABLE;
        $user->save();

        $adminNotification            = new AdminNotification();
        $adminNotification->user_id   = $user->id;
        $adminNotification->title     = 'New member registered';
        $adminNotification->click_url = urlPath('admin.users.detail', $user->id);
        $adminNotification->save();


        //Login Log Create
        $ip        = getRealIP();
        $exist     = UserLogin::where('user_ip', $ip)->first();
        $userLogin = new UserLogin();

        if ($exist) {
            $userLogin->longitude    = $exist->longitude;
            $userLogin->latitude     = $exist->latitude;
            $userLogin->city         = $exist->city;
            $userLogin->country_code = $exist->country_code;
            $userLogin->country      = $exist->country;
        } else {
            $info                    = json_decode(json_encode(getIpInfo()), true);
            $userLogin->longitude    = @implode(',', $info['long']);
            $userLogin->latitude     = @implode(',', $info['lat']);
            $userLogin->city         = @implode(',', $info['city']);
            $userLogin->country_code = @implode(',', $info['code']);
            $userLogin->country      = @implode(',', $info['country']);
        }

        $userAgent          = osBrowser();
        $userLogin->user_id = $user->id;
        $userLogin->user_ip = $ip;

        $userLogin->browser = @$userAgent['browser'];
        $userLogin->os      = @$userAgent['os_platform'];
        $userLogin->save();


        return $user;
    }

    public function checkUser(Request $request)
    {
        $exist['data'] = false;
        $exist['type'] = null;
        if ($request->email) {
            $exist['data'] = User::where('email', $request->email)->exists();
            $exist['type'] = 'email';
            $exist['field'] = 'Email';
        }
        if ($request->mobile) {
            $exist['data'] = User::where('mobile', $request->mobile)->where('dial_code', $request->mobile_code)->exists();
            $exist['type'] = 'mobile';
            $exist['field'] = 'Mobile';
        }
        if ($request->username) {
            $exist['data'] = User::where('username', $request->username)->exists();
            $exist['type'] = 'username';
            $exist['field'] = 'Username';
        }
        return response($exist);
    }

    public function registered(Request $request, $user)
    {
        $user_extras = new UserExtra();
        $user_extras->user_id = $user->id;
        $user_extras->save();
        updateFreeCount($user->id);
        return to_route('user.home');
    }
}
