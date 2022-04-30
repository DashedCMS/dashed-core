<?php

namespace Qubiqx\QcommerceCore\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\SchemaOrg\Schema;
use App\Classes\CustomSettings;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\View;
use Qubiqx\QcommerceCore\Classes\Sites;
use Qubiqx\QcommerceCore\Models\Customsetting;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

class FrontendMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        App::setLocale(LaravelLocalization::getCurrentLocale());

        //Todo: create JSON schema's
        seo()->metaData('webmasterTags', [
            'google' => Customsetting::get('webmaster_tag_google'),
            'bing' => Customsetting::get('webmaster_tag_bing'),
            'alexa' => Customsetting::get('webmaster_tag_alexa'),
            'pinterest' => Customsetting::get('webmaster_tag_pinterest'),
            'yandex' => Customsetting::get('webmaster_tag_yandex'),
            'norton' => Customsetting::get('webmaster_tag_norton'),
        ]);
        seo()->metaData('robots', env('APP_ENV') == 'local' ? 'noindex, nofollow' : 'index, follow');
        seo()->metaData('metaTitle', Customsetting::get('site_name', Sites::getActive(), 'Website'));

        $schema = Schema::localBusiness()
            ->legalName(CustomSettings::get('site-name', 'Quezy'))
            ->email(CustomSettings::get('contact-email', 'help@quezy.io'))
            ->telephone(CustomSettings::get('contact-number', '085 - 732 69 02'))
            ->logo(asset('/assets/files/branding/quezy-logo.svg'))
            ->address(CustomSettings::get('contact-location', 'Bijsterhuizen 1158, 6546AS Nijmegen, Nederland'))
            ->url($request->url())
            ->priceRange('$')
            ->contactPoint(
                Schema::contactPoint()
                    ->telephone(CustomSettings::get('contact-number', '085 - 732 69 02'))
                    ->email(CustomSettings::get('contact-email', 'help@quezy.io'))
            );

        $logo = Customsetting::get('site_logo', Sites::getActive(), '');
        $favicon = Customsetting::get('site_favicon', Sites::getActive(), '');

        View::share('logo', $logo);
        View::share('favicon', $favicon);
        View::share('schema', $schema);

        return $next($request);
    }
}
