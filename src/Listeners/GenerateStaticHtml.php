<?php

namespace Caesargustav\StaticPrerenderer\Listeners;

use Caesargustav\StaticPrerenderer\PrerenderedEntry;
use Statamic\Events\EntryCreated;
use Statamic\Events\EntrySaved;

class GenerateStaticHtml
{
    public function handle(EntryCreated|EntrySaved $event): void
    {
        PrerenderedEntry::create($event->entry)->prerender();
    }
}
