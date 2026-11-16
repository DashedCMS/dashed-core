<?php

namespace Dashed\DashedCore\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * Een beheerder heeft een adres bij Postmark van de blokkadelijst gehaald.
 * Pakketten met een eigen blokkadelijst (de nieuwsbrief) luisteren hierop,
 * anders krijgt het adres van ons alsnog niets.
 */
class EmailSuppressionReactivated
{
    use Dispatchable;

    public function __construct(public string $email, public string $stream)
    {
    }
}
