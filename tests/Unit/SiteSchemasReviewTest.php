<?php

namespace Dashed\DashedCore\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Dashed\DashedCore\Classes\Seo\SiteSchemas;

class SiteSchemasReviewTest extends TestCase
{
    public function test_review_text_becomes_one_clean_line(): void
    {
        $this->assertSame('Mooie vaas, snel geleverd!', SiteSchemas::cleanText("  Mooie vaas,\n\n snel   geleverd! "));
    }

    public function test_short_review_keeps_its_text_as_title(): void
    {
        $this->assertSame('Top!', SiteSchemas::reviewTitle('Top!'));
        $this->assertSame('Review', SiteSchemas::reviewTitle(''));
    }

    public function test_long_review_title_is_cut_on_a_word(): void
    {
        $title = SiteSchemas::reviewTitle('De vaas is prachtig geworden en paste precies in onze woonkamer naast de bank');

        $this->assertSame('De vaas is prachtig geworden en paste precies in onze...', $title);
        $this->assertLessThanOrEqual(60, mb_strlen($title));
    }
}
