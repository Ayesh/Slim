<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Slim\CallableResolver;
use Slim\Container;
use Slim\Tests\Mocks\CallableTest;
use Slim\Tests\Mocks\InvokableTest;

class CallableResolverTest extends TestCase
{
    /**
     * @var Container
     */
    private $container;

    public function setUp(): void
    {
        CallableTest::$CalledCount = 0;
        InvokableTest::$CalledCount = 0;
        $this->container = new Container();
    }

    public function testClosure(): void
    {
        $test = function () {
            static $called_count = 0;
            return $called_count++;
        };
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve($test);
        $callable();
        $this->assertEquals(1, $callable());
    }

    public function testFunctionName(): void
    {
        // @codingStandardsIgnoreStart
        function testCallable()
        {
            static $called_count = 0;
            return $called_count++;
        };
        // @codingStandardsIgnoreEnd

        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve(__NAMESPACE__ . '\testCallable');
        $callable();
        $this->assertEquals(1, $callable());
    }

    public function testObjMethodArray(): void
    {
        $obj = new CallableTest();
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve([$obj, 'toCall']);
        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testSlimCallable(): void
    {
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve('Slim\Tests\Mocks\CallableTest:toCall');
        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testSlimCallableContainer(): void
    {
        $resolver = new CallableResolver($this->container);
        $resolver->resolve('Slim\Tests\Mocks\CallableTest:toCall');
        $this->assertEquals($this->container, CallableTest::$CalledContainer);
    }

    public function testContainer(): void
    {
        $this->container['callable_service'] = new CallableTest();
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve('callable_service:toCall');
        $callable();
        $this->assertEquals(1, CallableTest::$CalledCount);
    }

    public function testResolutionToAnInvokableClassInContainer(): void
    {
        $this->container['an_invokable'] = function ($c) {
            return new InvokableTest();
        };
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve('an_invokable');
        $callable();
        $this->assertEquals(1, InvokableTest::$CalledCount);
    }

    public function testResolutionToAnInvokableClass(): void {
        $resolver = new CallableResolver($this->container);
        $callable = $resolver->resolve(InvokableTest::class);
        $callable();
        $this->assertEquals(1, InvokableTest::$CalledCount);
    }

    public function testMethodNotFoundThrowException(): void
    {
        $this->container['callable_service'] = new CallableTest();
        $resolver = new CallableResolver($this->container);
        $this->expectException(RuntimeException::class);
        $resolver->resolve('callable_service:noFound');
    }

    public function testFunctionNotFoundThrowException(): void
    {
        $resolver = new CallableResolver($this->container);
        $this->expectException(RuntimeException::class);
        $resolver->resolve('noFound');
    }

    public function testClassNotFoundThrowException(): void
    {
        $resolver = new CallableResolver($this->container);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Callable Unknown does not exist');
        $resolver->resolve('Unknown:notFound');
    }

    public function testCallableClassNotFoundThrowException(): void
    {
        $resolver = new CallableResolver($this->container);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not resolvable');
        $resolver->resolve(['Unknown', 'notFound']);
    }

    public function testCallableInvalidTypeThrowException(): void
    {
        $resolver = new CallableResolver($this->container);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('is not resolvable');
        $resolver->resolve(__LINE__);
    }
}
