<?php

namespace Caesargustav\StaticPrerenderer\Listeners;

use Caesargustav\StaticPrerenderer\Cache\PrerenderCache;
use Caesargustav\StaticPrerenderer\Dependencies\CollectionDependencyStore;
use Caesargustav\StaticPrerenderer\Services\EntityPrerenderer;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Entries\EntryRepository;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Contracts\Taxonomies\TermRepository;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;

class GenerateStaticHtml
{
    public function __construct(
        private readonly PrerenderCache $cache,
        private readonly CollectionDependencyStore $dependencies,
        private readonly EntryRepository $entries,
        private readonly TermRepository $terms,
        private readonly EntityPrerenderer $prerenderer,
    ) {}

    public function handle(EntrySaved|EntryDeleted $event): void
    {
        $changed = $event->entry;
        $changedId = $changed->id();
        $allEntries = $this->entries->query()->get();
        $entriesById = $allEntries->keyBy(fn (Entry $entry) => $entry->id());

        $targetIds = collect($this->dependencies->dependentEntityIds($changed->collectionHandle()))
            ->merge($allEntries
                ->reject(fn (Entry $entry) => $entry->id() === $changedId)
                ->filter(fn (Entry $entry) => str_contains((string) json_encode($entry->data()), $changedId))
                ->map(fn (Entry $entry) => $entry->id()))
            ->merge($allEntries
                ->filter(fn (Entry $entry) => $this->shouldPrerender($entry)
                    && $this->cache->has($entry->id())
                    && ! $this->dependencies->has($entry->id()))
                ->map(fn (Entry $entry) => $entry->id()))
            ->push($changedId)
            ->unique()
            ->values();

        if ($event instanceof EntrySaved) {
            $entriesById->put($changedId, $changed);
        }

        foreach ($targetIds as $entityId) {
            $this->cache->forget($entityId);

            $entity = $entriesById->get($entityId) ?? $this->terms->find($entityId);
            if (! $entity || ! $this->shouldPrerender($entity)) {
                $this->dependencies->forget($entityId);

                continue;
            }

            $this->prerenderer->render($entity);
        }
    }

    private function shouldPrerender(Entry|Term $entity): bool
    {
        return (! ($entity instanceof Entry) || $entity->published())
            && ! $entity->private()
            && (bool) $entity->uri();
    }
}
