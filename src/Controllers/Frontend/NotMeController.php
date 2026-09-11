<?php

namespace Dashed\DashedCore\Controllers\Frontend;

use Illuminate\Http\Request;
use Dashed\DashedCore\Models\User;
use Illuminate\Routing\Controller;
use Dashed\DashedCore\Classes\LockUserAction;

/**
 * "Dit was ik niet"-link uit de loginmail. GET toont eerst een bevestigknop,
 * want mailscanners volgen links en die mogen niet per ongeluk accounts
 * vergrendelen; POST vergrendelt echt. De route zit achter de handtekening
 * en een verzoeklimiet.
 */
class NotMeController extends Controller
{
    public function __invoke(Request $request, LockUserAction $lockUser)
    {
        $user = User::query()->findOrFail((int) $request->route('user'));

        if ($request->isMethod('GET')) {
            return response()->view('dashed-core::security.not-me', ['user' => $user, 'alreadyLocked' => ! $user->mustLoginViaPanel()]);
        }

        if ($user->mustLoginViaPanel()) {
            $lockUser->handle($user, 'dit-was-ik-niet-link, bevestigd vanaf IP ' . $request->ip());
        }

        return response()->view('dashed-core::security.not-me-done', ['user' => $user]);
    }
}
