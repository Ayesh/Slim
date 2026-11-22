<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use RuntimeException;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Response;

class ResponseTest extends TestCase
{
    use \Slim\Tests\Assets\ReadsAttributes;

    public function testConstructorWithDefaultArgs(): void {
        $response = new Response();

        $this->assertEquals(200, $this->readAttribute($response, 'status'));
        $this->assertInstanceOf(Headers::class, $this->readAttribute($response, 'headers'));
        $this->assertInstanceOf('\Psr\Http\Message\StreamInterface', $this->readAttribute($response, 'body'));
    }

    public function testConstructorWithCustomArgs(): void {
        $headers = new Headers();
        $body = new Body(fopen('php://temp', 'r+'));
        $response = new Response(404, $headers, $body);

        $this->assertEquals(404, $this->readAttribute($response, 'status'));
        $this->assertSame($headers, $this->readAttribute($response, 'headers'));
        $this->assertSame($body, $this->readAttribute($response, 'body'));
    }

    public function testDeepCopyClone(): void {
        $headers = new Headers();
        $body = new Body(fopen('php://temp', 'r+'));
        $response = new Response(404, $headers, $body);
        $clone = clone $response;

        $this->assertEquals('1.1', $this->readAttribute($clone, 'protocolVersion'));
        $this->assertEquals(404, $this->readAttribute($clone, 'status'));
        $this->assertNotSame($headers, $this->readAttribute($clone, 'headers'));
        $this->assertSame($body, $this->readAttribute($clone, 'body'));
    }

    public function testDisableSetter(): void {
        $response = new Response();
        $response->foo = 'bar';

        $this->assertFalse(property_exists($response, 'foo'));
    }

    public function testGetStatusCode(): void {
        $response = new Response();
        $responseStatus = new ReflectionProperty($response, 'status');
        $responseStatus->setValue($response, '404');

        $this->assertEquals(404, $response->getStatusCode());
    }

    public function testWithStatus(): void {
        $response = new Response();
        $clone = $response->withStatus(302);

        $this->assertEquals(302, $this->readAttribute($clone, 'status'));
    }

    public function testWithStatusInvalidStatusCodeThrowsException(): void {
        $this->expectException(\InvalidArgumentException::class);

        $response = new Response();
        $response->withStatus(800);
    }

    public function testWithStatusInvalidReasonPhraseThrowsException(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ReasonPhrase must be a string');

        $response = new Response();
        $response->withStatus(200, null);
    }

    public function testWithStatusEmptyReasonPhrase(): void {
        $responseWithNoMessage = new Response(310);

        $this->assertEquals('', $responseWithNoMessage->getReasonPhrase());
    }

    public function testGetReasonPhrase(): void {
        $response = new Response(404);

        $this->assertEquals('Not Found', $response->getReasonPhrase());
    }

    public function testMustSetReasonPhraseForUnrecognisedCode(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('ReasonPhrase must be supplied for this code');

        $response = new Response();
        $response = $response->withStatus(199);
    }

    public function testSetReasonPhraseForUnrecognisedCode(): void {
        $response = new Response();
        $response = $response->withStatus(199, 'Random Message');

        $this->assertEquals('Random Message', $response->getReasonPhrase());
    }

    public function testGetCustomReasonPhrase(): void {
        $response = new Response();
        $clone = $response->withStatus(200, 'Custom Phrase');

        $this->assertEquals('Custom Phrase', $clone->getReasonPhrase());
    }

    public function testWithRedirect(): void {
        $response = new Response(200);
        $clone = $response->withRedirect('/foo', 301);
        $cloneWithDefaultStatus = $response->withRedirect('/foo');
        $cloneWithStatusMethod = $response->withStatus(301)->withRedirect('/foo');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertFalse($response->hasHeader('Location'));

        $this->assertSame(301, $clone->getStatusCode());
        $this->assertTrue($clone->hasHeader('Location'));
        $this->assertEquals('/foo', $clone->getHeaderLine('Location'));

        $this->assertSame(302, $cloneWithDefaultStatus->getStatusCode());
        $this->assertTrue($cloneWithDefaultStatus->hasHeader('Location'));
        $this->assertEquals('/foo', $cloneWithDefaultStatus->getHeaderLine('Location'));

        $this->assertSame(301, $cloneWithStatusMethod->getStatusCode());
        $this->assertTrue($cloneWithStatusMethod->hasHeader('Location'));
        $this->assertEquals('/foo', $cloneWithStatusMethod->getHeaderLine('Location'));
    }

    public function testIsEmpty(): void {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setValue($response, 204);

        $this->assertTrue($response->isEmpty());
    }

    public function testIsInformational(): void {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setValue($response, 100);

        $this->assertTrue($response->isInformational());
    }

    public function testIsOk(): void {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setValue($response, 200);

        $this->assertTrue($response->isOk());
    }

    public function testIsSuccessful(): void {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setValue($response, 201);

        $this->assertTrue($response->isSuccessful());
    }

    public function testIsRedirect(): void {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setValue($response, 302);

        $this->assertTrue($response->isRedirect());
    }

    public function testIsRedirection(): void {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setValue($response, 308);

        $this->assertTrue($response->isRedirection());
    }

    public function testIsForbidden(): void {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setValue($response, 403);

        $this->assertTrue($response->isForbidden());
    }

    public function testIsNotFound(): void {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setValue($response, 404);

        $this->assertTrue($response->isNotFound());
    }

    public function testIsBadRequest(): void {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setValue($response, 400);

        $this->assertTrue($response->isBadRequest());
    }

    public function testIsClientError(): void {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setValue($response, 400);

        $this->assertTrue($response->isClientError());
    }

    public function testIsServerError(): void {
        $response = new Response();
        $prop = new ReflectionProperty($response, 'status');
        $prop->setValue($response, 503);

        $this->assertTrue($response->isServerError());
    }

    public function testToString(): void {
        $output = 'HTTP/1.1 404 Not Found' . Response::EOL .
                  'X-Foo: Bar' . Response::EOL . Response::EOL .
                  'Where am I?';
        $this->expectOutputString($output);
        $response = new Response();
        $response = $response->withStatus(404)->withHeader('X-Foo', 'Bar')->write('Where am I?');

        echo $response;
    }

    public function testWithJson(): void {
        $data = ['foo' => 'bar1&bar2'];

        $originalResponse = new Response();
        $response = $originalResponse->withJson($data, 201);

        $this->assertNotEquals($response->getStatusCode(), $originalResponse->getStatusCode());
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('application/json', $response->getHeaderLine('Content-Type'));

        $body = $response->getBody();
        $body->rewind();
        $dataJson = $body->getContents(); //json_decode($body->getContents(), true);

        $originalBody = $originalResponse->getBody();
        $originalBody->rewind();
        $originalContents = $originalBody->getContents();

        // test the original body hasn't be replaced
        $this->assertNotEquals($dataJson, $originalContents);

        $this->assertEquals('{"foo":"bar1&bar2"}', $dataJson);
        $this->assertEquals($data['foo'], json_decode($dataJson, true)['foo']);

        // Test encoding option
        $response = $response->withJson($data, 200, JSON_HEX_AMP);

        $body = $response->getBody();
        $body->rewind();
        $dataJson = $body->getContents();

        $this->assertEquals('{"foo":"bar1\u0026bar2"}', $dataJson);
        $this->assertEquals($data['foo'], json_decode($dataJson, true)['foo']);

        $response = $response->withStatus(201)->withJson([]);
        $this->assertEquals($response->getStatusCode(), 201);
    }

    public function testWithInvalidJsonThrowsException(): void {
        $this->expectException(\RuntimeException::class);

        $data = ['foo' => 'bar'.chr(233)];
        $this->assertEquals('bar'.chr(233), $data['foo']);

        $response = new Response();
        $response->withJson($data, 200);

        // Safety net: this assertion should not occur, since the RuntimeException
        // must have been caught earlier by the test framework
        $this->assertFalse(true);
    }

    public function testStatusIsSetTo302IfLocationIsSetWhenStatusis200(): void {
        $response = new Response();
        $response = $response->withHeader('Location', '/foo');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testStatusIsNotSetTo302IfLocationIsSetWhenStatusisNot200(): void {
        $response = new Response();
        $response = $response->withStatus(201)->withHeader('Location', '/foo');

        $this->assertSame(201, $response->getStatusCode());
    }
}
