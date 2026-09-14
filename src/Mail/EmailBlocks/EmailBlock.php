<?php

namespace Dashed\DashedCore\Mail\EmailBlocks;

use Filament\Forms\Components\Builder\Block;

abstract class EmailBlock
{
    public const CONTEXT_TRANSACTIONAL = 'transactional';
    public const CONTEXT_NEWSLETTER = 'newsletter';

    /**
     * Waar dit blok thuishoort. Standaard alleen transactioneel, zodat een
     * blok dat hier niets over zegt nooit ongemerkt in een nieuwsbrief
     * verschijnt. Een bestelsamenvatting in een nieuwsbrief is onzin, en een
     * productraster in een wachtwoord-vergeten-mail net zo goed.
     *
     * @return array<int, string>
     */
    public static function contexts(): array
    {
        return [self::CONTEXT_TRANSACTIONAL];
    }

    public static function inContext(string $context): bool
    {
        return in_array($context, static::contexts(), true);
    }

    /**
     * Of dit blok per ontvanger gerenderd moet worden. Standaard nee: de
     * nieuwsbrief rendert één keer per campagne en vervangt daarna alleen
     * plaatshouders (CampaignRenderer::substitute()). Een blok dat iets van
     * de ontvanger zelf toont (zijn verlanglijst) zet dit op true; de
     * nieuwsbrief laat dan een plaatshouder staan en roept per ontvanger
     * renderForRecipient() aan met 'recipientEmail' en 'subscriber' in de
     * context. Kost per ontvanger één extra render van alleen dat blok.
     */
    public static function perRecipient(): bool
    {
        return false;
    }

    /**
     * De per-ontvanger-variant van render(). Zelfde blokdata, dezelfde context
     * als render() plus 'recipientEmail' (string) en 'subscriber' (het
     * NewsletterSubscriber-model of null bij een proefmail). Standaard
     * gewoon render(), zodat een blok dat perRecipient() aanzet maar deze
     * methode niet overschrijft nog steeds iets oplevert.
     *
     * @param  array<string, mixed>  $blockData
     * @param  array<string, mixed>  $context
     */
    public static function renderForRecipient(array $blockData, array $context): string
    {
        return static::render($blockData, $context);
    }

    abstract public static function key(): string;

    abstract public static function label(): string;

    abstract public static function filamentBlock(): Block;

    abstract public static function render(array $blockData, array $context): string;

    /**
     * Een mailprogramma heeft geen basis-URL, dus een relatief pad
     * ('/producten/x') is daar een dode link. Bovendien slaat LinkRewriter
     * alles over wat niet met https begint, dus zo'n link wordt ook nooit
     * gemeten. De basis is de site-URL uit de context (CampaignRenderer geeft
     * die mee per lijst), met de app-URL als terugval voor aanroepen zonder.
     */
    protected static function absoluteUrl(string $url, array $context): string
    {
        if (preg_match('#^(https?:)?//#i', $url)) {
            return $url;
        }

        $basis = rtrim((string) ($context['siteUrl'] ?? config('app.url')), '/');

        return $basis . '/' . ltrim($url, '/');
    }

    protected static function substitute(string $text, array $context): string
    {
        return preg_replace_callback('/:(\w+):/', function ($m) use ($context) {
            return array_key_exists($m[1], $context) ? (string) $context[$m[1]] : $m[0];
        }, $text);
    }
}
