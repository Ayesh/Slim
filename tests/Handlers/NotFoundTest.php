<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Handlers;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Slim\Handlers\NotFound;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;

class NotFoundTest extends TestCase
{
    public function notFoundProvider()
    {
        return [
            ['application/json', 'application/json', '{'],
            ['application/vnd.api+json', 'application/json', '{'],
            ['application/xml', 'application/xml', '<root>'],
            ['application/hal+xml', 'application/xml', '<root>'],
            ['text/xml', 'text/xml', '<root>'],
            ['text/html', 'text/html', '<html>'],
        ];
    }

    /**
     * Test invalid method returns the correct code and content type
     *
     * @dataProvider notFoundProvider
     */
    public function testNotFound($acceptHeader, $contentType, $startOfBody)
    {
        $notAllowed = new NotFound();

        /** @var Response $res */
        $res = $notAllowed->__invoke($this->getRequest('GET', $acceptHeader), new Response(), ['POST', 'PUT']);

        $this->assertSame(404, $res->getStatusCode());
        $this->assertSame($contentType, $res->getHeaderLine('Content-Type'));
        $this->assertEquals(0, strpos((string)$res->getBody(), $startOfBody));
    }

    public function testNotFoundContentType()
    {
        $errorMock = $this->getMockBuilder(NotFound::class)->setMethods(['determineContentType'])->getMock();
        $errorMock->method('determineContentType')
            ->willReturn('unknown/type');

        $req = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();

        $this->expectException(\UnexpectedValueException::class);
        $errorMock->__invoke($req, new Response(), ['POST']);
    }

    /**
     * @param string $method
     *
     * @return MockObject|Request
     */
    protected function getRequest($method, $contentType = 'text/html')
    {
        $uri = new Uri('http', 'example.com', 80, '/notfound');

        $req = $this->getMockBuilder(Request::class)->disableOriginalConstructor()->getMock();
        $req->expects($this->once())->method('getHeaderLine')->willReturn($contentType);
        $req->expects($this->any())->method('getUri')->willReturn($uri);

        return $req;
    }
}
