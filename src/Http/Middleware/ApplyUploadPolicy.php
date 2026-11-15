<?php

namespace Dashed\DashedCore\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Dashed\DashedCore\Security\Uploads\UploadPolicy;
use Dashed\DashedCore\Security\Uploads\GuestUploadRule;

/**
 * Hangt aan /livewire/upload-file (UploadSecurity zet hem in de Livewire-config).
 * Livewire leest de uploadregels uit de config; voor bezoekers zonder
 * paneelrechten komen daar per request de gastregel en eventueel een
 * gastmaximum bij.
 */
class ApplyUploadPolicy
{
    public function handle(Request $request, Closure $next)
    {
        if (UploadPolicy::active() && ! UploadPolicy::isPanelUser($request->user())) {
            $rules = array_values((array) config('livewire.temporary_file_upload.rules', []));

            if (! in_array(GuestUploadRule::RULE, $rules, true)) {
                $rules[] = GuestUploadRule::RULE;
            }

            if ($max = UploadPolicy::guestMaxKilobytes()) {
                $rules[] = 'max:' . $max;
            }

            config(['livewire.temporary_file_upload.rules' => $rules]);
        }

        return $next($request);
    }
}
