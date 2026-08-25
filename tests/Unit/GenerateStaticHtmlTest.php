<?php

namespace Tests\Unit;

use Caesargustav\StaticPrerenderer\Cache\PrerenderCache;
use Caesargustav\StaticPrerenderer\Dependencies\CollectionDependencyStore;
use Caesargustav\StaticPrerenderer\Listeners\GenerateStaticHtml;
use Caesargustav\StaticPrerenderer\Services\EntityPrerenderer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Statamic\Contracts\Entries\EntryRepository;
use Statamic\Contracts\Taxonomies\TermRepository;
use Statamic\Entries\Entry;
use Statamic\Events\EntryDeleted;
use Statamic\Events\EntrySaved;
use Statamic\Stache\Query\EntryQueryBuilder;
use Statamic\Taxonomies\LocalizedTerm;

class GenerateStaticHtmlTest extends TestCase
{
    public function test_saved_entry_and_collection_and_direct_dependents_are_rendered_once(): void
    {
        $changed = $this->entry('changed', 'lexicon');
        $overview = $this->entry('overview', 'pages', ['related' => 'changed']);
        $unrelated = $this->entry('unrelated', 'pages');

        [$listener, $forgotten, $rendered] = $this->listener(
            [$changed, $overview, $unrelated],
            ['overview', 'changed', 'overview'],
        );

        $listener->handle($this->event(EntrySaved::class, $changed));

        $this->assertEqualsCanonicalizing(['changed', 'overview'], $forgotten());
        $this->assertEqualsCanonicalizing(['changed', 'overview'], $rendered());
        $this->assertNotContains('unrelated', $forgotten());
    }

    #[DataProvider('removedEntryEvents')]
    public function test_depublished_and_deleted_entries_refresh_dependents_without_rendering_the_entry(string $eventClass): void
    {
        $changed = $this->entry('changed', 'lexicon', [], $eventClass !== EntrySaved::class);
        $overview = $this->entry('overview', 'pages');

        $currentEntries = $eventClass === EntryDeleted::class ? [$overview] : [$changed, $overview];
        [$listener, $forgotten, $rendered, $dependencies] = $this->listener($currentEntries, ['overview']);

        $listener->handle($this->event($eventClass, $changed));

        $this->assertEqualsCanonicalizing(['changed', 'overview'], $forgotten());
        $this->assertSame(['overview'], $rendered());
        $dependencies->expects($this->never())->method('replace');
    }

    public static function removedEntryEvents(): array
    {
        return [
            'depublished' => [EntrySaved::class],
            'deleted' => [EntryDeleted::class],
        ];
    }

    public function test_a_legacy_cached_page_without_dependency_data_is_rendered_once(): void
    {
        $changed = $this->entry('changed', 'lexicon');
        $overview = $this->entry('overview', 'pages');
        $unrelated = $this->entry('unrelated', 'pages');

        [$listener, $forgotten, $rendered] = $this->listener(
            [$changed, $overview, $unrelated],
            [],
            ['overview'],
        );

        $listener->handle($this->event(EntrySaved::class, $changed));

        $this->assertEqualsCanonicalizing(['changed', 'overview'], $forgotten());
        $this->assertEqualsCanonicalizing(['changed', 'overview'], $rendered());
    }

    public function test_a_direct_id_dependency_still_refreshes_without_a_collection_edge(): void
    {
        $changed = $this->entry('changed', 'lexicon');
        $detail = $this->entry('detail', 'pages', ['selected_entry' => 'changed']);

        [$listener, $forgotten, $rendered] = $this->listener([$changed, $detail], []);

        $listener->handle($this->event(EntrySaved::class, $changed));

        $this->assertEqualsCanonicalizing(['changed', 'detail'], $forgotten());
        $this->assertEqualsCanonicalizing(['changed', 'detail'], $rendered());
    }

    public function test_a_dependent_term_page_is_also_rendered(): void
    {
        $changed = $this->entry('changed', 'lexicon');
        $term = $this->term('topics::health');

        [$listener, $forgotten, $rendered] = $this->listener(
            [$changed],
            ['topics::health'],
            [],
            ['topics::health' => $term],
        );

        $listener->handle($this->event(EntrySaved::class, $changed));

        $this->assertEqualsCanonicalizing(['changed', 'topics::health'], $forgotten());
        $this->assertEqualsCanonicalizing(['changed', 'topics::health'], $rendered());
    }

    private function listener(array $entries, array $dependents, array $legacyCached = [], array $termsById = []): array
    {
        $forgottenIds = [];
        $renderedIds = [];

        $cache = $this->createMock(PrerenderCache::class);
        $cache->method('has')->willReturnCallback(fn (string $id) => in_array($id, $legacyCached, true));
        $cache->method('forget')->willReturnCallback(function (string $id) use (&$forgottenIds) {
            $forgottenIds[] = $id;
        });

        $dependencies = $this->createMock(CollectionDependencyStore::class);
        $dependencies->method('dependentEntityIds')->willReturn($dependents);

        $query = $this->getMockBuilder(EntryQueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['get'])
            ->getMock();
        $query->method('get')->willReturn(collect($entries));

        $repository = $this->createMock(EntryRepository::class);
        $repository->method('query')->willReturn($query);

        $terms = $this->createMock(TermRepository::class);
        $terms->method('find')->willReturnCallback(fn (string $id) => $termsById[$id] ?? null);

        $prerenderer = $this->createMock(EntityPrerenderer::class);
        $prerenderer->method('render')->willReturnCallback(function ($entity) use (&$renderedIds) {
            $renderedIds[] = $entity->id();
        });

        return [
            new GenerateStaticHtml($cache, $dependencies, $repository, $terms, $prerenderer),
            function () use (&$forgottenIds) {
                return $forgottenIds;
            },
            function () use (&$renderedIds) {
                return $renderedIds;
            },
            $dependencies,
        ];
    }

    private function entry(string $id, string $collection, array $data = [], bool $published = true): Entry
    {
        $entry = $this->getMockBuilder(Entry::class)
            ->onlyMethods(['id', 'collectionHandle', 'data', 'published', 'private', 'uri'])
            ->getMock();
        $entry->method('id')->willReturn($id);
        $entry->method('collectionHandle')->willReturn($collection);
        $entry->method('data')->willReturn(collect($data));
        $entry->method('published')->willReturn($published);
        $entry->method('private')->willReturn(false);
        $entry->method('uri')->willReturn('/'.$id);

        return $entry;
    }

    private function event(string $eventClass, Entry $entry): EntrySaved|EntryDeleted
    {
        $event = $this->getMockBuilder($eventClass)->disableOriginalConstructor()->getMock();
        $event->entry = $entry;

        return $event;
    }

    private function term(string $id): LocalizedTerm
    {
        $term = $this->getMockBuilder(LocalizedTerm::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['id', 'private', 'uri'])
            ->getMock();
        $term->method('id')->willReturn($id);
        $term->method('private')->willReturn(false);
        $term->method('uri')->willReturn('/topics/health');

        return $term;
    }
}
