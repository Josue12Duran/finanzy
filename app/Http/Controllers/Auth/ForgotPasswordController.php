<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendResetLinkRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm(): View
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(SendResetLinkRequest $request): RedirectResponse
    {
        $estado = Password::sendResetLink($request->only('email'));

        return $estado === Password::RESET_LINK_SENT
            ? back()->with('status', __($estado))
            : back()->withInput($request->only('email'))->withErrors(['email' => __($estado)]);
    }
}
