<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Dashed\DashedCore\Models\Customsetting;
use Dashed\DashedCore\Requests\Frontend\UpdateAccountRequest;
use Dashed\DashedTranslations\Models\Translation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AccountController extends FrontendController
{
    public function account()
    {
        if (view()->exists('dashed.account.show')) {
            seo()->metaData('metaTitle', Translation::get('account-page-meta-title', 'account', 'Account'));
            seo()->metaData('metaDescription', Translation::get('account-page-meta-description', 'account', 'View your account here'));

            return view(Customsetting::get('site_theme', null, 'dashed').'.account.show');
        } else {
            return $this->pageNotFound();
        }
    }

    public function accountPost(UpdateAccountRequest $request)
    {
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
