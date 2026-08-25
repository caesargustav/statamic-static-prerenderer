<?php

namespace Caesargustav\StaticPrerenderer\Cache;

use Illuminate\Contracts\Filesystem\Factory;

class PrerenderCache
{
    public const DIRECTORY = 'public/statamic-static-prerenderer';

    public function __construct(private readonly Factory $storage) {}

    public function clear(): void
    {
        $this->storage->disk()->deleteDirectory(self::DIRECTORY);
    }

    public function forget(string $entityId): void
    {
        $disk = $this->storage->disk();
        $paths = array_values(array_filter(
            $disk->files(self::DIRECTORY),
            fn (string $path) => $this->matches($path, $entityId, 'html|css'),
        ));

        if ($paths !== []) {
            $disk->delete($paths);
        }
    }

    public function cachedEntityIds(iterable $entityIds): array
    {
        $paths = $this->storage->disk()->files(self::DIRECTORY);

        return collect($entityIds)
            ->filter(fn (string $entityId) => collect($paths)
                ->contains(fn (string $path) => $this->matches($path, $entityId, 'html')))
            ->values()
            ->all();
    }

    private function matches(string $path, string $entityId, string $extensions): bool
    {
        $prefix = preg_quote(self::DIRECTORY.'/'.$entityId, '~');

        return preg_match("~^{$prefix}[a-f0-9]{32}\\.(?:{$extensions})$~", $path) === 1;
    }
}
