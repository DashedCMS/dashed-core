<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Illuminate\Http\Response;
use Dashed\DashedCore\Classes\Avatars\InitialsAvatar;

/**
 * /user-avatar/{initials}: ronde SVG met initialen voor gebruikers zonder
 * eigen foto. Buiten de web-groep (geen sessie of cookies nodig) en een jaar
 * cachebaar; de ?v= in de URL wijzigt mee met de kleuren.
 */
class UserAvatarController
{
    public function __invoke(string $initials): Response
    {
        return response(InitialsAvatar::svg($initials), 200, [
            'Content-Type' => 'image/svg+xml; charset=utf-8',
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; style-src 'unsafe-inline'",
        ]);
    }
}
