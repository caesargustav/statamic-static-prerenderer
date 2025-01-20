<?php

namespace Caesargustav\StaticPrerenderer;

use Caesargustav\StaticPrerenderer\Services\TailwindCSS;
use Illuminate\Support\Facades\Storage;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;
use Statamic\View\View;

class PrerenderedEntity
{
    public function __construct(private readonly EntryContract|TermContract $entity)
    {
    }

    public static function create(EntryContract|TermContract $entry): PrerenderedEntity
    {
        return new static($entry);
    }

    public function id(): string
    {
        return $this->entity->id();
    }

    public function data(): array
    {
        return $this->entity->data()->toArray();
    }

    public function html(): string
    {
        return $this->prerender();
    }

    public function prerender(): string
    {
        $path = 'public/statamic-static-prerenderer/' . $this->entity->id() . '.html';
        $cssPath = 'public/statamic-static-prerenderer/' . $this->entity->id() . '.css';
        $lastModified = $this->entity->lastModified();

        if (Storage::exists($path) && Storage::lastModified($path) >= $lastModified->timestamp) {
            return Storage::get($path);
        }

        $this->entity->layout('statamic-static-prerenderer::layout');

        if ($this->entity->template() === 'default') {
            $this->entity->template('statamic-static-prerenderer::headless');
        }

        $html = $this->entity->toResponse(request())->content();

        $css = app(TailwindCSS::class)->process($cssPath);

        $style = sprintf('<style>%s</style>', $css);

        Storage::put($path, $style . $html);

        return Storage::get($path);
    }
}
