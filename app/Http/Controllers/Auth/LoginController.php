<?php

# app/Http/Controllers/Auth/LoginController.php
namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use App\Models\WebProperty;

class LoginController extends Controller
{
    use AuthenticatesUsers;

    protected $redirectTo = '/dashboard'; // Redirect to dashboard after login

    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    public function showLoginForm()
    {
        $webProperty = WebProperty::first(); // Fetch the first WebProperty record

        return view('auth.login', ['webProperty' => $webProperty]);
    }
}
