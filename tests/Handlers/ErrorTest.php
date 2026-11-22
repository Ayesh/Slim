<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Handlers;

use Exception;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use RuntimeException;
use Slim\Handlers\Error;
use Slim\Http\Request;
use Slim\Http\Response;
use UnexpectedValueException;

class ErrorTest extends TestCase
{
    public static function errorProvider(): array {
        return [
            ['application/json', 'application/json', '{'],
            ['application/vnd.api+json', 'application/json', '{'],
            ['application/xml', 'application/xml', '<error>'],
            ['application/hal+xml', 'application/xml', '<error>'],
            ['text/xml', 'text/xml', '<error>'],
            ['text/html', 'text/html', '<html>'],
        ];
    }

    /**
     * Test invalid method returns the correct code and content type
     */
    #[DataProvider('errorProvider')]
    public function testError($acceptHeader, $contentType, $startOfBody): void {
        $error = new Error();
        $e = new Exception("Oops", 1, new Exception('Previous oops'));

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $e);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    /**
     * Test invalid method returns the correct code and content type with details
     */
    #[DataProvider('errorProvider')]
    public function testErrorDisplayDetails($acceptHeader, $contentType, $startOfBody): void {
        $error = new Error(true);
        $e = new Exception('Oops', 1, new Exception('Opps before'));

        /** @var Response $res */
        $res = $error->__invoke($this->getRequest('GET', $acceptHeader), new Response(), $e);

        $this->assertSame(500, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    public function testNotFoundContentType(): void {
        $errorMock = $this->getMockBuilder(Error::class)->onlyMethods(['determineContentType'])->getMock();
        $errorMock->expects($this->once())
            ->method('determineContentType')
            ->willReturn('unknown/type');

        $e = new Exception("Oops");

        $req = $this->createStub(Request::class);
        $this->expectException(UnexpectedValueException::class);
        $errorMock->__invoke($req, new Response(), $e);
    }

    /**
     * Test that an exception with a previous exception provides correct output
     * to the error log
     */
    public function testPreviousException(): void {
        $error = $this->getMockBuilder(Error::class)->onlyMethods(['logError'])->getMock();
        $error->expects($this->once())->method('logError')->with(
            $this->logicalAnd(
                $this->stringContains("Type: Exception" . PHP_EOL . "Message: Second Oops"),
                $this->stringContains("Previous Error:" . PHP_EOL . "Type: Exception" . PHP_EOL . "Message: First Oops")
            )
        );

        $first = new Exception("First Oops");
        $second = new Exception("Second Oops", 0, $first);

        $error->__invoke($this->getRequest('GET', 'application/json'), new Response(), $second);
    }

    /**
     * If someone extends the Error handler and calls renderHtmlExceptionOrError with
     * a parameter that isn't an Exception or Error, then we thrown an Exception.
     */
    public function testRenderHtmlExceptionOrErrorTypeChecksParameter(): void {
        $class = new ReflectionClass(Error::class);
        $renderHtmlExceptionorError = $class->getMethod('renderHtmlExceptionOrError');

        $this->expectException(RuntimeException::class);

        $error = new Error();
        $renderHtmlExceptionorError->invokeArgs($error, ['foo']);
    }

    /**
     * @param string $method
     *
     * @return MockObject|Request
     */
    protected function getRequest($method, $acceptHeader)
    {
        $req = $this->createMock(Request::class);
        $req->expects($this->once())->method('getHeaderLine')->willReturn($acceptHeader);

        return $req;
    }
}
