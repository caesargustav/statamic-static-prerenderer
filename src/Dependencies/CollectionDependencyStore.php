<?php

namespace Caesargustav\StaticPrerenderer\Dependencies;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Contracts\Filesystem\Filesystem;

class CollectionDependencyStore
{
    public const DIRECTORY = 'statamic-static-prerenderer/dependencies';

    private readonly DependencyLock $lock;

    public function __construct(private readonly Factory $storage, ?DependencyLock $lock = null)
    {
        $this->lock = $lock ?? new DependencyLock;
    }

    public function replace(string $entityId, array $collections): void
    {
        $this->lock->synchronized(function () use ($entityId, $collections) {
            $collections = array_values(array_unique(array_filter(
                $collections,
                fn ($collection) => is_string($collection) && $collection !== '',
            )));
            $previous = $this->collectionsFor($entityId);

            foreach (array_diff($previous, $collections) as $collection) {
                $this->removeDependent($collection, $entityId);
            }

            foreach (array_diff($collections, $previous) as $collection) {
                $this->addDependent($collection, $entityId);
            }

            $this->write($this->entityPath($entityId), $collections);
        });
    }

    public function dependentEntityIds(string $collection): array
    {
        return $this->read($this->collectionPath($collection));
    }

    public function has(string $entityId): bool
    {
        return $this->disk()->exists($this->entityPath($entityId));
    }

    public function forget(string $entityId): void
    {
        $this->lock->synchronized(function () use ($entityId) {
            foreach ($this->collectionsFor($entityId) as $collection) {
                $this->removeDependent($collection, $entityId);
            }

            $this->disk()->delete($this->entityPath($entityId));
        });
    }

    private function collectionsFor(string $entityId): array
    {
        return $this->read($this->entityPath($entityId));
    }

    private function addDependent(string $collection, string $entityId): void
    {
        $dependents = $this->dependentEntityIds($collection);
        $dependents[] = $entityId;

        $this->write($this->collectionPath($collection), array_values(array_unique($dependents)));
    }

    private function removeDependent(string $collection, string $entityId): void
    {
        $dependents = array_values(array_diff($this->dependentEntityIds($collection), [$entityId]));
        $path = $this->collectionPath($collection);

        if ($dependents === []) {
            $this->disk()->delete($path);

            return;
        }

        $this->write($path, $dependents);
    }

    private function read(string $path): array
    {
        if (! $this->disk()->exists($path)) {
            return [];
        }

        $data = json_decode((string) $this->disk()->get($path), true);

        return is_array($data) ? array_values(array_filter($data, 'is_string')) : [];
    }

    private function write(string $path, array $values): void
    {
        $this->disk()->put($path, json_encode(array_values($values), JSON_THROW_ON_ERROR));
    }

    private function entityPath(string $entityId): string
    {
        return self::DIRECTORY.'/entities/'.rawurlencode($entityId).'.json';
    }

    private function collectionPath(string $collection): string
    {
        return self::DIRECTORY.'/collections/'.rawurlencode($collection).'.json';
    }

    private function disk(): Filesystem
    {
        return $this->storage->disk();
    }
}
