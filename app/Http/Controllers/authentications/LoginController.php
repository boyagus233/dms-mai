<?php

namespace App\Http\Controllers\authentications;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
  public function index()
  {
    return view('auth.login');
  }

  public function login(Request $request)
  {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt(['user_name' => $credentials['username'], 'password' => $credentials['password']])) {
            $request->session()->regenerate();
            return redirect()->intended(route('dashboard'));
        }  else {
            //Session::flash('error', 'Email atau Password Salah');
            return back()->withErrors([
                'username' => 'Invalid email or password.',
            ])->onlyInput('username');
        }
  }

  public function logout()
  {
      Auth::logout();
      return view('auth.login');
  }

}
