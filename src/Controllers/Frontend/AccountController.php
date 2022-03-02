<?php

namespace Qubiqx\QcommerceCore\Controllers\Frontend;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\View;
use Artesaos\SEOTools\Facades\SEOTools;
use Qubiqx\QcommerceTranslations\Models\Translation;
use Qubiqx\QcommerceCore\Requests\Frontend\UpdateAccountRequest;

class AccountController extends FrontendController
{
    public function account()
    {
        if (View::exists('qcommerce.account.show')) {
            SEOTools::setTitle(Translation::get('account-page-meta-title', 'account', 'Account'));
            SEOTools::setDescription(Translation::get('account-page-meta-description', 'account', 'View your account here'));
            SEOTools::opengraph()->setUrl(url()->current());

            return view('qcommerce.account.show');
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
