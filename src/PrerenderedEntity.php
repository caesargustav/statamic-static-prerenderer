<?php

namespace Caesargustav\StaticPrerenderer;

use Caesargustav\StaticPrerenderer\Services\TailwindCSS;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Storage;
use Statamic\Contracts\Entries\Entry as EntryContract;
use Statamic\Contracts\Taxonomies\Term as TermContract;

class PrerenderedEntity
{
    public function __construct(private readonly EntryContract|TermContract $entity, private readonly ?Request $request = null) {}

    public static function create(EntryContract|TermContract $entry, ?Request $request = null): PrerenderedEntity
    {
        return new static($entry, $request);
    }

    public function id(): string
    {
        return $this->entity->id();
    }

    public function data(): array
    {
        if (!$this->isAuthorized()) {
            return [];
        }

        return $this->entity->data()->toArray();
    }

    public function html(): string
    {
        return $this->prerender();
    }

    public function prerender(): string
    {
        [$path, $cssPath] = $this->cachePaths();

        $lastModified = $this->entity->lastModified();

        if (!$this->isAuthorized()) {
            return view('statamic-static-prerenderer::login');
        }

        if (Storage::exists($path) && Storage::lastModified($path) >= $lastModified->timestamp && $this->request) {
            return Storage::get($path);
        }

        $this->entity->layout('statamic-static-prerenderer::layout');

        if ($this->entity->template() === 'default') {
            $this->entity->template('statamic-static-prerenderer::headless');
        }

        // Set a custom path resolver that returns a relative URL
        // If we don't do this, the paginator resolves the API URL as the URL is resolved from the request
        Paginator::currentPathResolver(fn () => '');

        $html = $this->entity->toResponse(request())->content();

        // only process $html, not all content
        $css = app(TailwindCSS::class)->process($cssPath);

        $style = sprintf('<style>%s</style>', $css);

        Storage::put($path, $style.$html);

        return Storage::get($path);
    }

    private function cachePaths(): array
    {
        $id = $this->entity->id() . md5(json_encode($this->request?->query->all()));

        return [
            'public/statamic-static-prerenderer/'.$id.'.html',
            'public/statamic-static-prerenderer/'.$id.'.css',
        ];
    }

    private function isAuthorized(): bool
    {
        if (!$this->request) {
            return true;
        }

        if ($this->entity->data()->get('protect_api') !== 'password') {
            return true;
        }

        return $this->request->get('password') === $this->entity->data()->get('password');
    }
}
