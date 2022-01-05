@if(env('APP_ENV') != 'local')
    @if(Customsetting::get('google_tagmanager_id'))
        <noscript>
            <iframe src="https://www.googletagmanager.com/ns.html?id={{Customsetting::get('google_tagmanager_id')}}"
                    height="0" width="0" style="display:none;visibility:hidden"></iframe>
        </noscript>
    @endif
@endif
