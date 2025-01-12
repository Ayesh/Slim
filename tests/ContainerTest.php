<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Slim\Container;

class ContainerTest extends TestCase
{
    /**
     * @var Container
     */
    protected $container;

    public function setUp(): void
    {
        $this->container = new Container;
    }

    public function testGet()
    {
        $this->assertInstanceOf('\Slim\Http\Environment', $this->container->get('environment'));
    }

    public function testGetWithValueNotFoundError()
    {
        $this->expectException(\Slim\Exception\ContainerValueNotFoundException::class);
        $this->container->get('foo');
    }

    /**
     * Test `get()` throws something that is a ContainerException - typically a NotFoundException, when there is a DI
     * config error
     */
    public function testGetWithDiConfigErrorThrownAsContainerValueNotFoundException()
    {
        $container = new Container;
        $container['foo'] =
            function (ContainerInterface $container) {
                return $container->get('doesnt-exist');
            }
        ;
        $this->expectException(\Slim\Exception\ContainerValueNotFoundException::class);
        $container->get('foo');
    }

    /**
     * Test `get()` recasts InvalidArgumentException as psr/container exceptions when an error is present
     * in the DI config
     */
    public function testGetWithDiConfigErrorThrownAsInvalidArgumentException()
    {
        $container = new Container;
        $container['foo'] =
            function (ContainerInterface $container) {
                return $container['doesnt-exist'];
            }
        ;
        $this->expectException(\Slim\Exception\ContainerException::class);
        $container->get('foo');
    }

    /**
     * Test `get()` does not recast exceptions which are thrown in a factory closure
     */
    public function testGetWithErrorThrownByFactoryClosure()
    {
        $invokable = $this->getMockBuilder('StdClass')->setMethods(['__invoke'])->getMock();
        /** @var callable $invokable */
        $invokable->expects($this->any())
            ->method('__invoke')
            ->will($this->throwException(new InvalidArgumentException()));

        $container = new Container;
        $container['foo'] =
            function (ContainerInterface $container) use ($invokable) {
                call_user_func($invokable);
            }
        ;
        $this->expectException(InvalidArgumentException::class);
        $container->get('foo');
    }

    public function testGetRequest()
    {
        $this->assertInstanceOf('\Psr\Http\Message\RequestInterface', $this->container['request']);
    }

    public function testGetResponse()
    {
        $this->assertInstanceOf('\Psr\Http\Message\ResponseInterface', $this->container['response']);
    }

    public function testGetRouter()
    {
        $this->assertInstanceOf('\Slim\Router', $this->container['router']);
    }

    public function testGetErrorHandler()
    {
        $this->assertInstanceOf('\Slim\Handlers\Error', $this->container['errorHandler']);
    }

    public function testGetNotAllowedHandler()
    {
        $this->assertInstanceOf('\Slim\Handlers\NotAllowed', $this->container['notAllowedHandler']);
    }

    public function testSettingsCanBeEdited()
    {
        $this->assertSame('1.1', $this->container->get('settings')['httpVersion']);

        $this->container->get('settings')['httpVersion'] = '1.2';
        $this->assertSame('1.2', $this->container->get('settings')['httpVersion']);
    }

    public function testMagicIssetMethod()
    {
        $this->assertEquals(true, $this->container->__isset('settings'));
    }

    public function testMagicGetMethod()
    {
        $this->container->get('settings')['httpVersion'] = '1.2';
        $this->assertSame('1.2', $this->container->__get('settings')['httpVersion']);
    }

    public function testRouteCacheDisabledByDefault()
    {
        $this->assertFalse($this->container->get('settings')['routerCacheFile']);
    }
}
