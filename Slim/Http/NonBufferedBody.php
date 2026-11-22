<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @license https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */

namespace Slim\Http;

use Psr\Http\Message\StreamInterface;

/**
 * Represents a non-readable stream that whenever it is written pushes
 * the data back to the browser immediately.
 */
class NonBufferedBody implements StreamInterface
{
    /**
     * {@inheritdoc}
     */
    public function __toString()
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void {
    }

    /**
     * {@inheritdoc}
     */
    public function detach(): null {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSize(): ?int {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function tell(): int {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function eof(): bool {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function isSeekable(): bool {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function seek($offset, $whence = SEEK_SET): void {
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): void {
    }

    /**
     * {@inheritdoc}
     */
    public function isWritable(): bool {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function write($string): int {
        $buffered = '';
        while (0 < ob_get_level()) {
            $buffered = ob_get_clean() . $buffered;
        }

        echo $buffered . $string;

        flush();

        return strlen($string) + strlen($buffered);
    }

    /**
     * {@inheritdoc}
     */
    public function isReadable(): bool {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function read($length): string {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getContents(): string {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($key = null): mixed {
        return null;
    }
}
