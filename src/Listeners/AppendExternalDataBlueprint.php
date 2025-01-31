<?php

namespace Caesargustav\StaticPrerenderer\Listeners;

use Caesargustav\StaticPrerenderer\Blueprints\ExternalDataBlueprint;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Events\TermBlueprintFound;
use Statamic\Support\Str;

class AppendExternalDataBlueprint
{
    public function handle(EntryBlueprintFound|TermBlueprintFound $event): void
    {
        // We don't want the SEO fields to get added to the blueprint editor
        if (Str::contains(request()->url(), '/blueprints/')) {
            return;
        }

        $blueprint = $event->blueprint;
        $contents = $blueprint->contents();

        $externalDataBlueprint = ExternalDataBlueprint::requestBlueprint();
        $contents['tabs']['Externe Daten'] = $externalDataBlueprint->contents()['tabs']['main'];

        $blueprint->setContents($contents);
    }
}
