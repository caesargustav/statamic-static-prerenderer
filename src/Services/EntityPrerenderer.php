<?php

namespace Caesargustav\StaticPrerenderer\Services;

use Caesargustav\StaticPrerenderer\PrerenderedEntity;
use Statamic\Contracts\Entries\Entry;
use Statamic\Contracts\Taxonomies\Term;
use Statamic\Exceptions\NotFoundHttpException;

class EntityPrerenderer
{
    public function render(Entry|Term $entity): void
    {
        try {
            PrerenderedEntity::create($entity)->prerender();
        } catch (NotFoundHttpException) {
            // The entity may become unroutable between lookup and render.
        }
    }
}
