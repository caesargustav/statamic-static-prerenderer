<?php

namespace Caesargustav\StaticPrerenderer\Blueprints;

use Statamic\Facades\Blueprint as StatamicBlueprint;

class ExternalDataBlueprint
{
    public static function requestBlueprint()
    {
        return StatamicBlueprint::make()
            ->setContents([
                'sections' => [
                    'main' => [
                        'fields' => [
                            [
                                'handle' => 'meta_title',
                                'field' => [
                                    'type' => 'text',
                                    'display' => 'Meta Title',
                                ],
                            ],
                            [
                                'handle' => 'meta_description',
                                'field' => [
                                    'type' => 'text',
                                    'display' => 'Meta Beschreibung',
                                ],
                            ],
                            [
                                'handle' => 'no_index_page',
                                'field' => [
                                    'type' => 'toggle',
                                    'display' => 'No Index',
                                    'width' => 50,
                                ],
                            ],
                            [
                                'handle' => 'no_follow_links',
                                'field' => [
                                    'type' => 'toggle',
                                    'display' => 'No Follow',
                                    'width' => 50,
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
    }
}
