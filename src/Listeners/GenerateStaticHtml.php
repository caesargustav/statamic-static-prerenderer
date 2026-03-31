<?php

namespace Caesargustav\StaticPrerenderer\Listeners;

use Caesargustav\StaticPrerenderer\PrerenderedEntity;
use Statamic\Entries\Entry;
use Statamic\Events\EntryCreated;
use Statamic\Events\EntrySaved;
use Statamic\Facades\Entry as EntryFacade;

class GenerateStaticHtml
{
    public function handle(EntryCreated|EntrySaved $event): void
    {
        // Generate static HTML of saved page
        PrerenderedEntity::create($event->entry)->prerender();

        // Regenerate static HTML of related pages
        $id = $event->entry->id();

        EntryFacade::query()
            ->where('id', '!=', $id)
            ->get()
            ->filter(function (Entry $entry) use ($id) {
                return str_contains(json_encode($entry->data()), $id);
            })
            ->each(fn ($entry) => PrerenderedEntity::create($entry)->prerender());
    }
}
