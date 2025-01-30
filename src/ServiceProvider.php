<?php

namespace Caesargustav\StaticPrerenderer;

use Caesargustav\StaticPrerenderer\Services\TailwindCSS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\EntryCreated;
use Statamic\Events\EntrySaved;
use Statamic\Events\TermBlueprintFound;
use Statamic\Facades\Entry;
use Statamic\Facades\Term;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $publishAfterInstall = false;

    protected $listen = [
        EntryBlueprintFound::class => [
            Listeners\AppendExternalDataBluetprint::class,
        ],
        TermBlueprintFound::class => [
            Listeners\AppendExternalDataBluetprint::class,
        ],
        EntryCreated::class => [
            Listeners\GenerateStaticHtml::class,
        ],
        EntrySaved::class => [
            Listeners\GenerateStaticHtml::class,
            Listeners\ClearRelatedCaches::class
        ],
    ];

    public function bootAddon(): void
    {
        $this->loadRoutes();

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/statamic-static-prerenderer'),
        ]);

        app()->bind(TailwindCSS::class, fn () => new TailwindCSS($this->getAddon()->directory() . 'bin'));

        Statamic::afterInstalled(function ($command) {
            app()->get(TailwindCSS::class)->downloadBinary();
        });
    }

    protected function loadRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/static-prerenderer')
            ->group(function () {
                Route::get('/', function (Request $request) {
                    $entries = Entry::query()
                        ->where('published', '=', true)
                        ->where('url', '!=', '/')
                        ->get(['id', 'slug', 'url'])
                        ->map(function ($entry) {
                            return [
                                'id' => $entry->id(),
                                'type' => 'entry',
                                'slug' => $entry->slug(),
                                'url' => $entry->url(),
                            ];
                        })
                        ->toArray();

                    $terms = Term::query()
                        ->where('published', '=', true)
                        ->where('entries_count', '>=', 1)
                        ->get(['id', 'slug', 'url'])
                        ->map(function ($term) {
                            return [
                                'id' => $term->id(),
                                'type' => 'term',
                                'slug' => $term->slug(),
                                'url' => $term->url(),
                            ];
                        })
                        ->toArray();

                    return response()->json(array_merge($entries, $terms));
                });

                Route::get('{type}/{entryId}', function (Request $request, string $type, string $entryId) {
                    match ($type) {
                        'entry' => $entity = Entry::find($entryId),
                        'term' => $entity = Term::find($entryId),
                    };
                    $prerenderedEntry = PrerenderedEntity::create($entity);

                    return response()->json([
                        'data' => $prerenderedEntry->data(),
                        'html' => $prerenderedEntry->html(),
                    ]);
                });
            });
    }
}
