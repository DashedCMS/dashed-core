<?php

namespace Dashed\DashedCore\Http\Controllers;

use Illuminate\Http\Request;
use Dashed\DashedCore\Security\Uploads\UploadPolicy;
use Dashed\DashedCore\Security\Uploads\PrivateUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Serveert een privé upload (PrivateUploads) aan een paneelgebruiker, of aan
 * wie een geldige, tijdelijke gesigneerde link heeft. Gasten en klanten
 * zonder geldige handtekening krijgen een 403.
 */
class PrivateUploadController
{
    public function __invoke(Request $request, string $path): StreamedResponse
    {
        abort_unless($request->hasValidSignature() || UploadPolicy::isPanelUser($request->user()), 403);

        $reference = PrivateUploads::PREFIX . $path;
        abort_unless(PrivateUploads::isPrivate($reference), 404);
        abort_unless(PrivateUploads::disk()->exists($reference), 404);

        $extension = PrivateUploads::extension($reference);
        $inline = in_array($extension, PrivateUploads::INLINE_TYPES, true);

        return PrivateUploads::disk()->response($reference, basename($reference), [
            'Content-Type' => PrivateUploads::CONTENT_TYPES[$extension] ?? 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self' data:; style-src 'unsafe-inline'; sandbox",
            'Referrer-Policy' => 'no-referrer',
        ], $inline ? 'inline' : 'attachment');
    }
}
