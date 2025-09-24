<?php

namespace App\Protectors;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Statamic\Auth\Protect\Protectors\Authenticated;

class AuthVerified extends Authenticated
{
    public function protect()
    {
        if (Auth::guest()) {
            abort(redirect($this->getLoginUrl()));
        }

        $user = Auth::user();

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            abort(redirect(route('verification.notice')));
        }
    }
}
