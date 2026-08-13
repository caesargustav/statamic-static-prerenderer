<?php

namespace Caesargustav\StaticPrerenderer\Imaging;

use Statamic\Imaging\ResponseFactory;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RevalidatingGlideResponseFactory extends ResponseFactory
{
    public function create($cache, $path)
    {
        $stream = $cache->readStream($path);
        $etag = hash('sha256', stream_get_contents($stream));
        rewind($stream);

        $response = new StreamedResponse;
        $response->headers->set('Content-Type', $cache->mimeType($path));
        $response->headers->set('Content-Length', $cache->fileSize($path));
        $response->headers->set('Cache-Control', 'public, no-cache, must-revalidate');
        $response->setEtag($etag);
        $response->setLastModified(date_create()->setTimestamp($cache->lastModified($path)));
        $response->setExpires(date_create());

        if ($this->request) {
            $response->isNotModified($this->request);
        }

        $response->setCallback(function () use ($stream): void {
            fpassthru($stream);
            fclose($stream);
        });

        return $response;
    }
}
