<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ResponsiveImageTemplateTest extends TestCase
{
    public function test_responsive_images_use_the_assets_focal_point_without_recropping_the_source(): void
    {
        $template = file_get_contents(__DIR__.'/../../resources/views/pagebuilder/responsive_image.antlers.html');

        $this->assertStringContainsString('object-position: {{ focus_css }}', $template);
        $this->assertStringContainsString('fit="max"', $template);
    }

    public function test_all_image_blocks_use_the_shared_responsive_template(): void
    {
        foreach (['image', 'image_with_text'] as $block) {
            $template = file_get_contents(__DIR__."/../../resources/views/pagebuilder/{$block}.antlers.html");

            $this->assertStringContainsString('pagebuilder/responsive_image', $template);
            $this->assertStringNotContainsString('src="{{ permalink }}"', $template);
        }
    }

    public function test_all_responsive_glide_urls_are_absolute_for_cross_domain_embedding(): void
    {
        $template = file_get_contents(__DIR__.'/../../resources/views/pagebuilder/responsive_image.antlers.html');

        preg_match_all('/{{\s*glide:url\b([^}]*)}}/', $template, $matches);

        $this->assertNotEmpty($matches[1]);

        foreach ($matches[1] as $parameters) {
            $this->assertStringContainsString('absolute="true"', $parameters);
        }
    }

    public function test_image_with_text_creates_a_real_crop_frame_for_the_focal_point(): void
    {
        $template = file_get_contents(__DIR__.'/../../resources/views/pagebuilder/image_with_text.antlers.html');

        $this->assertStringContainsString('hls-relative hls-min-h-64', $template);
        $this->assertStringContainsString('hls-absolute hls-inset-0 hls-object-cover', $template);
    }
}
