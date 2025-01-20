<?php

namespace Caesargustav\StaticPrerenderer\Listeners;

use Caesargustav\StaticPrerenderer\PrerenderedEntity;
use Statamic\Events\EntryCreated;
use Statamic\Events\EntrySaved;

class GenerateStaticHtml
{
    public function handle(EntryCreated|EntrySaved $event): void
    {
        PrerenderedEntity::create($event->entry)->prerender();
    }
}
