<?php

namespace Caesargustav\StaticPrerenderer;

use Caesargustav\StaticPrerenderer\Services\TailwindCSS;
use Illuminate\Support\Facades\Storage;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Entries\Entry;
use Statamic\View\View;

class PrerenderedEntry
{
    public function __construct(private readonly Entry $entry)
    {
    }

    public static function create(Entry|EntryContract $entry): PrerenderedEntry
    {
        return new static($entry);
    }

    public function id(): string
    {
        return $this->entry->id();
    }

    public function data(): array
    {
        return $this->entry->data()->toArray();
    }

    public function html(): string
    {
        return $this->prerender();
    }

    public function prerender(): string
    {
        $path = 'public/statamic-static-prerenderer/' . $this->entry->id() . '.html';
        $cssPath = 'public/statamic-static-prerenderer/' . $this->entry->id() . '.css';
        $lastModified = $this->entry->lastModified();

        if (Storage::exists($path) && Storage::lastModified($path) >= $lastModified->timestamp) {
            return Storage::get($path);
        }

        $template = app(View::class)
            ->make('statamic-static-prerenderer::headless');

        $this->entry->layout('statamic-static-prerenderer::layout');
        $this->entry->template($template->template());

        $html = $this->entry->toResponse(request())->content();

        $css = app(TailwindCSS::class)->process($cssPath);

        $style = sprintf('<style>%s</style>', $css);

        Storage::put($path, $style . $html);

        return Storage::get($path);
    }
}
