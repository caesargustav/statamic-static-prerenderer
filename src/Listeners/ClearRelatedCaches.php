<?php

namespace Caesargustav\StaticPrerenderer\Listeners;

use Caesargustav\StaticPrerenderer\PrerenderedEntity;
use Statamic\Entries\Entry;
use Statamic\Events\EntrySaved;
use Statamic\Facades\Entry as EntryFacade;

class ClearRelatedCaches
{
    public function handle(EntrySaved $event): void
    {
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
            ->each(function ($entry) {
                $prerenderedEntity = PrerenderedEntity::create($entry);
                $prerenderedEntity->clearCache();
            });
    }
}
