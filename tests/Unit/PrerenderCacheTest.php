<?php

namespace Tests\Unit;

use Caesargustav\StaticPrerenderer\Cache\PrerenderCache;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class PrerenderCacheTest extends TestCase
{
    public function test_it_deletes_the_complete_prerender_cache_directory(): void
    {
        $disk = $this->createMock(Filesystem::class);
        $disk->expects($this->once())
            ->method('deleteDirectory')
            ->with(PrerenderCache::DIRECTORY)
            ->willReturn(true);

        $storage = $this->createMock(Factory::class);
        $storage->expects($this->once())
            ->method('disk')
            ->willReturn($disk);

        (new PrerenderCache($storage))->clear();
    }

    public function test_it_deletes_all_query_variants_for_only_one_entity(): void
    {
        $firstHash = str_repeat('a', 32);
        $secondHash = str_repeat('b', 32);
        $disk = $this->createMock(Filesystem::class);
        $disk->expects($this->once())
            ->method('files')
            ->with(PrerenderCache::DIRECTORY)
            ->willReturn([
                PrerenderCache::DIRECTORY.'/page-1'.$firstHash.'.html',
                PrerenderCache::DIRECTORY.'/page-1'.$firstHash.'.css',
                PrerenderCache::DIRECTORY.'/page-1'.$secondHash.'.html',
                PrerenderCache::DIRECTORY.'/page-10'.$firstHash.'.html',
                PrerenderCache::DIRECTORY.'/page-2'.$firstHash.'.html',
                PrerenderCache::DIRECTORY.'/page-1-not-cache.txt',
            ]);
        $disk->expects($this->once())
            ->method('delete')
            ->with([
                PrerenderCache::DIRECTORY.'/page-1'.$firstHash.'.html',
                PrerenderCache::DIRECTORY.'/page-1'.$firstHash.'.css',
                PrerenderCache::DIRECTORY.'/page-1'.$secondHash.'.html',
            ])
            ->willReturn(true);

        $storage = $this->createMock(Factory::class);
        $storage->expects($this->once())->method('disk')->willReturn($disk);

        (new PrerenderCache($storage))->forget('page-1');
    }

    public function test_it_detects_an_existing_html_variant_for_an_exact_entity_id(): void
    {
        $hash = str_repeat('a', 32);
        $disk = $this->createMock(Filesystem::class);
        $disk->method('files')->willReturn([
            PrerenderCache::DIRECTORY.'/page-10'.$hash.'.html',
            PrerenderCache::DIRECTORY.'/page-1'.$hash.'.html',
        ]);
        $storage = $this->createMock(Factory::class);
        $storage->method('disk')->willReturn($disk);

        $cache = new PrerenderCache($storage);

        $this->assertTrue($cache->has('page-1'));
        $this->assertFalse($cache->has('page-2'));
    }
}
