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
        $collectionHandle = $event->entry->collectionHandle();

        EntryFacade::query()
            ->get()
            ->filter(function (Entry $entry) use ($id, $collectionHandle) {
                $jsonData = json_encode($entry->data());

                $idIsInJson = str_contains($jsonData, $id);
                $collectionHandleIsInJson = str_contains($jsonData, $collectionHandle);

                return $idIsInJson || ($entry->collectionHandle() !== $collectionHandle && $collectionHandleIsInJson);
            })
            ->each(fn ($entry) => PrerenderedEntity::create($entry)->prerender());
    }
}
