<?php

namespace Dashed\DashedCore\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Dashed\DashedCore\Services\VisualEditor\VisualEditor;

class VisualEditorController extends Controller
{
    public function toggle(Request $request, VisualEditor $editor): \Illuminate\Http\RedirectResponse
    {
        if (! $editor->isAdmin()) {
            abort(403);
        }

        $request->session()->put(VisualEditor::SESSION_FLAG, $request->boolean('enable'));

        return redirect()->back();
    }
}
