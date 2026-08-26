<?php

namespace Tests\Unit;

use Caesargustav\StaticPrerenderer\Imaging\RevalidatingGlideResponseFactory;
use Caesargustav\StaticPrerenderer\Query\TrackingEntryQueryBuilder;
use Caesargustav\StaticPrerenderer\ServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use League\Glide\Server;
use PHPUnit\Framework\TestCase;
use Statamic\Contracts\Entries\QueryBuilder;
use Statamic\Events\EntryCreated;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Stache\Query\EntryQueryBuilder;
use Statamic\Stache\ServiceProvider as StacheServiceProvider;
use Statamic\Stache\Stache;
use Statamic\Stache\Stores\Store;

class ServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_glide_server_is_extended_before_statamics_deferred_binding_resolves(): void
    {
        $container = new Application(dirname(__DIR__, 2));
        $container->instance('config', new Repository([
            'statamic-static-prerenderer' => ['revalidate_glide_responses' => true],
        ]));
        $container->instance('request', Request::create('/img/test.jpg'));
        Container::setInstance($container);

        (new ServiceProvider($container))->register();

        $server = $this->createMock(Server::class);
        $server->expects($this->once())
            ->method('setResponseFactory')
            ->with($this->isInstanceOf(RevalidatingGlideResponseFactory::class));
        $container->singleton(Server::class, fn () => $server);

        $this->assertSame($server, $container->make(Server::class));
    }

    public function test_entries_are_processed_once_on_save_and_deletions_are_processed(): void
    {
        $provider = new ServiceProvider(new Container);
        $property = new \ReflectionProperty($provider, 'listen');
        $listeners = $property->getValue($provider);

        $this->assertArrayHasKey(EntrySaved::class, $listeners);
        $this->assertArrayHasKey(EntryDeleted::class, $listeners);
        $this->assertArrayNotHasKey(EntryCreated::class, $listeners);
    }

    public function test_tracking_binding_wins_when_statamic_registers_after_the_addon(): void
    {
        $container = new Application(dirname(__DIR__, 2));
        $stache = $this->createMock(Stache::class);
        $stache->method('store')->with('entries')->willReturn($this->createMock(Store::class));
        Container::setInstance($container);

        (new ServiceProvider($container))->register();
        (new StacheServiceProvider($container))->register();
        $container->bind(QueryBuilder::class, EntryQueryBuilder::class);
        $container->instance(Stache::class, $stache);

        $this->assertInstanceOf(EntryQueryBuilder::class, $container->make(EntryQueryBuilder::class));
        $this->assertNotInstanceOf(TrackingEntryQueryBuilder::class, $container->make(EntryQueryBuilder::class));

        $container->boot();

        $this->assertInstanceOf(TrackingEntryQueryBuilder::class, $container->make(EntryQueryBuilder::class));
        $this->assertInstanceOf(TrackingEntryQueryBuilder::class, $container->make(QueryBuilder::class));
    }
}
