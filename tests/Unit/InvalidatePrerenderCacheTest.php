<?php

namespace Tests\Unit;

use Caesargustav\StaticPrerenderer\Cache\PrerenderCache;
use Caesargustav\StaticPrerenderer\Listeners\InvalidatePrerenderCache;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Statamic\Events\AssetDeleted;
use Statamic\Events\AssetReuploaded;
use Statamic\Events\AssetSaved;
use Statamic\Imaging\GlideManager;

class InvalidatePrerenderCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $container = new Container;
        $container->instance('config', new Repository([
            'statamic-static-prerenderer' => ['invalidate_on_asset_change' => true],
        ]));
        Container::setInstance($container);
    }

    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    #[DataProvider('assetEvents')]
    public function test_asset_changes_invalidate_prerendered_html(object $event): void
    {
        $cache = $this->createMock(PrerenderCache::class);
        $cache->expects($this->once())->method('clear');
        $glide = $this->createMock(GlideManager::class);
        $glide->expects($this->once())
            ->method('clearAsset')
            ->with($event->asset);

        (new InvalidatePrerenderCache($cache, $glide))->handle($event);
    }

    public static function assetEvents(): array
    {
        $asset = new \stdClass;

        return [
            'saved metadata or focal point' => [new AssetSaved($asset)],
            'cropped or reuploaded source' => [new AssetReuploaded($asset, 'image.jpg')],
            'deleted source' => [new AssetDeleted($asset)],
        ];
    }

    public function test_asset_cache_invalidation_can_be_disabled(): void
    {
        config()->set('statamic-static-prerenderer.invalidate_on_asset_change', false);

        $cache = $this->createMock(PrerenderCache::class);
        $cache->expects($this->never())->method('clear');
        $glide = $this->createMock(GlideManager::class);
        $glide->expects($this->never())->method('clearAsset');

        (new InvalidatePrerenderCache($cache, $glide))->handle(
            new AssetSaved(new \stdClass),
        );
    }
}
