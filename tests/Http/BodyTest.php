<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Http;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_TestCase;
use ReflectionProperty;
use Slim\Http\Body;

class BodyTest extends TestCase
{
    /**
     * @var string
     */
    // @codingStandardsIgnoreStart
    protected $text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.';
    // @codingStandardsIgnoreEnd
    /**
     * @var resource
     */
    protected $stream;

    protected function tearDown(): void
    {
        if (is_resource($this->stream) === true) {
            fclose($this->stream);
        }
    }

    /**
     * This method creates a new resource, and it seeds
     * the resource with lorem ipsum text. The returned
     * resource is readable, writable, and seekable.
     *
     * @param string $mode
     *
     * @return resource
     */
    public function resourceFactory($mode = 'r+')
    {
        $stream = fopen('php://temp', $mode);
        fwrite($stream, $this->text);
        rewind($stream);

        return $stream;
    }

    public function testConstructorAttachesStream(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $bodyStream = new ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);

        $this->assertSame($this->stream, $bodyStream->getValue($body));
    }

    public function testConstructorInvalidStream(): void {
        $this->stream = 'foo';
        $this->expectException(InvalidArgumentException::class);
        $body = new Body($this->stream);
    }

    public function testGetMetadata(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);

        $this->assertInternalType('array', $body->getMetadata());
    }

    public function testGetMetadataKey(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);

        $this->assertEquals('php://temp', $body->getMetadata('uri'));
    }

    public function testGetMetadataKeyNotFound(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);

        $this->assertNull($body->getMetadata('foo'));
    }

    public function testDetach(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);

        $bodyStream = new ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);

        $bodyMetadata = new ReflectionProperty($body, 'meta');
        $bodyMetadata->setAccessible(true);

        $bodyReadable = new ReflectionProperty($body, 'readable');
        $bodyReadable->setAccessible(true);

        $bodyWritable = new ReflectionProperty($body, 'writable');
        $bodyWritable->setAccessible(true);

        $bodySeekable = new ReflectionProperty($body, 'seekable');
        $bodySeekable->setAccessible(true);

        $result = $body->detach();

        $this->assertSame($this->stream, $result);
        $this->assertNull($bodyStream->getValue($body));
        $this->assertNull($bodyMetadata->getValue($body));
        $this->assertNull($bodyReadable->getValue($body));
        $this->assertNull($bodyWritable->getValue($body));
        $this->assertNull($bodySeekable->getValue($body));
    }

    public function testToStringAttached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);

        $this->assertEquals($this->text, (string)$body);
    }

    public function testToStringAttachedRewindsFirst(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);

        $this->assertEquals($this->text, (string)$body);
        $this->assertEquals($this->text, (string)$body);
        $this->assertEquals($this->text, (string)$body);
    }

    public function testToStringDetached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $bodyStream = new ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($body, null);

        $this->assertEquals('', (string)$body);
    }

    public function testClose(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $body->close();

        $this->assertAttributeEquals(null, 'stream', $body);
        //$this->assertFalse($body->isAttached()); #1269
    }

    public function testGetSizeAttached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);

        $this->assertEquals(mb_strlen($this->text), $body->getSize());
    }

    public function testGetSizeDetached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $bodyStream = new ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($body, null);

        $this->assertNull($body->getSize());
    }

    public function testTellAttached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        fseek($this->stream, 10);

        $this->assertEquals(10, $body->tell());
    }

    public function testTellDetachedThrowsRuntimeException(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $bodyStream = new ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($body, null);

        $this->setExpectedException(\RuntimeException::class);
        $body->tell();
    }

    public function testEofAttachedFalse(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        fseek($this->stream, 10);

        $this->assertFalse($body->eof());
    }

    public function testEofAttachedTrue(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        while (feof($this->stream) === false) {
            fread($this->stream, 1024);
        }

        $this->assertTrue($body->eof());
    }

    public function testEofDetached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $bodyStream = new ReflectionProperty($body, 'stream');
        $bodyStream->setAccessible(true);
        $bodyStream->setValue($body, null);

        $this->assertTrue($body->eof());
    }

    public function isReadableAttachedTrue(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);

        $this->assertTrue($body->isReadable());
    }

    public function isReadableAttachedFalse(): void {
        $stream = fopen('php://temp', 'w');
        $body = new Body($this->stream);

        $this->assertFalse($body->isReadable());
        fclose($stream);
    }

    public function testIsReadableDetached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $body->detach();

        $this->assertFalse($body->isReadable());
    }

    public function isWritableAttachedTrue(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);

        $this->assertTrue($body->isWritable());
    }

    public function isWritableAttachedFalse(): void {
        $stream = fopen('php://temp', 'r');
        $body = new Body($this->stream);

        $this->assertFalse($body->isWritable());
        fclose($stream);
    }

    public function testIsWritableDetached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $body->detach();

        $this->assertFalse($body->isWritable());
    }

    public function isSeekableAttachedTrue(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);

        $this->assertTrue($body->isSeekable());
    }

    // TODO: Is seekable is false when attached... how?

    public function testIsSeekableDetached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $body->detach();

        $this->assertFalse($body->isSeekable());
    }

    public function testSeekAttached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $body->seek(10);

        $this->assertEquals(10, ftell($this->stream));
    }

    public function testSeekDetachedThrowsRuntimeException(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $body->detach();

        $this->setExpectedException(\RuntimeException::class);
        $body->seek(10);
    }

    public function testRewindAttached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        fseek($this->stream, 10);
        $body->rewind();

        $this->assertEquals(0, ftell($this->stream));
    }

    public function testRewindDetachedThrowsRuntimeException(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $body->detach();

        $this->setExpectedException(\RuntimeException::class);
        $body->rewind();
    }

    public function testReadAttached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);

        $this->assertEquals(substr($this->text, 0, 10), $body->read(10));
    }

    public function testReadDetachedThrowsRuntimeException(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $body->detach();

        $this->setExpectedException(\RuntimeException::class);
        $body->read(10);
    }

    public function testWriteAttached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        while (feof($this->stream) === false) {
            fread($this->stream, 1024);
        }
        $body->write('foo');

        $this->assertEquals($this->text . 'foo', (string)$body);
    }

    public function testWriteDetachedThrowsRuntimeException(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $body->detach();

        $this->setExpectedException(\RuntimeException::class);
        $body->write('foo');
    }

    public function testGetContentsAttached(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        fseek($this->stream, 10);

        $this->assertEquals(substr($this->text, 10), $body->getContents());
    }

    public function testGetContentsDetachedThrowsRuntimeException(): void {
        $this->stream = $this->resourceFactory();
        $body = new Body($this->stream);
        $body->detach();

        $this->setExpectedException(\RuntimeException::class);
        $body->getContents();
    }
}
