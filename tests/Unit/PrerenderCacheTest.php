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
}
