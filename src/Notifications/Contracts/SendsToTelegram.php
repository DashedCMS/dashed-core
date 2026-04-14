<?php

namespace Dashed\DashedCore\Notifications\Contracts;

use Dashed\DashedCore\Notifications\DTOs\TelegramSummary;

interface SendsToTelegram
{
    public function telegramSummary(): TelegramSummary;
}
