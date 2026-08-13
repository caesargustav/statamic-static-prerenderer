<?php

namespace Caesargustav\StaticPrerenderer\Listeners;

use Caesargustav\StaticPrerenderer\Cache\PrerenderCache;
use Statamic\Events\AssetDeleted;
use Statamic\Events\AssetReuploaded;
use Statamic\Events\AssetSaved;
use Statamic\Imaging\GlideManager;

class InvalidatePrerenderCache
{
    public function __construct(
        private readonly PrerenderCache $cache,
        private readonly GlideManager $glide,
    ) {}

    public function handle(AssetSaved|AssetReuploaded|AssetDeleted $event): void
    {
        if (config('statamic-static-prerenderer.invalidate_on_asset_change', true)) {
            $this->glide->clearAsset($event->asset);
            $this->cache->clear();
        }
    }
}
