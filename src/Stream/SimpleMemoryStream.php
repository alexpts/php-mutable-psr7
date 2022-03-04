<?php

namespace PTS\Psr7\Stream;

use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\StreamInterface;
use function strlen;

/**
 * Experimental append-only stupid implementation
 */
class SimpleMemoryStream implements StreamInterface
{
    private string $buffer = '';
    protected ?int $size = null;
    protected bool $closed = false;

    #[Pure]
    public static function create($body = ''): StreamInterface
    {
        return new self($body);
    }

    /**
     * @param string $buffer
     */
    public function __construct(string $buffer)
    {
        $this->buffer = $buffer;
    }

    public function __toString(): string
    {
        return $this->buffer;
    }

    public function close()
    {
        $this->buffer = '';
        $this->closed = true;
    }

    public function detach()
    {
        $this->close();
        return null;
    }

    public function getSize()
    {
        if ($this->closed) {
            return null;
        }

        if ($this->size === null) {
            $this->size = strlen($this->buffer);
        }

        return $this->size;
    }

    public function tell()
    {
        return 0;
    }

    public function eof()
    {
        return false;
    }

    public function isSeekable()
    {
        return false;
    }

    public function seek($offset, $whence = \SEEK_SET)
    {
        // position always is 0
    }

    public function rewind()
    {
        // position always is 0
    }

    public function isWritable()
    {
        return !$this->closed;
    }

    /**
     * @param string $string
     * @return int - written
     */
    public function write($string)
    {
        $this->buffer .= $string;

        $len = strlen($string);
        $this->size = $len;

        return $len;
    }

    public function isReadable()
    {
        return !$this->closed;
    }

    public function read($length)
    {
        return $this->buffer;
    }

    public function getContents()
    {
        return $this->buffer;
    }

    /**
     * @param string $key
     * @return array|null
     */
    public function getMetadata($key = null)
    {
        return $key === null ? [] : null;
    }
}