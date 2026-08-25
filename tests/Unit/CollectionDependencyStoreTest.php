<?php

namespace Tests\Unit;

use Caesargustav\StaticPrerenderer\Dependencies\CollectionDependencyStore;
use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;

class CollectionDependencyStoreTest extends TestCase
{
    public function test_replacing_dependencies_removes_old_reverse_edges(): void
    {
        $files = [];
        $store = new CollectionDependencyStore($this->memoryStorage($files));

        $store->replace('overview', ['lexicon', 'news', 'lexicon']);

        $this->assertSame(['overview'], $store->dependentEntityIds('lexicon'));
        $this->assertSame(['overview'], $store->dependentEntityIds('news'));

        $store->replace('overview', ['news']);

        $this->assertSame([], $store->dependentEntityIds('lexicon'));
        $this->assertSame(['overview'], $store->dependentEntityIds('news'));
        $this->assertTrue($store->has('overview'));
    }

    public function test_an_empty_dependency_set_still_marks_the_entity_as_tracked(): void
    {
        $files = [];
        $store = new CollectionDependencyStore($this->memoryStorage($files));

        $store->replace('plain-page', []);

        $this->assertTrue($store->has('plain-page'));
    }

    public function test_forgetting_an_entity_removes_its_reverse_edges(): void
    {
        $files = [];
        $store = new CollectionDependencyStore($this->memoryStorage($files));
        $store->replace('overview', ['lexicon']);

        $store->forget('overview');

        $this->assertFalse($store->has('overview'));
        $this->assertSame([], $store->dependentEntityIds('lexicon'));
    }

    private function memoryStorage(array &$files): Factory
    {
        $disk = $this->createMock(Filesystem::class);
        $disk->method('exists')->willReturnCallback(function (string $path) use (&$files) {
            return array_key_exists($path, $files);
        });
        $disk->method('get')->willReturnCallback(function (string $path) use (&$files) {
            return $files[$path] ?? null;
        });
        $disk->method('put')->willReturnCallback(function (string $path, string $contents) use (&$files) {
            $files[$path] = $contents;

            return true;
        });
        $disk->method('delete')->willReturnCallback(function (string|array $paths) use (&$files) {
            foreach ((array) $paths as $path) {
                unset($files[$path]);
            }

            return true;
        });

        $storage = $this->createMock(Factory::class);
        $storage->method('disk')->willReturn($disk);

        return $storage;
    }
}
