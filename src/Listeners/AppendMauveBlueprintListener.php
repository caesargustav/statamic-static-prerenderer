<?php

namespace Caesargustav\MauveConnector\Listeners;

use Caesargustav\MauveConnector\Blueprints\MauveBlueprint;
use Statamic\Events\EntryBlueprintFound;
use Statamic\Support\Str;

class AppendMauveBlueprintListener
{
    public function handle(EntryBlueprintFound $event): void
    {
        // We don't want the SEO fields to get added to the blueprint editor
        if (Str::contains(request()->url(), '/blueprints/')) {
            return;
        }

        $blueprint = $event->blueprint;
        $contents = $blueprint->contents();

        $mauveBlueprint = MauveBlueprint::requestBlueprint();
        $contents['tabs']['Mauve'] = $mauveBlueprint->contents()['tabs']['main'];

        $blueprint->setContents($contents);
    }
}
