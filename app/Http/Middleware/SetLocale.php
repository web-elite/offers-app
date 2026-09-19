<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public const SUPPORTED = ['fa', 'en'];

    /**
     * Apply locale from session (or from the ?lang query string for first-time switching)
     * to the current request and to <html lang/dir> via the shared view.
     */
    public function handle(Request $request, Closure $next)
    {
        // One-time switch via ?lang=en or ?lang=fa
        if ($request->has('lang')) {
            $chosen = (string) $request->input('lang');

            if (in_array($chosen, self::SUPPORTED, true)) {
                session(['locale' => $chosen]);
            }
        }

        $locale = session('locale', config('app.locale', 'fa'));

        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = 'fa';
        }

        App::setLocale($locale);

        return $next($request);
    }
}
