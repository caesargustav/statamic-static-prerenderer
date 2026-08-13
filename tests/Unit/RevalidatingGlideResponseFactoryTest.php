<?php

namespace Tests\Unit;

use Caesargustav\StaticPrerenderer\Imaging\RevalidatingGlideResponseFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class RevalidatingGlideResponseFactoryTest extends TestCase
{
    public function test_glide_responses_must_be_revalidated_by_the_browser(): void
    {
        $response = (new RevalidatingGlideResponseFactory(Request::create('/img/test.jpg')))
            ->create($this->cacheContaining('image-data'), 'test.jpg');

        $this->assertSame(
            'must-revalidate, no-cache, public',
            $response->headers->get('Cache-Control'),
        );
        $this->assertNotNull($response->getLastModified());
        $this->assertNotNull($response->getEtag());
    }

    public function test_etag_detects_changed_image_bytes_with_the_same_timestamp(): void
    {
        $initial = (new RevalidatingGlideResponseFactory(Request::create('/img/test.jpg')))
            ->create($this->cacheContaining('old-image'), 'test.jpg');

        $request = Request::create('/img/test.jpg', server: [
            'HTTP_IF_NONE_MATCH' => $initial->getEtag(),
        ]);
        $changed = (new RevalidatingGlideResponseFactory($request))
            ->create($this->cacheContaining('new-image'), 'test.jpg');

        $this->assertSame(200, $changed->getStatusCode());
        $this->assertNotSame($initial->getEtag(), $changed->getEtag());
    }

    private function cacheContaining(string $contents): object
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $contents);
        rewind($stream);

        return new class($stream, strlen($contents))
        {
            public function __construct(private $stream, private int $size) {}

            public function readStream(string $path)
            {
                return $this->stream;
            }

            public function mimeType(string $path): string
            {
                return 'image/jpeg';
            }

            public function fileSize(string $path): int
            {
                return $this->size;
            }

            public function lastModified(string $path): int
            {
                return 1_700_000_000;
            }
        };
    }
}
