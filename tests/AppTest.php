<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests;

use BadMethodCallException;
use Error;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UriInterface;
use ReflectionMethod;
use RuntimeException;
use Slim\App;
use Slim\Container;
use Slim\Exception\MethodNotAllowedException;
use Slim\Exception\NotFoundException;
use Slim\Exception\SlimException;
use Slim\Handlers\Strategies\RequestResponseArgs;
use Slim\Http\Body;
use Slim\Http\Environment;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\RequestBody;
use Slim\Http\Response;
use Slim\Http\StatusCode;
use Slim\Http\Uri;
use Slim\Router;
use Slim\Tests\Assets\HeaderStack;
use Slim\Tests\Mocks\MockAction;
use Slim\Route;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Emit a header, without creating actual output artifacts
 *
 * @param string $value
 */
function header($value, $replace = true)
{
    \Slim\header($value, $replace);
}

class AppTest extends TestCase
{
    public function setUp(): void
    {
        HeaderStack::reset();
    }

    public function tearDown(): void
    {
        HeaderStack::reset();
    }

    public static function setupBeforeClass(): void
    {
        // ini_set('log_errors', 0);
        ini_set('error_log', tempnam(sys_get_temp_dir(), 'slim'));
    }

    public static function tearDownAfterClass(): void
    {
        // ini_set('log_errors', 1);
    }

    public function testContainerInterfaceException(): void {
        $this->expectException('InvalidArgumentException', 'Expected a ContainerInterface');
        $this->expectExceptionMessage('Expected a ContainerInterface');
        $app = new App('');
    }

    public function testIssetInContainer(): void {
        $app = new App();
        $router = $app->getContainer()->get('router');
        $this->assertTrue(isset($router));
    }

    public function testGetRoute(): void {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->get($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('GET', $route->getMethods());
    }

    public function testPostRoute(): void {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->post($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('POST', $route->getMethods());
    }

    public function testPutRoute(): void {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->put($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('PUT', $route->getMethods());
        $this->assertArrayNotHasKey('POST', $route->getMethods());
    }

    public function testPatchRoute(): void {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->patch($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('PATCH', $route->getMethods());
    }

    public function testDeleteRoute(): void {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->delete($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('DELETE', $route->getMethods());
    }

    public function testOptionsRoute(): void {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->options($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('OPTIONS', $route->getMethods());
    }

    public function testAnyRoute(): void {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->any($path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('GET', $route->getMethods());
        $this->assertArrayHasKey('POST', $route->getMethods());
        $this->assertArrayHasKey('PUT', $route->getMethods());
        $this->assertArrayHasKey('PATCH', $route->getMethods());
        $this->assertArrayHasKey('DELETE', $route->getMethods());
        $this->assertArrayHasKey('OPTIONS', $route->getMethods());
    }

    public function testMapRoute(): void {
        $path = '/foo';
        $callable = function ($req, $res) {
            // Do something
        };
        $app = new App();
        $route = $app->map(['GET', 'POST'], $path, $callable);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('GET', $route->getMethods());
        $this->assertArrayHasKey('POST', $route->getMethods());
    }

    public function testRedirectRoute(): void {
        $source = '/foo';
        $destination = '/bar';

        $app = new App();
        $request = $this->getMockBuilder(ServerRequestInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $route = $app->redirect($source, $destination, 301);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertArrayHasKey('GET', $route->getMethods());

        $response = $route->run($request, new Response());
        $this->assertEquals(301, $response->getStatusCode());
        $this->assertEquals($destination, $response->getHeaderLine('Location'));

        $routeWithDefaultStatus = $app->redirect($source, $destination);
        $response = $routeWithDefaultStatus->run($request, new Response());
        $this->assertEquals(302, $response->getStatusCode());

        $uri = $this->getMockBuilder(UriInterface::class)->getMock();
        $uri->expects($this->once())->method('__toString')->willReturn($destination);

        $routeToUri = $app->redirect($source, $uri);
        $response = $routeToUri->run($request, new Response());
        $this->assertEquals($destination, $response->getHeaderLine('Location'));
    }

    public function testBottomMiddlewareIsApp(): void {
        $app = new App();
        $bottom = null;
        $mw = function ($req, $res, $next) use (&$bottom) {
            $bottom = $next;
            return $res;
        };
        $app->add($mw);

        $app->callMiddlewareStack(
            $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock()
        );

        $this->assertEquals($app, $bottom);
    }

    public function testAddMiddleware(): void {
        $app = new App();
        $called = 0;

        $mw = function ($req, $res, $next) use (&$called) {
            $called++;
            return $res;
        };
        $app->add($mw);

        $app->callMiddlewareStack(
            $this->getMockBuilder(ServerRequestInterface::class)->disableOriginalConstructor()->getMock(),
            $this->getMockBuilder(ResponseInterface::class)->disableOriginalConstructor()->getMock()
        );

        $this->assertSame($called, 1);
    }

    public function testAddMiddlewareOnRoute(): void {
        $app = new App();

        $app->get('/', function ($req, $res) {
            return $res->write('Center');
        })->add(function ($req, $res, $next) {
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');

            return $res;
        })->add(function ($req, $res, $next) {
            $res->write('In2');
            $res = $next($req, $res);
            $res->write('Out2');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $app($req, $res);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$res->getBody());
    }

    public function testAddMiddlewareOnRouteGroup(): void {
        $app = new App();

        $app->group('/foo', function ($app) {
            $app->get('/', function ($req, $res) {
                return $res->write('Center');
            });
        })->add(function ($req, $res, $next) {
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');

            return $res;
        })->add(function ($req, $res, $next) {
            $res->write('In2');
            $res = $next($req, $res);
            $res->write('Out2');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $app($req, $res);

        $this->assertEquals('In2In1CenterOut1Out2', (string)$res->getBody());
    }

    public function testAddMiddlewareOnTwoRouteGroup(): void {
        $app = new App();

        $app->group('/foo', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/', function ($req, $res) {
                    return $res->write('Center');
                });
            })->add(function ($req, $res, $next) {
                $res->write('In2');
                $res = $next($req, $res);
                $res->write('Out2');

                return $res;
            });
        })->add(function ($req, $res, $next) {
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/baz/',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $app($req, $res);

        $this->assertEquals('In1In2CenterOut2Out1', (string)$res->getBody());
    }

    public function testAddMiddlewareOnRouteAndOnTwoRouteGroup(): void {
        $app = new App();

        $app->group('/foo', function ($app) {
            $app->group('/baz', function ($app) {
                $app->get('/', function ($req, $res) {
                    return $res->write('Center');
                })->add(function ($req, $res, $next) {
                    $res->write('In3');
                    $res = $next($req, $res);
                    $res->write('Out3');

                    return $res;
                });
            })->add(function ($req, $res, $next) {
                $res->write('In2');
                $res = $next($req, $res);
                $res->write('Out2');

                return $res;
            });
        })->add(function ($req, $res, $next) {
            $res->write('In1');
            $res = $next($req, $res);
            $res->write('Out1');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/baz/',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $app($req, $res);

        $this->assertEquals('In1In2In3CenterOut3Out2Out1', (string)$res->getBody());
    }

    public function testInvokeReturnMethodNotAllowed(): void {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'POST',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('POST', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals(405, (string)$resOut->getStatusCode());
        $this->assertEquals(['GET'], $resOut->getHeader('Allow'));
        $this->assertStringContainsString(
            '<p>Method not allowed. Must be one of: <strong>GET</strong></p>',
            (string)$resOut->getBody()
        );

        // now test that exception is raised if the handler isn't registered
        unset($app->getContainer()['notAllowedHandler']);
        $this->expectException(MethodNotAllowedException::class);
        $app($req, $res);
    }

    public function testInvokeWithMatchingRoute(): void {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals('Hello', (string)$res->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArgument(): void {
        $app = new App();
        $app->get('/foo/bar', function ($req, $res, $args) {
            return $res->write("Hello {$args['attribute']}");
        })->setArgument('attribute', 'world!');

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals('Hello world!', (string)$res->getBody());
    }

    public function testInvokeWithMatchingRouteWithSetArguments(): void {
        $app = new App();
        $app->get('/foo/bar', function ($req, $res, $args) {
            return $res->write("Hello {$args['attribute1']} {$args['attribute2']}");
        })->setArguments(['attribute1' => 'there', 'attribute2' => 'world!']);

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals('Hello there world!', (string)$res->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameter(): void {
        $app = new App();
        $app->get('/foo/{name}', function ($req, $res, $args) {
            return $res->write("Hello {$args['name']}");
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/test!',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals('Hello test!', (string)$res->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterRequestResponseArgStrategy(): void {
        $c = new Container();
        $c['foundHandler'] = function ($c) {
            return new RequestResponseArgs();
        };

        $app = new App($c);
        $app->get('/foo/{name}', function ($req, $res, $name) {
            return $res->write("Hello {$name}");
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/test!',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals('Hello test!', (string)$res->getBody());
    }

    public function testInvokeWithMatchingRouteWithNamedParameterOverwritesSetArgument(): void {
        $app = new App();
        $app->get('/foo/{name}', function ($req, $res, $args) {
            return $res->write("Hello {$args['extra']} {$args['name']}");
        })->setArguments(['extra' => 'there', 'name' => 'world!']);

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/test!',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals('Hello there test!', (string)$res->getBody());
    }

    public function testInvokeWithoutMatchingRoute(): void {
        $app = new App();
        $app->get('/bar', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals(404, $resOut->getStatusCode());

        // now test that exception is raised if the handler isn't registered
        unset($app->getContainer()['notFoundHandler']);
        $this->expectException(NotFoundException::class);
        $app($req, $res);
    }

    public function testInvokeWithPimpleCallable(): void {
        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        $mock = $this->getMockBuilder('StdClass')->setMethods(['bar'])->getMock();

        $app = new App();
        $container = $app->getContainer();
        $container['foo'] = function () use ($mock, $res) {
            $mock->method('bar')
                ->willReturn(
                    $res->write('Hello')
                );
            return $mock;
        };

        $app->get('/foo', 'foo:bar');

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals('Hello', (string)$res->getBody());
    }

    public function testInvokeWithPimpleUndefinedCallable(): void {
        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        $mock = $this->getMockBuilder('StdClass')->getMock();

        $app = new App();
        $container = $app->getContainer();
        $container['foo'] = function () use ($mock, $res) {
            return $mock;
        };

        $app->get('/foo', 'foo:bar');

        $this->expectException(RuntimeException::class);

        // Invoke app
        $app($req, $res);
    }

    public function testInvokeWithPimpleCallableViaMagicMethod(): void {
        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        $mock = new MockAction();

        $app = new App();
        $container = $app->getContainer();
        $container['foo'] = function () use ($mock, $res) {
            return $mock;
        };

        $app->get('/foo', 'foo:bar');

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals(json_encode(['name'=>'bar', 'arguments' => []]), (string)$res->getBody());
    }

    public function testInvokeFunctionName(): void {
        $app = new App();

        // @codingStandardsIgnoreStart
        function handle($req, $res)
        {
            $res->write('foo');

            return $res;
        }
        // @codingStandardsIgnoreEnd

        $app->get('/foo', __NAMESPACE__ . '\handle');

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $app($req, $res);

        $this->assertEquals('foo', (string)$res->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArguments(): void {
        $app = new App();
        $app->get('/foo/{name}', function ($req, $res, $args) {
            return $res->write($req->getAttribute('one') . $args['name']);
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/rob',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $req = $req->withAttribute("one", 1);
        $res = new Response();


        // Invoke app
        $resOut = $app($req, $res);
        $this->assertEquals('1rob', (string)$resOut->getBody());
    }

    public function testCurrentRequestAttributesAreNotLostWhenAddingRouteArgumentsRequestResponseArg(): void {
        $c = new Container();
        $c['foundHandler'] = function () {
            return new RequestResponseArgs();
        };

        $app = new App($c);
        $app->get('/foo/{name}', function ($req, $res, $name) {
            return $res->write($req->getAttribute('one') . $name);
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/rob',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $req = $req->withAttribute("one", 1);
        $res = new Response();


        // Invoke app
        $resOut = $app($req, $res);
        $this->assertEquals('1rob', (string)$resOut->getBody());
    }

    public function testInvokeSubRequest(): void {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res->write('foo');

            return $res;
        });

        $newResponse = $subReq = $app->subRequest('GET', '/foo');

        $this->assertEquals('foo', (string)$subReq->getBody());
        $this->assertEquals(200, $newResponse->getStatusCode());
    }

    public function testInvokeSubRequestWithQuery(): void {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res->write("foo {$req->getParam('bar')}");

            return $res;
        });

        $subReq = $app->subRequest('GET', '/foo', 'bar=bar');

        $this->assertEquals('foo bar', (string)$subReq->getBody());
    }

    public function testInvokeSubRequestUsesResponseObject(): void {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res->write("foo {$req->getParam('bar')}");

            return $res;
        });

        $resp = new Response(201);
        $newResponse = $subReq = $app->subRequest('GET', '/foo', 'bar=bar', [], [], '', $resp);

        $this->assertEquals('foo bar', (string)$subReq->getBody());
        $this->assertEquals(201, $newResponse->getStatusCode());
    }

    // TODO: Test finalize()

    // TODO: Test run()
    public function testRun(): void {
        $app = $this->getAppForTestingRunMethod();

        ob_start();
        $app->run();
        $resOut = ob_get_clean();

        $this->assertEquals('bar', (string)$resOut);
    }

    public function testRunReturnsEmptyResponseBodyWithHeadRequestMethod(): void {
        $app = $this->getAppForTestingRunMethod('HEAD');

        ob_start();
        $app->run();
        $resOut = ob_get_clean();

        $this->assertEquals('', (string)$resOut);
    }

    public function testRunReturnsEmptyResponseBodyWithGetRequestMethodInSilentMode(): void {
        $app = $this->getAppForTestingRunMethod();
        $response = $app->run(true);

        $this->assertEquals('bar', $response->getBody()->__toString());
    }

    public function testRunReturnsEmptyResponseBodyWithHeadRequestMethodInSilentMode(): void {
        $app = $this->getAppForTestingRunMethod('HEAD');
        $response = $app->run(true);

        $this->assertEquals('', $response->getBody()->__toString());
    }

    private function getAppForTestingRunMethod($method = 'GET'): App {
        $app = new App();

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => $method,
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request($method, $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response(StatusCode::HTTP_OK, null, $body);
        $app->getContainer()['request'] = $req;
        $app->getContainer()['response'] = $res;

        $app->get('/foo', function ($req, $res) {
            $res->getBody()->write('bar');

            return $res;
        });

        return $app;
    }

    public function testRespond(): void {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $app->respond($resOut);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->expectOutputString('Hello');
    }

    public function testRespondWithHeaderNotSent(): void {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $app->respond($resOut);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->expectOutputString('Hello');
    }

    public function testRespondNoContent(): void {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            $res = $res->withStatus(204);
            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);

        $app->respond($resOut);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals([], $resOut->getHeader('Content-Type'));
        $this->assertEquals([], $resOut->getHeader('Content-Length'));
        $this->expectOutputString('');
    }

    public function testRespondWithPaddedStreamFilterOutput(): void {
        $availableFilter = stream_get_filters();

        if (PHP_VERSION_ID >= 70000) {
            $filterName           = 'string.rot13';
            $unfilterName         = 'string.rot13';
            $specificFilterName   = 'string.rot13';
            $specificUnfilterName = 'string.rot13';
        } else {
            $filterName           = 'mcrypt.*';
            $unfilterName         = 'mdecrypt.*';
            $specificFilterName   = 'mcrypt.rijndael-128';
            $specificUnfilterName = 'mdecrypt.rijndael-128';
        }

        if (in_array($filterName, $availableFilter) && in_array($unfilterName, $availableFilter)) {
            $app = new App();
            $app->get('/foo', function ($req, $res) use ($specificFilterName, $specificUnfilterName) {
                $key = base64_decode('xxxxxxxxxxxxxxxx');
                $iv = base64_decode('Z6wNDk9LogWI4HYlRu0mng==');

                $data = 'Hello';
                $length = strlen($data);

                $stream = fopen('php://temp', 'r+');

                $filter = stream_filter_append($stream, $specificFilterName, STREAM_FILTER_WRITE, [
                    'key' => $key,
                    'iv' => $iv
                ]);

                fwrite($stream, $data);
                rewind($stream);
                stream_filter_remove($filter);

                stream_filter_append($stream, $specificUnfilterName, STREAM_FILTER_READ, [
                    'key' => $key,
                    'iv' => $iv
                ]);

                return $res->withHeader('Content-Length', $length)->withBody(new Body($stream));
            });

            // Prepare request and response objects
            $env = Environment::mock([
                'SCRIPT_NAME' => '/index.php',
                'REQUEST_URI' => '/foo',
                'REQUEST_METHOD' => 'GET',
            ]);
            $uri = Uri::createFromEnvironment($env);
            $headers = Headers::createFromEnvironment($env);
            $cookies = [];
            $serverParams = $env->all();
            $body = new RequestBody();
            $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
            $res = new Response();

            // Invoke app
            $resOut = $app($req, $res);
            $app->respond($resOut);

            $this->assertInstanceOf(ResponseInterface::class, $resOut);
            $this->expectOutputString('Hello');
        } else {
            $this->assertTrue(true);
        }
    }

    public function testRespondIndeterminateLength(): void {
        $app = new App();
        $body_stream = fopen('php://temp', 'r+');
        $response = new Response();
        $body = $this->getMockBuilder(Body::class)
            ->setMethods(["getSize"])
            ->setConstructorArgs([$body_stream])
            ->getMock();
        fwrite($body_stream, "Hello");
        rewind($body_stream);
        $body->method("getSize")->willReturn(null);
        $response = $response->withBody($body);
        $app->respond($response);
        $this->expectOutputString("Hello");
    }

    public function testResponseWithStreamReadYieldingLessBytesThanAsked(): void {
        $app = new App([
            'settings' => ['responseChunkSize' => Mocks\SmallChunksStream::CHUNK_SIZE * 2]
        ]);
        $app->get('/foo', function ($req, $res) {
            $res->write('Hello');

            return $res;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new Mocks\SmallChunksStream();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = (new Response())->withBody($body);

        // Invoke app
        $resOut = $app($req, $res);

        $app->respond($resOut);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->expectOutputString(str_repeat('.', Mocks\SmallChunksStream::SIZE));
    }

    public function testResponseReplacesPreviouslySetHeaders(): void {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            return $res
                ->withHeader('X-Foo', 'baz1')
                ->withAddedHeader('X-Foo', 'baz2')
                ;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);
        $app->respond($resOut);

        $expectedStack = [
            ['header' => 'X-Foo: baz1', 'replace' => true, 'status_code' => null],
            ['header' => 'X-Foo: baz2', 'replace' => false, 'status_code' => null],
            ['header' => 'HTTP/1.1 200 OK', 'replace' => true, 'status_code' => 200],
        ];

        $this->assertSame($expectedStack, HeaderStack::stack());
    }

    public function testResponseDoesNotReplacePreviouslySetSetCookieHeaders(): void {
        $app = new App();
        $app->get('/foo', function ($req, $res) {
            return $res
                ->withHeader('Set-Cookie', 'foo=bar')
                ->withAddedHeader('Set-Cookie', 'bar=baz')
                ;
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke app
        $resOut = $app($req, $res);
        $app->respond($resOut);

        $expectedStack = [
            ['header' => 'Set-Cookie: foo=bar', 'replace' => false, 'status_code' => null],
            ['header' => 'Set-Cookie: bar=baz', 'replace' => false, 'status_code' => null],
            ['header' => 'HTTP/1.1 200 OK', 'replace' => true, 'status_code' => 200],
        ];

        $this->assertSame($expectedStack, HeaderStack::stack());
    }

    public function testExceptionErrorHandlerDoesNotDisplayErrorDetails(): void {
        $app = new App();

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();
        $app->getContainer()['request'] = $req;
        $app->getContainer()['response'] = $res;

        $mw = function ($req, $res, $next) {
            throw new Exception('middleware exception');
        };

        $app->add($mw);

        $app->get('/foo', function ($req, $res) {
            return $res;
        });

        $resOut = $app->run(true);

        $this->assertEquals(500, $resOut->getStatusCode());
        $this->assertNotRegExp('/.*middleware exception.*/', (string)$resOut);
    }

    /**
     * @requires PHP 7.0
     */
    public function testExceptionPhpErrorHandlerDoesNotDisplayErrorDetails(): void {
        $app = new App();

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();
        $app->getContainer()['request'] = $req;
        $app->getContainer()['response'] = $res;

        $mw = function ($req, $res, $next) {
            dumpFonction();
        };

        $app->add($mw);

        $app->get('/foo', function ($req, $res) {
            return $res;
        });

        $resOut = $app->run(true);

        $this->assertEquals(500, $resOut->getStatusCode());
        $this->assertNotRegExp('/.*middleware exception.*/', (string)$resOut);
    }

    /**
     * @return App
     */
    public function appFactory(): App {
        $app = new App();

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();
        $app->getContainer()['request'] = $req;
        $app->getContainer()['response'] = $res;

        return $app;
    }

    public function testRunExceptionNoHandler(): void {
        $app = $this->appFactory();

        $container = $app->getContainer();
        unset($container['errorHandler']);

        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            throw new Exception();
        });
        $this->expectException(Exception::class);
        $res = $app->run(true);
    }

    public function testRunSlimException(): void {
        $app = $this->appFactory();
        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            $res->write("Failed");
            throw new SlimException($req, $res);
        });
        $res = $app->run(true);

        $res->getBody()->rewind();
        $this->assertEquals(200, $res->getStatusCode());
        $this->assertEquals("Failed", $res->getBody()->getContents());
    }

    /**
     * @requires PHP 7.0
     */
    public function testRunThrowable(): void {
        $app = $this->appFactory();
        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            throw new Error('Failed');
        });

        $res = $app->run(true);

        $res->getBody()->rewind();

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame('text/html', $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), '<html>'));
    }

    public function testRunNotFound(): void {
        $app = $this->appFactory();
        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            throw new NotFoundException($req, $res);
        });
        $res = $app->run(true);

        $this->assertEquals(404, $res->getStatusCode());
    }

    public function testRunNotFoundWithoutHandler(): void {
        $app = $this->appFactory();
        $container = $app->getContainer();
        unset($container['notFoundHandler']);

        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            throw new NotFoundException($req, $res);
        });
        $this->expectException(NotFoundException::class);
        $res = $app->run(true);
    }


    public function testRunNotAllowed(): void {
        $app = $this->appFactory();
        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            throw new MethodNotAllowedException($req, $res, ['POST']);
        });
        $res = $app->run(true);

        $this->assertEquals(405, $res->getStatusCode());
    }

    public function testRunNotAllowedWithoutHandler(): void {
        $app = $this->appFactory();
        $container = $app->getContainer();
        unset($container['notAllowedHandler']);

        $app->get('/foo', function ($req, $res, $args) {
            return $res;
        });
        $app->add(function ($req, $res, $args) {
            throw new MethodNotAllowedException($req, $res, ['POST']);
        });
        $this->expectException(MethodNotAllowedException::class);
        $res = $app->run(true);
    }

    public function testAppRunWithDetermineRouteBeforeAppMiddleware(): void {
        $app = $this->appFactory();

        $app->get('/foo', function ($req, $res) {
            return $res->write("Test");
        });

        $app->getContainer()['settings']['determineRouteBeforeAppMiddleware'] = true;

        $resOut = $app->run(true);
        $resOut->getBody()->rewind();
        $this->assertEquals("Test", $resOut->getBody()->getContents());
    }

    public function testExceptionErrorHandlerDisplaysErrorDetails(): void {
        $app = new App([
            'settings' => [
                'displayErrorDetails' => true
            ],
        ]);

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new Body(fopen('php://temp', 'r+'));
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();
        $app->getContainer()['request'] = $req;
        $app->getContainer()['response'] = $res;

        $mw = function ($req, $res, $next) {
            throw new RuntimeException('middleware exception');
        };

        $app->add($mw);

        $app->get('/foo', function ($req, $res) {
            return $res;
        });

        $resOut = $app->run(true);

        $this->assertEquals(500, $resOut->getStatusCode());
        $this->assertRegExp('/.*middleware exception.*/', (string)$resOut);
    }

    public function testFinalize(): void {
        $method = new ReflectionMethod(App::class, 'finalize');
        $method->setAccessible(true);

        $response = new Response();
        $response->getBody()->write('foo');

        $response = $method->invoke(new App(), $response);

        $this->assertTrue($response->hasHeader('Content-Length'));
        $this->assertEquals('3', $response->getHeaderLine('Content-Length'));
    }

    public function testFinalizeWithoutBody(): void {
        $method = new ReflectionMethod(App::class, 'finalize');
        $method->setAccessible(true);

        $response = $method->invoke(new App(), new Response(304));

        $this->assertFalse($response->hasHeader('Content-Length'));
        $this->assertFalse($response->hasHeader('Content-Type'));
    }

    public function testCallingAContainerCallable(): void {
        $settings = [
            'foo' => function ($c) {
                return function ($a) {
                    return $a;
                };
            }
        ];
        $app = new App($settings);

        $result = $app->foo('bar');
        $this->assertSame('bar', $result);

        $headers = new Headers();
        $body = new Body(fopen('php://temp', 'r+'));
        $request = new Request('GET', Uri::createFromString(''), $headers, [], [], $body);
        $response = new Response();

        $response = $app->notFoundHandler($request, $response);

        $this->assertSame(404, $response->getStatusCode());
    }

    public function testCallingFromContainerNotCallable(): void {
        $settings = [
            'foo' => function ($c) {
                return null;
            }
        ];
        $app = new App($settings);
        $this->expectException(BadMethodCallException::class);
        $app->foo('bar');
    }

    public function testCallingAnUnknownContainerCallableThrows(): void {
        $app = new App();
        $this->expectException(BadMethodCallException::class);
        $app->foo('bar');
    }

    public function testCallingAnUncallableContainerKeyThrows(): void {
        $app = new App();
        $app->getContainer()['bar'] = 'foo';
        $this->expectException(BadMethodCallException::class);
        $app->foo('bar');
    }

    public function testOmittingContentLength(): void {
        $method = new ReflectionMethod(App::class, 'finalize');
        $method->setAccessible(true);

        $response = new Response();
        $response->getBody()->write('foo');

        $app = new App();
        $container = $app->getContainer();
        $container['settings']['addContentLengthHeader'] = false;
        $response = $method->invoke($app, $response);

        $this->assertFalse($response->hasHeader('Content-Length'));
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage Unexpected data in output buffer
     */
    public function testForUnexpectedDataInOutputBuffer(): void {
        $this->expectOutputString('test'); // needed to avoid risky test warning
        echo "test";
        $method = new ReflectionMethod(App::class, 'finalize');
        $method->setAccessible(true);

        $response = new Response();
        $response->getBody()->write('foo');

        $app = new App();
        $container = $app->getContainer();
        $container['settings']['addContentLengthHeader'] = true;
        $response = $method->invoke($app, $response);
    }

    public function testUnsupportedMethodWithoutRoute(): void {
        $app = new App();
        $c = $app->getContainer();
        $c['environment'] = Environment::mock(['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'BADMTHD']);

        $resOut = $app->run(true);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals(404, $resOut->getStatusCode());
    }

    public function testUnsupportedMethodWithRoute(): void {
        $app = new App();
        $app->get('/', function () {
            // stubbed action to give us a route at /
        });
        $c = $app->getContainer();
        $c['environment'] = Environment::mock(['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'BADMTHD']);

        $resOut = $app->run(true);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals(405, $resOut->getStatusCode());
    }

    public function testContainerSetToRoute(): void {
        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        $mock = new MockAction();

        $app = new App();
        $container = $app->getContainer();
        $container['foo'] = function () use ($mock, $res) {
            return $mock;
        };

        /** @var $router Router */
        $router = $container['router'];

        $router->map(['get'], '/foo', 'foo:bar');

        // Invoke app
        $resOut = $app($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals(json_encode(['name'=>'bar', 'arguments' => []]), (string)$res->getBody());
    }

    public function testIsEmptyResponseWithEmptyMethod(): void {
        $method = new ReflectionMethod(App::class, 'isEmptyResponse');
        $method->setAccessible(true);

        $response = new Response();
        $response = $response->withStatus(204);

        $result = $method->invoke(new App(), $response);
        $this->assertTrue($result);
    }

    public function testIsEmptyResponseWithoutEmptyMethod(): void {
        $method = new ReflectionMethod(App::class, 'isEmptyResponse');
        $method->setAccessible(true);

        /** @var Response $response */
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $response->method('getStatusCode')
            ->willReturn(204);

        $result = $method->invoke(new App(), $response);
        $this->assertTrue($result);
    }

    public function testIsHeadRequestWithGetRequest(): void {
        $method = new ReflectionMethod(App::class, 'isHeadRequest');
        $method->setAccessible(true);

        /** @var Request $request */
        $request = $this->getMockBuilder(RequestInterface::class)->getMock();
        $request->method('getMethod')
            ->willReturn('get');

        $result = $method->invoke(new App(), $request);
        $this->assertFalse($result);
    }

    public function testIsHeadRequestWithHeadRequest(): void {
        $method = new ReflectionMethod(App::class, 'isHeadRequest');
        $method->setAccessible(true);

        /** @var Request $request */
        $request = $this->getMockBuilder(RequestInterface::class)->getMock();
        $request->method('getMethod')
            ->willReturn('head');

        $result = $method->invoke(new App(), $request);
        $this->assertTrue($result);
    }

    public function testHandlePhpError(): void {
        $this->skipIfPhp70();
        $method = new ReflectionMethod(App::class, 'handlePhpError');
        $method->setAccessible(true);

        $throwable = $this->getMock(
            \Throwable::class,
            ['getCode', 'getMessage', 'getFile', 'getLine', 'getTraceAsString', 'getPrevious']
        );
        $req = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $res = new Response();

        $res = $method->invoke(new App(), $throwable, $req, $res);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame('text/html', $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), '<html>'));
    }

    public function testExceptionOutputBufferingOff(): void {
        $app = $this->appFactory();
        $app->getContainer()['settings']['outputBuffering'] = false;

        $app->get("/foo", function ($request, $response, $args) {
            $test = [1,2,3];
            var_dump($test);
            throw new Exception("oops");
        });

        $unExpectedOutput = <<<end
array(3) {
  [0] =>
  int(1)
  [1] =>
  int(2)
  [2] =>
  int(3)
}
end;

        $resOut = $app->run(true);
        $output = (string)$resOut->getBody();
        $strPos = strpos($output, $unExpectedOutput);
        $this->assertFalse($strPos);
    }

    public function testExceptionOutputBufferingAppend(): void {
        // If we are testing in HHVM skip this test due to a bug in HHVM
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('https://github.com/facebook/hhvm/issues/7803');
        }

        $app = $this->appFactory();
        $app->getContainer()['settings']['outputBuffering'] = 'append';
        $app->get("/foo", function ($request, $response, $args) {
            echo 'output buffer test';
            throw new Exception("oops");
        });

        $resOut = $app->run(true);
        $output = (string)$resOut->getBody();
        $this->assertStringEndsWith('output buffer test', $output);
    }

    public function testExceptionOutputBufferingPrepend(): void {
        // If we are testing in HHVM skip this test due to a bug in HHVM
        if (defined('HHVM_VERSION')) {
            $this->markTestSkipped('https://github.com/facebook/hhvm/issues/7803');
        }

        $app = $this->appFactory();
        $app->getContainer()['settings']['outputBuffering'] = 'prepend';
        $app->get("/foo", function ($request, $response, $args) {
            echo 'output buffer test';
            throw new Exception("oops");
        });

        $resOut = $app->run(true);
        $output = (string)$resOut->getBody();
        $this->assertStringStartsWith('output buffer test', $output);
    }

    public function testInvokeSequentialProccessToAPathWithOptionalArgsAndWithoutOptionalArgs(): void {
        $app = new App();
        $app->get('/foo[/{bar}]', function ($req, $res, $args) {
            return $res->write(count($args));
        });

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke process with optional arg
        $resOut = $app->process($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals('1', (string)$resOut->getBody());

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke process without optional arg
        $resOut2 = $app->process($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut2);
        $this->assertEquals('0', (string)$resOut2->getBody());
    }

    public function testInvokeSequentialProccessToAPathWithOptionalArgsAndWithoutOptionalArgsAndKeepSetedArgs(): void {
        $app = new App();
        $app->get('/foo[/{bar}]', function ($req, $res, $args) {
            return $res->write(count($args));
        })->setArgument('baz', 'quux');

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke process without optional arg
        $resOut = $app->process($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals('2', (string)$resOut->getBody());

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke process with optional arg
        $resOut2 = $app->process($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut2);
        $this->assertEquals('1', (string)$resOut2->getBody());
    }

    public function testInvokeSequentialProccessAfterAddingAnotherRouteArgument(): void {
        $app = new App();
        $route = $app->get('/foo[/{bar}]', function ($req, $res, $args) {
            return $res->write(count($args));
        })->setArgument('baz', 'quux');

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // Invoke process with optional arg
        $resOut = $app->process($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut);
        $this->assertEquals('2', (string)$resOut->getBody());

        // Prepare request and response objects
        $env = Environment::mock([
            'SCRIPT_NAME' => '/index.php',
            'REQUEST_URI' => '/foo/bar',
            'REQUEST_METHOD' => 'GET',
        ]);
        $uri = Uri::createFromEnvironment($env);
        $headers = Headers::createFromEnvironment($env);
        $cookies = [];
        $serverParams = $env->all();
        $body = new RequestBody();
        $req = new Request('GET', $uri, $headers, $cookies, $serverParams, $body);
        $res = new Response();

        // add another argument
        $route->setArgument('one', '1');

        // Invoke process with optional arg
        $resOut2 = $app->process($req, $res);

        $this->assertInstanceOf(ResponseInterface::class, $resOut2);
        $this->assertEquals('3', (string)$resOut2->getBody());
    }

    protected function skipIfPhp70(): void {
        if (PHP_VERSION_ID >= 70000) {
            $this->markTestSkipped();
        }
    }
}
