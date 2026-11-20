<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;

use App\Models\User;
use App\Models\LogResetPasswordToken;
use RealRashid\SweetAlert\Facades\Alert;

class AuthController extends Controller
{
  public function showLogin()
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

  public function forgotPassword()
  {
      return view('auth.forgot-password');
  }

public function sendResetLink(Request $request)
{
    $username = $request->input('username');
    $password = $request->input('password');
    $hashedPassword = Hash::make($password);
    $existUser = User::where('user_name', $username)->first();

    if (!$existUser ) {
        Alert::warning("Error", 'User data doesnt exists, please input valid NIK!');
        return redirect()->route('forgot.password')->withInput();
    } else {
      $existUser->password = $hashedPassword;
      $existUser->save();
      Alert::success("Success", 'your password has been reset successfully!');
      return redirect()->route('forgot.password');
    }

    // $status = Password::sendResetLink(
    //     $request->only('email')
    // );
    // return $status == Password::RESET_LINK_SENT
    //             ? back()->with(['status' => __($status)])
    //             : back()->withErrors(['email' => __($status)]);
}

}
