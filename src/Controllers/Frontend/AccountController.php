<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedTranslations\Models\Translation;
use Dashed\DashedCore\Requests\Frontend\UpdateAccountRequest;

class AccountController extends FrontendController
{
    public function account()
    {
        if (view()->exists('dashed.account.show')) {
            seo()->metaData('metaTitle', Translation::get('account-page-meta-title', 'account', 'Account'));
            seo()->metaData('metaDescription', Translation::get('account-page-meta-description', 'account', 'View your account here'));

            return view(env('SITE_THEME', 'dashed').'.account.show');
        } else {
            return $this->pageNotFound();
        }
    }

    public function accountPost(UpdateAccountRequest $request)
    {
        throw new Exception('The accountPost method is outdated, use Livewire instead');

        $user = Auth::user();
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;

        if ($request->password) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->back()->with('success', Translation::get('account-updated', 'account', 'Your account has been updated'));
    }
}
