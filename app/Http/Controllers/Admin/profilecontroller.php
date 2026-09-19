<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function index(): ViewContract
    {
        return view('admin.profile.index', [
            'admin' => auth('admin')->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $admin = auth('admin')->user();

        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed', Password::defaults()],
        ], [], [
            'current_password' => 'رمز عبور فعلی',
            'password' => 'رمز عبور جدید',
        ]);

        if (! Hash::check($data['current_password'], $admin->password)) {
            return back()->withErrors(['current_password' => 'رمز عبور فعلی درست نیست.']);
        }

        $admin->forceFill(['password' => $data['password']])->save();

        return back()->with('status', 'رمز عبور تغییر کرد.');
    }
}
