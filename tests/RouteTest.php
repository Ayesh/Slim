<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests;

use Exception;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_TestCase;
use Slim\Container;
use Slim\DeferredCallable;
use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;
use Slim\Route;
use Slim\Tests\Mocks\CallableTest;
use Slim\Tests\Mocks\InvocationStrategyTest;
use Slim\Tests\Mocks\MiddlewareStub;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class RouteTest extends TestCase
{
    public function routeFactory(): Route {
        $methods = ['GET', 'POST'];
        $pattern = '/hello/{name}';
        $callable = function ($req, $res, $args) {
            // Do something
        };

        return new Route($methods, $pattern, $callable);
    }

    public function testConstructor(): void {
        $methods = ['GET', 'POST'];
        $pattern = '/hello/{name}';
        $callable = function ($req, $res, $args) {
            // Do something
        };
        $route = new Route($methods, $pattern, $callable);

        $this->assertAttributeEquals($methods, 'methods', $route);
        $this->assertAttributeEquals($pattern, 'pattern', $route);
        $this->assertAttributeEquals($callable, 'callable', $route);
    }

    public function testGetMethodsReturnsArrayWhenContructedWithString(): void {
        $route = new Route('GET', '/hello', function ($req, $res, $args) {
            // Do something
        });

        $this->assertEquals(['GET'], $route->getMethods());
    }

    public function testGetMethods(): void {
        $this->assertEquals(['GET', 'POST'], $this->routeFactory()->getMethods());
    }

    public function testGetPattern(): void {
        $this->assertEquals('/hello/{name}', $this->routeFactory()->getPattern());
    }

    public function testGetCallable(): void {
        $callable = $this->routeFactory()->getCallable();

        $this->assertInternalType('callable', $callable);
    }

    public function testArgumentSetting(): void {
        $route = $this->routeFactory();
        $route->setArguments(['foo' => 'FOO', 'bar' => 'BAR']);
        $this->assertSame($route->getArguments(), ['foo' => 'FOO', 'bar' => 'BAR']);
        $route->setArgument('bar', 'bar');
        $this->assertSame($route->getArguments(), ['foo' => 'FOO', 'bar' => 'bar']);
        $route->setArgument('baz', 'BAZ');
        $this->assertSame($route->getArguments(), ['foo' => 'FOO', 'bar' => 'bar', 'baz' => 'BAZ']);

        $route->setArguments(['a' => 'b']);
        $this->assertSame($route->getArguments(), ['a' => 'b']);
        $this->assertSame($route->getArgument('a', 'default'), 'b');
        $this->assertSame($route->getArgument('b', 'default'), 'default');

        $this->assertEquals($route, $route->setArgument('c', null));
        $this->assertEquals($route, $route->setArguments(['d' => null]));
    }


    public function testBottomMiddlewareIsRoute(): void {
        $route = $this->routeFactory();
        $bottom = null;
        $mw = function ($req, $res, $next) use (&$bottom) {
            $bottom = $next;
            return $res;
        };
        $route->add($mw);
        $route->finalize();

        $route->callMiddlewareStack(
            $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock()
        );

        $this->assertEquals($route, $bottom);
    }

    public function testAddMiddleware(): void {
        $route = $this->routeFactory();
        $called = 0;

        $mw = function ($req, $res, $next) use (&$called) {
            $called++;
            return $res;
        };

        $route->add($mw);
        $route->finalize();

        $route->callMiddlewareStack(
            $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock()
        );

        $this->assertSame($called, 1);
    }

    public function testRefinalizing(): void {
        $route = $this->routeFactory();
        $called = 0;

        $mw = function ($req, $res, $next) use (&$called) {
            $called++;
            return $res;
        };

        $route->add($mw);

        $route->finalize();
        $route->finalize();

        $route->callMiddlewareStack(
            $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock()
        );

        $this->assertSame($called, 1);
    }


    public function testIdentifier(): void {
        $route = $this->routeFactory();
        $this->assertEquals('route0', $route->getIdentifier());
    }

    public function testSetName(): void {
        $route = $this->routeFactory();
        $this->assertEquals($route, $route->setName('foo'));
        $this->assertEquals('foo', $route->getName());
    }

    public function testSetInvalidName(): void {
        $route = $this->routeFactory();

        $this->setExpectedException('InvalidArgumentException');

        $route->setName(false);
    }

    public function testSetOutputBuffering(): void {
        $route = $this->routeFactory();

        $route->setOutputBuffering(false);
        $this->assertFalse($route->getOutputBuffering());

        $route->setOutputBuffering('append');
        $this->assertSame('append', $route->getOutputBuffering());

        $route->setOutputBuffering('prepend');
        $this->assertSame('prepend', $route->getOutputBuffering());

        $this->assertEquals($route, $route->setOutputBuffering(false));
    }

    public function testSetInvalidOutputBuffering(): void {
        $route = $this->routeFactory();

        $this->setExpectedException('InvalidArgumentException');

        $route->setOutputBuffering('invalid');
    }

    public function testAddMiddlewareAsString(): void {
        $route = $this->routeFactory();

        $container = new Container();
        $container['MiddlewareStub'] = new MiddlewareStub();

        $route->setContainer($container);
        $route->add('MiddlewareStub:run');

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [
            'user' => 'john',
            'id' => '123',
        ];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);

        $response = new Response;
        $result = $route->callMiddlewareStack($request, $response);

        $this->assertInstanceOf(Response::class, $result);
    }

    public function testControllerInContainer(): void {

        $container = new Container();
        $container['CallableTest'] = new CallableTest;

        $deferred = new DeferredCallable('CallableTest:toCall', $container);

        $route = new Route(['GET'], '/', $deferred);
        $route->setContainer($container);

        $uri = Uri::createFromString('https://example.com:80');
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, new Headers(), [], Environment::mock()->all(), $body);

        CallableTest::$CalledCount = 0;

        $result = $route->callMiddlewareStack($request, new Response);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testInvokeWhenReturningAResponse(): void {
        $callable = function ($req, $res, $args) {
            return $res->write('foo');
        };
        $route = new Route(['GET'], '/', $callable);

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('foo', (string)$response->getBody());
    }

    public function testInvokeWhenReturningAString(): void {
        $callable = function ($req, $res, $args) {
            return "foo";
        };
        $route = new Route(['GET'], '/', $callable);

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('foo', (string)$response->getBody());
    }

    /**
     * @expectedException Exception
     */
    public function testInvokeWithException(): void {
        $callable = function ($req, $res, $args) {
            throw new Exception();
        };
        $route = new Route(['GET'], '/', $callable);

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);
    }

    public function testInvokeWhenDisablingOutputBuffer(): void {
        ob_start();
        $callable = function ($req, $res, $args) {
            echo 'foo';
            return $res->write('bar');
        };
        $route = new Route(['GET'], '/', $callable);
        $route->setOutputBuffering(false);

        $env = Environment::mock();
        $uri = Uri::createFromString('https://example.com:80');
        $headers = new Headers();
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $response = new Response;

        $response = $route->__invoke($request, $response);

        $this->assertEquals('bar', (string)$response->getBody());

        $output = ob_get_clean();
        $this->assertEquals('foo', $output);
    }

    public function testInvokeDeferredCallable(): void {
        $container = new Container();
        $container['CallableTest'] = new CallableTest;
        $container['foundHandler'] = function () {
            return new InvocationStrategyTest();
        };

        $route = new Route(['GET'], '/', 'CallableTest:toCall');
        $route->setContainer($container);

        $uri = Uri::createFromString('https://example.com:80');
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, new Headers(), [], Environment::mock()->all(), $body);

        $result = $route->callMiddlewareStack($request, new Response);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals([$container['CallableTest'], 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }

    public function testPatternCanBeChanged(): void {
        $route = $this->routeFactory();
        $route->setPattern('/hola/{nombre}');
        $this->assertEquals('/hola/{nombre}', $route->getPattern());
    }

    public function testChangingCallable(): void {
        $container = new Container();
        $container['CallableTest2'] = new CallableTest;
        $container['foundHandler'] = function () {
            return new InvocationStrategyTest();
        };

        $route = new Route(['GET'], '/', 'CallableTest:toCall'); //Note that this doesn't actually exist
        $route->setContainer($container);

        $route->setCallable('CallableTest2:toCall'); //Then we fix it here.

        $uri = Uri::createFromString('https://example.com:80');
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', $uri, new Headers(), [], Environment::mock()->all(), $body);

        $result = $route->callMiddlewareStack($request, new Response);

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals([$container['CallableTest2'], 'toCall'], InvocationStrategyTest::$LastCalledFor);
    }
}
