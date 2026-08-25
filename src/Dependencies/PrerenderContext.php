<?php

namespace Caesargustav\StaticPrerenderer\Dependencies;

class PrerenderContext
{
    private array $frames = [];

    public function begin(string $entityId): void
    {
        $this->frames[] = [
            'entity_id' => $entityId,
            'collections' => [],
        ];
    }

    public function record(string $collection): void
    {
        if ($this->frames === []) {
            return;
        }

        $index = array_key_last($this->frames);
        $this->frames[$index]['collections']['collection:'.$collection] = $collection;
    }

    public function finish(): array
    {
        $frame = array_pop($this->frames);

        return $frame ? array_values($frame['collections']) : [];
    }

    public function discard(): void
    {
        array_pop($this->frames);
    }

    public function entityId(): ?string
    {
        $frame = end($this->frames);

        return $frame['entity_id'] ?? null;
    }
}
