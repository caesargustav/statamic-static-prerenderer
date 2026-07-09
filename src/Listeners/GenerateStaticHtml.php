<?php

namespace Caesargustav\StaticPrerenderer\Listeners;

use Caesargustav\StaticPrerenderer\PrerenderedEntity;
use Statamic\Entries\Entry;
use Statamic\Events\EntryCreated;
use Statamic\Events\EntrySaved;
use Statamic\Exceptions\NotFoundHttpException;
use Statamic\Facades\Entry as EntryFacade;

class GenerateStaticHtml
{
    public function handle(EntryCreated|EntrySaved $event): void
    {
        $entry = $event->entry;

        if (! $this->shouldPrerender($entry)) {
            return;
        }

        // Generate static HTML of saved page
        $this->prerender($entry);

        // Regenerate static HTML of related pages
        $id = $entry->id();

        EntryFacade::query()
            ->where('id', '!=', $id)
            ->get()
            ->filter(fn (Entry $entry) => $this->shouldPrerender($entry) && str_contains(json_encode($entry->data()), $id))
            ->each(fn (Entry $entry) => $this->prerender($entry));
    }

    private function shouldPrerender(Entry $entry): bool
    {
        return $entry->published()
            && ! $entry->private()
            && (bool) $entry->uri();
    }

    private function prerender(Entry $entry): void
    {
        try {
            PrerenderedEntity::create($entry)->prerender();
        } catch (NotFoundHttpException) {
            // The entry may become unroutable between lookup and render.
        }
    }
}
