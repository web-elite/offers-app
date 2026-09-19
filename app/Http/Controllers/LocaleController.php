<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    /**
     * Switch the active UI language.
     *
     * Redirects back to the previous page with `?lang=xx` appended; the `SetLocale`
     * middleware then persists the choice to the session and re-renders the view
     * in the new language while preserving any other query params (filters, etc.).
     */
    public function switch(Request $request, string $lang): RedirectResponse
    {
        if (! in_array($lang, SetLocale::SUPPORTED, true)) {
            return back();
        }

        $query = $request->query();
        $query['lang'] = $lang;

        return redirect()->to(url()->previous() ?: url('/'))->withQuery($query);
    }
}
