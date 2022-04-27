<?php

namespace App\Http\Controllers\Auth;

use App\Models\Users;
use Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Foundation\Auth\AuthenticatesAndRegistersUsers;
use Illuminate\Support\Facades\Auth as fAuth;
use Illuminate\Support\Facades\Session;
use Hash;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Registration & Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users, as well as the
    | authentication of existing users. By default, this controller uses
    | a simple trait to add these behaviors. Why don't you explore it?
    |
    */

    use AuthenticatesAndRegistersUsers, ThrottlesLogins;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = '/gatekeeper';
    

    /**
     * Create a new authentication controller instance.
     *
     * @return void
     */
    public function __construct()
    {
//		$this->middleware($this->guestMiddleware(), ['except' => 'logout']);
    	$this->middleware('guest', ['except' => ['logout', 'getLogout']]); // Default router name is "logout" should be same router
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        return Users::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
    }
    
    /**
     * Handle a login request to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function login(Request $request)
    {
    	$this->validateLogin($request);
    
    	// If the class is using the ThrottlesLogins trait, we can automatically throttle
    	// the login attempts for this application. We'll key this by the username and
    	// the IP address of the client making these requests into this application.
    	$throttles = $this->isUsingThrottlesLoginsTrait();
    
    	if ($throttles && $lockedOut = $this->hasTooManyLoginAttempts($request)) {
    		$this->fireLockoutEvent($request);
    
    		return $this->sendLockoutResponse($request);
    	}
    
    	$credentials = $this->getCredentials($request);
    
	    if (fAuth::guard($this->getGuard())->attempt($credentials, $request->has('remember'))) {
		    return $this->handleUserWasAuthenticated($request, $throttles);
		} else {
		    $user = Users::where('email', $request->email)->first();
		    if ($user && $user->password == md5($request->password)) {
		        $user->password = Hash::make($request->password);
		        $user->save();
		
		        if (fAuth::guard($this->getGuard())->attempt($credentials, $request->has('remember'))) {
		            return $this->handleUserWasAuthenticated($request, $throttles);
		        }
		    }
		}
    
    	// If the login attempt was unsuccessful we will increment the number of attempts
    	// to login and redirect the user back to the login form. Of course, when this
    	// user surpasses their maximum number of attempts they will get locked out.
    	if ($throttles && ! $lockedOut) {
    		$this->incrementLoginAttempts($request);
    	}
    
    	return $this->sendFailedLoginResponse($request);
    }
}
