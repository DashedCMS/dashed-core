<?php

namespace Qubiqx\QcommerceCore\View\Components;

use Illuminate\View\Component;

class FrontendBodyExtend extends Component
{
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('qcommerce-core::components.frontend.body-extend');
    }
}
