<?php

namespace Caesargustav\StaticPrerenderer\Dependencies;

use Illuminate\Container\Container;

class DependencyLock
{
    public function synchronized(callable $callback): mixed
    {
        $container = Container::getInstance();
        $directory = $container->bound('path.storage')
            ? $container->make('path.storage').'/statamic-static-prerenderer'
            : sys_get_temp_dir().'/statamic-static-prerenderer-'.md5(__DIR__);

        if (! is_dir($directory) && ! @mkdir($directory, 0777, true) && ! is_dir($directory)) {
            throw new \RuntimeException('Unable to create the prerender dependency lock directory.');
        }

        $handle = fopen($directory.'/dependencies.lock', 'c');

        if ($handle === false) {
            throw new \RuntimeException('Unable to open the prerender dependency lock.');
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new \RuntimeException('Unable to acquire the prerender dependency lock.');
            }

            return $callback();
        } finally {
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
