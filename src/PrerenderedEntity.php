<?php

namespace Caesargustav\StaticPrerenderer;

use Caesargustav\StaticPrerenderer\Services\TailwindCSS;
use Illuminate\Http\Request;
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

        if (Storage::exists($path) && Storage::lastModified($path) >= $lastModified->timestamp) {
            return Storage::get($path);
        }

        $this->entity->layout('statamic-static-prerenderer::layout');

        if ($this->entity->template() === 'default') {
            $this->entity->template('statamic-static-prerenderer::headless');
        }

        $html = $this->entity->toResponse(request())->content();

        // only process $html, not all content
        $css = app(TailwindCSS::class)->process($cssPath);

        $style = sprintf('<style>%s</style>', $css);

        Storage::put($path, $style.$html);

        return Storage::get($path);
    }

    private function cachePaths(): array
    {
        return [
            'public/statamic-static-prerenderer/'.$this->entity->id().'.html',
            'public/statamic-static-prerenderer/'.$this->entity->id().'.css',
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
