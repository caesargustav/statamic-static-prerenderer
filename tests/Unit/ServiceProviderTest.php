<?php

namespace Tests\Unit;

use Caesargustav\StaticPrerenderer\Imaging\RevalidatingGlideResponseFactory;
use Caesargustav\StaticPrerenderer\ServiceProvider;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Illuminate\Http\Request;
use League\Glide\Server;
use PHPUnit\Framework\TestCase;

class ServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        Container::setInstance(null);
        parent::tearDown();
    }

    public function test_glide_server_is_extended_before_statamics_deferred_binding_resolves(): void
    {
        $container = new Container;
        $container->instance('config', new Repository([
            'statamic-static-prerenderer' => ['revalidate_glide_responses' => true],
        ]));
        $container->instance('request', Request::create('/img/test.jpg'));
        Container::setInstance($container);

        (new ServiceProvider($container))->register();

        $server = $this->createMock(Server::class);
        $server->expects($this->once())
            ->method('setResponseFactory')
            ->with($this->isInstanceOf(RevalidatingGlideResponseFactory::class));
        $container->singleton(Server::class, fn () => $server);

        $this->assertSame($server, $container->make(Server::class));
    }
}
