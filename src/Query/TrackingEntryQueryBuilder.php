<?php

namespace Caesargustav\StaticPrerenderer\Query;

use Caesargustav\StaticPrerenderer\Dependencies\PrerenderContext;
use Statamic\Facades\Collection;
use Statamic\Stache\Query\EntryQueryBuilder;
use Statamic\Stache\Stores\Store;

class TrackingEntryQueryBuilder extends EntryQueryBuilder
{
    public function __construct(Store $store, private readonly PrerenderContext $context)
    {
        parent::__construct($store);
    }

    protected function getFilteredKeys()
    {
        $keys = parent::getFilteredKeys();
        $collections = empty($this->collections) ? Collection::handles() : $this->collections;

        foreach ($collections as $collection) {
            if (is_string($collection)) {
                $this->context->record($collection);
            }
        }

        return $keys;
    }
}
