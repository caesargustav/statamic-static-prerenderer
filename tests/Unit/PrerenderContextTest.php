<?php

namespace Tests\Unit;

use Caesargustav\StaticPrerenderer\Dependencies\PrerenderContext;
use PHPUnit\Framework\TestCase;

class PrerenderContextTest extends TestCase
{
    public function test_it_only_records_collections_during_an_active_prerender(): void
    {
        $context = new PrerenderContext;

        $context->record('ignored');
        $context->begin('overview');
        $context->record('lexicon');
        $context->record('lexicon');
        $context->record('news');

        $this->assertSame(['lexicon', 'news'], $context->finish());

        $context->record('also-ignored');
        $context->begin('second-page');
        $this->assertSame([], $context->finish());
    }

    public function test_nested_renders_keep_their_dependency_sets_separate(): void
    {
        $context = new PrerenderContext;
        $context->begin('outer');
        $context->record('pages');
        $context->begin('inner');
        $context->record('lexicon');

        $this->assertSame(['lexicon'], $context->finish());
        $this->assertSame('outer', $context->entityId());

        $context->discard();

        $this->assertNull($context->entityId());
    }

    public function test_numeric_collection_handles_remain_strings(): void
    {
        $context = new PrerenderContext;
        $context->begin('archive');
        $context->record('2024');

        $this->assertSame(['2024'], $context->finish());
    }
}
