<?php

namespace Caesargustav\StaticPrerenderer;

use Caesargustav\StaticPrerenderer\Services\TailwindCSS;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\EntryCreated;
use Statamic\Events\EntrySaved;
use Statamic\Facades\Entry;
use Statamic\Providers\AddonServiceProvider;
use Statamic\Statamic;

class ServiceProvider extends AddonServiceProvider
{
    protected $publishAfterInstall = false;

    protected $listen = [
        EntryBlueprintFound::class => [
            Listeners\AppendExternalDataBluetprint::class,
        ],
        EntryCreated::class => [
            Listeners\GenerateStaticHtml::class
        ],
        EntrySaved::class => [
            Listeners\GenerateStaticHtml::class
        ],
    ];

    public function bootAddon(): void
    {
        $this->loadRoutes();

        $this->publishes([
            __DIR__ . '/../resources/views' => resource_path('views/vendor/static-prerenderer')
        ]);

        app()->bind(TailwindCSS::class, fn () => new TailwindCSS($this->getAddon()->directory() . 'bin',));

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
                        ->get(['id', 'slug', 'url']);

                    return response()->json($entries);
                });

                Route::get('{entryId}', function (Request $request, string $entryId) {
                    $prerenderedEntry = PrerenderedEntry::create(Entry::find($entryId));

                    return response()->json([
                        'data' => $prerenderedEntry->data(),
                        'html' => $prerenderedEntry->html()
                    ]);
                });
            });
    }
}
