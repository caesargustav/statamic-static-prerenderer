<?php

namespace Tests\Unit;

use Caesargustav\StaticPrerenderer\Dependencies\PrerenderContext;
use Caesargustav\StaticPrerenderer\Query\TrackingEntryQueryBuilder;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;
use Statamic\Contracts\Entries\CollectionRepository;
use Statamic\Stache\Stores\AggregateStore;
use Statamic\Stache\Stores\ChildStore;
use Statamic\Stache\Stores\Store;

class TrackingEntryQueryBuilderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $container->instance('config', new Repository);
        $collections = $this->createMock(CollectionRepository::class);
        $collections->method('handles')->willReturn(collect(['pages', 'lexicon']));
        $container->instance(CollectionRepository::class, $collections);
        Container::setInstance($container);
        Facade::setFacadeApplication($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);

        parent::tearDown();
    }

    public function test_collection_queries_are_automatically_recorded_only_during_prerendering(): void
    {
        $context = new PrerenderContext;
        $builder = new TrackingEntryQueryBuilder($this->emptyStore(), $context);

        $builder->where('collection', 'outside')->count();

        $context->begin('overview');
        (new TrackingEntryQueryBuilder($this->emptyStore(), $context))
            ->whereIn('collection', ['lexicon', 'news', 'events'])
            ->count();

        $this->assertSame(['lexicon', 'news', 'events'], $context->finish());
    }

    public function test_a_query_that_is_only_built_is_not_recorded(): void
    {
        $context = new PrerenderContext;
        $context->begin('overview');

        (new TrackingEntryQueryBuilder($this->emptyStore(), $context))
            ->where('collection', 'lexicon');

        $this->assertSame([], $context->finish());
    }

    public function test_an_unscoped_query_records_all_queried_collections(): void
    {
        $context = new PrerenderContext;
        $context->begin('overview');

        (new TrackingEntryQueryBuilder($this->emptyStore(), $context))->count();

        $this->assertSame(['pages', 'lexicon'], $context->finish());
    }

    private function emptyStore(): Store
    {
        $child = $this->createMock(ChildStore::class);
        $child->method('paths')->willReturn(collect());

        $store = $this->getMockBuilder(AggregateStore::class)->getMock();
        $store->method('store')->willReturn($child);
        $store->method('getItems')->willReturn(collect());

        return $store;
    }
}
