<?php

namespace App\Http\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class UnauthorizedResponse
{
    public const ACCESS_DENIED_MESSAGE = 'You do not have access to this page.';

    public static function deny(Request $request): RedirectResponse
    {
        if ($request->user() === null) {
            return redirect()->guest(route('login'));
        }

        return redirect()
            ->route('dashboard')
            ->with('error', self::ACCESS_DENIED_MESSAGE);
    }
}
