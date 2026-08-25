<?php

namespace Tests\Unit;

use Caesargustav\StaticPrerenderer\Cache\PrerenderCache;
use Caesargustav\StaticPrerenderer\Dependencies\CollectionDependencyStore;
use Caesargustav\StaticPrerenderer\Dependencies\PrerenderContext;
use Caesargustav\StaticPrerenderer\PrerenderedEntity;
use Caesargustav\StaticPrerenderer\Query\TrackingEntryQueryBuilder;
use Caesargustav\StaticPrerenderer\Services\TailwindCSS;
use Carbon\Carbon;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\TestCase;
use Statamic\Entries\Entry;
use Statamic\Stache\Stores\AggregateStore;
use Statamic\Stache\Stores\ChildStore;
use Statamic\Stache\Stores\Store;

class PrerenderedEntityDependencyTest extends TestCase
{
    private Application $app;

    private string $storagePath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->storagePath = sys_get_temp_dir().'/static-prerenderer-test-'.bin2hex(random_bytes(8));
        $this->app = new Application(dirname(__DIR__, 2));
        $this->app->instance('config', new Repository([
            'filesystems' => [
                'default' => 'local',
                'disks' => [
                    'local' => [
                        'driver' => 'local',
                        'root' => $this->storagePath,
                    ],
                ],
            ],
        ]));
        $this->app->instance('request', Request::create('/'));
        $this->app->instance('files', new Filesystem);

        $manager = new FilesystemManager($this->app);
        $this->app->instance('filesystem', $manager);
        $this->app->instance(Factory::class, $manager);

        Facade::setFacadeApplication($this->app);
    }

    protected function tearDown(): void
    {
        (new Filesystem)->deleteDirectory($this->storagePath);
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        Container::setInstance(null);

        parent::tearDown();
    }

    public function test_a_collection_query_is_recorded_and_persisted_after_rendering(): void
    {
        $context = new PrerenderContext;
        $dependencies = $this->createMock(CollectionDependencyStore::class);
        $dependencies->method('has')->with('overview')->willReturn(false);
        $dependencies->expects($this->once())->method('replace')->with('overview', ['lexicon']);
        $this->app->instance(PrerenderContext::class, $context);
        $this->app->instance(CollectionDependencyStore::class, $dependencies);
        $this->mockTailwind();

        $entry = $this->entry(function () use ($context) {
            (new TrackingEntryQueryBuilder($this->emptyStore(), $context))
                ->where('collection', 'lexicon')
                ->count();

            return new Response('<main>Lexicon</main>');
        });

        $html = PrerenderedEntity::create($entry)->prerender();

        $this->assertSame('<style>.generated{}</style><main>Lexicon</main>', $html);
        $this->assertNull($context->entityId());
    }

    public function test_a_legacy_cache_entry_forgets_all_variants_before_rebuilding(): void
    {
        $context = new PrerenderContext;
        $dependencies = $this->createMock(CollectionDependencyStore::class);
        $dependencies->method('has')->with('overview')->willReturn(false);
        $dependencies->expects($this->once())->method('replace')->with('overview', []);
        $this->app->instance(PrerenderContext::class, $context);
        $this->app->instance(CollectionDependencyStore::class, $dependencies);
        $this->app->instance(PrerenderCache::class, new PrerenderCache($this->app->make(Factory::class)));
        $this->mockTailwind();

        $defaultPath = PrerenderCache::DIRECTORY.'/overview'.md5(json_encode([])).'.html';
        $variantPath = PrerenderCache::DIRECTORY.'/overview'.md5(json_encode(['page' => 2])).'.html';
        Storage::put($defaultPath, 'old default');
        Storage::put($variantPath, 'old variant');

        PrerenderedEntity::create($this->entry(fn () => new Response('new')))->prerender();

        $this->assertTrue(Storage::exists($defaultPath));
        $this->assertFalse(Storage::exists($variantPath));
    }

    private function entry(callable $response): Entry
    {
        $template = 'custom';
        $entry = $this->getMockBuilder(Entry::class)
            ->onlyMethods(['id', 'lastModified', 'layout', 'template', 'toResponse'])
            ->getMock();
        $entry->method('id')->willReturn('overview');
        $entry->method('lastModified')->willReturn(Carbon::create(2020));
        $entry->method('layout')->willReturnSelf();
        $entry->method('template')->willReturnCallback(function (...$arguments) use (&$template, $entry) {
            if ($arguments === []) {
                return $template;
            }

            $template = $arguments[0];

            return $entry;
        });
        $entry->method('toResponse')->willReturnCallback($response);

        return $entry;
    }

    private function mockTailwind(): void
    {
        $tailwind = $this->createMock(TailwindCSS::class);
        $tailwind->method('process')->willReturn('.generated{}');
        $this->app->instance(TailwindCSS::class, $tailwind);
    }

    private function emptyStore(): Store
    {
        $child = $this->createMock(ChildStore::class);
        $child->method('paths')->willReturn(collect());
        $store = $this->getMockBuilder(AggregateStore::class)->getMock();
        $store->method('store')->willReturn($child);

        return $store;
    }
}
