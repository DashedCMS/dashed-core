<?php

namespace Dashed\DashedCore\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Dashed\DashedCore\Classes\DelayedScripts;

class DelayedScriptsTest extends TestCase
{
    public function test_scripts_are_rewritten_to_delayed_placeholders(): void
    {
        $html = '<script src="https://t.contentsquare.net/uxa/x.js" async></script>'
            ."\n<script>!function(w){w.ttq=[]}(window);</script>"
            ."\n<script type=\"text/javascript\" src=\"https://a.example/a.js\"></script>";

        $expected = '<script type="text/plain" data-dashed-delayed src="https://t.contentsquare.net/uxa/x.js" async></script>'
            ."\n<script type=\"text/plain\" data-dashed-delayed>!function(w){w.ttq=[]}(window);</script>"
            ."\n<script type=\"text/plain\" data-dashed-delayed src=\"https://a.example/a.js\"></script>";

        $this->assertSame($expected, DelayedScripts::rewrite($html));
    }

    public function test_structured_data_and_already_delayed_scripts_are_left_alone(): void
    {
        $html = '<script type="application/ld+json">{"@type":"Thing"}</script>'
            .'<script type="text/plain" data-dashed-delayed src="https://a.example/a.js"></script>'
            .'<noscript><img src="https://www.facebook.com/tr?id=1&ev=PageView&noscript=1"/></noscript>';

        $this->assertSame($html, DelayedScripts::rewrite($html));
        $this->assertSame('', DelayedScripts::rewrite(null));
    }

    public function test_loader_defines_the_hook_and_watches_interaction(): void
    {
        $loader = DelayedScripts::loader();

        $this->assertStringContainsString('script[data-dashed-delayed]', $loader);
        $this->assertStringContainsString('dashedLoadDelayedScripts', $loader);
        $this->assertStringContainsString("'pointerdown'", $loader);
        $this->assertStringContainsString('setTimeout(run, 3000)', $loader);
    }
}
