<?php

namespace Caesargustav\StaticPrerenderer\Cache;

use Illuminate\Contracts\Filesystem\Factory;

class PrerenderCache
{
    public const DIRECTORY = 'public/statamic-static-prerenderer';

    public function __construct(private readonly Factory $storage) {}

    public function clear(): void
    {
        $this->storage->disk()->deleteDirectory(self::DIRECTORY);
    }
}
