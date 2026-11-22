<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Http;

use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_TestCase;
use RuntimeException;
use Slim\Http\Stream;

class StreamTest extends TestCase
{
    /**
     * @var resource pipe stream file handle
     */
    private $pipeFh;

    /**
     * @var Stream
     */
    private $pipeStream;

    public function tearDown(): void
    {
        if ($this->pipeFh != null) {
            stream_get_contents($this->pipeFh); // prevent broken pipe error message
        }
    }

    public function testIsPipe(): void {
        $this->openPipeStream();

        $this->assertTrue($this->pipeStream->isPipe());

        $this->pipeStream->detach();
        $this->assertFalse($this->pipeStream->isPipe());

        $fhFile = fopen(__FILE__, 'r');
        $fileStream = new Stream($fhFile);
        $this->assertFalse($fileStream->isPipe());
    }

    public function testIsPipeReadable(): void {
        $this->openPipeStream();

        $this->assertTrue($this->pipeStream->isReadable());
    }

    public function testPipeIsNotSeekable(): void {
        $this->openPipeStream();

        $this->assertFalse($this->pipeStream->isSeekable());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCannotSeekPipe(): void {
        $this->openPipeStream();

        $this->pipeStream->seek(0);
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCannotTellPipe(): void {
        $this->openPipeStream();

        $this->pipeStream->tell();
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCannotRewindPipe(): void {
        $this->openPipeStream();

        $this->pipeStream->rewind();
    }

    public function testPipeGetSizeYieldsNull(): void {
        $this->openPipeStream();

        $this->assertNull($this->pipeStream->getSize());
    }

    public function testClosePipe(): void {
        $this->openPipeStream();

        stream_get_contents($this->pipeFh); // prevent broken pipe error message
        $this->pipeStream->close();
        $this->pipeFh = null;

        $this->assertFalse($this->pipeStream->isPipe());
    }

    public function testPipeToString(): void {
        $this->openPipeStream();

        $this->assertSame('', (string) $this->pipeStream);
    }

    public function testPipeGetContents(): void {
        $this->openPipeStream();

        $contents = trim($this->pipeStream->getContents());
        $this->assertSame('12', $contents);
    }

    /**
     * Opens the pipe stream
     *
     * @see StreamTest::pipeStream
     */
    private function openPipeStream(): void {
        $this->pipeFh = popen('echo 12', 'r');
        $this->pipeStream = new Stream($this->pipeFh);
    }
}
