<?php

namespace Caesargustav\MauveConnector\Listeners;

use Caesargustav\MauveConnector\PrerenderedEntry;
use Statamic\Events\EntryCreated;
use Statamic\Events\EntrySaved;

class GenerateStaticHtml
{
    public function handle(EntryCreated|EntrySaved $event): void
    {
        PrerenderedEntry::create($event->entry)->prerender();
    }
}
