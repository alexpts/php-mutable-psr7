<?php

namespace PTS\Psr7\Stream;

use JetBrains\PhpStorm\Pure;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use function strlen;
use function substr;

class MemoryStream implements StreamInterface
{
    private string $buffer = '';
    protected int $position = 0;
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

        $len = strlen($buffer);
        $this->position = $len;
        $this->size = $len;
    }

    public function __toString(): string
    {
        if ($this->closed) {
            return '';
        }

        $this->seek(0);

        return $this->getContents();
    }

    public function close()
    {
        $this->buffer = '';
        $this->position = 0;
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
        if ($this->closed) {
            throw new RuntimeException('Unable to tell position of closed stream');
        }

        return $this->position;
    }

    public function eof()
    {
        return $this->position >= $this->getSize();
    }

    public function isSeekable()
    {
        return !$this->closed;
    }

    public function seek($offset, $whence = \SEEK_SET)
    {
        if ($this->closed) {
            throw new RuntimeException('Unable to seek on closed stream');
        }

        if ($whence === \SEEK_SET) {
            if ($this->getSize() < $offset) {
                throw new RuntimeException(
                    sprintf('Unable to seek to stream position %d with whence %d', $offset, var_export($whence, true))
                );
            }

            $this->position = $offset;
        } elseif ($whence === \SEEK_CUR) {
            $this->position += $offset;
        } elseif ($whence === \SEEK_END) {
            $this->position = strlen($this->buffer) + $offset;
        } else {
            throw new RuntimeException('Unable to seek to stream position ' .
                $offset .
                ' with whence ' .
                \var_export($whence, true));
        }
    }

    public function rewind()
    {
        $this->seek(0);
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
        if (!$this->isWritable()) {
            throw new RuntimeException('Cannot write to a non-writable stream');
        }

        if ($this->position > 0 && !isset($this->buffer[$this->position - 1])) {
            $this->buffer = \str_pad($this->buffer, $this->position, "\0");
        }

        $len = strlen($string);
        $this->buffer = substr($this->buffer, 0, $this->position) . $string . substr($this->buffer, $this->position + $len);
        $this->position += $len;
        $this->size = strlen($this->buffer);

        return $len;
    }

    public function isReadable()
    {
        return !$this->closed;
    }

    public function read($length)
    {
        if ($this->closed) {
            throw new RuntimeException('Unable to read from closed stream');
        }

        if ($length < 1) {
            throw new \InvalidArgumentException('Invalid read length given');
        }

        if ($this->position + $length > strlen($this->buffer)) {
            $length = strlen($this->buffer) - $this->position;
        }

        if (!isset($this->buffer[$this->position])) {
            return '';
        }

        $pos = $this->position;
        $this->position += $length;

        return substr($this->buffer, $pos, $length);
    }

    public function getContents()
    {
        if ($this->closed) {
            throw new RuntimeException('Unable to read from closed stream');
        }

        if (!isset($this->buffer[$this->position])) {
            return '';
        }

        $pos = $this->position;
        $this->position = strlen($this->buffer);

        return substr($this->buffer, $pos);
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