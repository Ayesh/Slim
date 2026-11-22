<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Tests\Mocks;

use Exception;
use Psr\Http\Message\StreamInterface;

class SmallChunksStream implements StreamInterface
{
    public const CHUNK_SIZE = 10;
    public const SIZE = 40;

    /**
     * @var int
     */
    private $amountToRead;

    public function __construct()
    {
        $this->amountToRead = self::SIZE;
    }

    /**
     * @throws Exception
     */
    public function __toString()
    {
        throw new Exception('not implemented');
    }

    public function close(): void {
    }

    /**
     * @throws Exception
     */
    public function detach(): void {
        throw new Exception('not implemented');
    }

    public function eof(): bool {
        return $this->amountToRead === 0;
    }

    /**
     * @throws Exception
     */
    public function getContents(): void {
        throw new Exception('not implemented');
    }

    /**
     * @param string $key
     *
     * @throws Exception
     */
    public function getMetadata($key = null): void {
        throw new Exception('not implemented');
    }

    public function getSize(): int {
        return self::SIZE;
    }

    public function isReadable(): bool {
        return true;
    }

    public function isSeekable(): bool {
        return false;
    }

    public function isWritable(): bool {
        return false;
    }

    public function read($length): string {
        $size = min($this->amountToRead, self::CHUNK_SIZE, $length);
        $this->amountToRead -= $size;

        return str_repeat('.', min($length, $size));
    }

    /**
     * @throws Exception
     */
    public function rewind(): void {
        throw new Exception('not implemented');
    }

    /**
     * @throws Exception
     */
    public function seek($offset, $whence = SEEK_SET): void {
        throw new Exception('not implemented');
    }

    /**
     * @throws Exception
     */
    public function tell(): void {
        throw new Exception('not implemented');
    }

    public function write($string): int {
        return strlen($string);
    }
}
