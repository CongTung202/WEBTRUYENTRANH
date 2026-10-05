<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $loginValue = $request->input('login_input') ?? $request->input('email');

        $request->validate([
            'password' => 'required|string|min:6',
        ]);

        if (empty($loginValue)) {
            return back()->withErrors(['login_input' => 'Vui lòng nhập email hoặc tên đăng nhập.']);
        }

        $fieldType = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'name';
        $credentials = [
            $fieldType => $loginValue,
            'password' => $request->password,
        ];

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            if (Auth::user()->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'))->with('success', 'Chào mừng Admin quay trở lại!');
            }

            return redirect()->intended(route('home'))->with('success', 'Đăng nhập thành công!');
        }

        return back()->withErrors([
            'login_input' => 'Tài khoản hoặc mật khẩu không chính xác.',
        ])->withInput();
    }

    public function showRegister()
    {
        if (Auth::check()) {
            return redirect()->route('home');
        }
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Support either username or name
        if (!$request->has('name') && $request->has('username')) {
            $request->merge(['name' => $request->username]);
        }
        if (!$request->has('password') && $request->has('register_password')) {
            $request->merge(['password' => $request->register_password]);
        }
        if (!$request->has('password_confirmation') && $request->has('confirm_password')) {
            $request->merge(['password_confirmation' => $request->confirm_password]);
        }

        $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'Vui lòng nhập tên đăng nhập.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email này đã được đăng ký tài khoản.',
            'password.required' => 'Vui lòng nhập mật khẩu.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
            'password.confirmed' => 'Xác nhận mật khẩu không khớp.',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'user',
        ]);

        Auth::login($user);

        return redirect()->route('home')->with('success', 'Đăng ký tài khoản thành công!');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Đã đăng xuất thành công.');
    }

    public function profile()
    {
        $user = Auth::user();
        return view('user.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:100',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        $user->name = $request->name;

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return back()->with('success', 'Cập nhật thông tin thành công!');
    }
}
