<?php

namespace Dashed\DashedCore\Mail\Contracts;

interface RegistersEmailTemplate
{
    public static function emailTemplateKey(): string;

    public static function emailTemplateName(): string;

    public static function emailTemplateDescription(): ?string;

    public static function availableVariables(): array;

    public static function availableBlockKeys(): array;

    public static function sampleData(): array;
}
