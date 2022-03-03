<?php
declare(strict_types=1);

namespace PTS\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use function clearstatcache;
use function fclose;
use function feof;
use function fopen;
use function fread;
use function fseek;
use function fstat;
use function ftell;
use function fwrite;
use function is_resource;
use function is_string;
use function stream_get_contents;
use function stream_get_meta_data;
use const SEEK_SET;

class Stream implements StreamInterface
{
    /** @var resource|null A resource reference */
    protected $stream = null;
    protected bool $seekable = false;
    protected bool $readable = false;
    protected bool $writable = false;
    /** @var array|mixed|void|null */
    protected $uri;
    /** @var int|null */
    protected ?int $size = null;

    protected const READ_WRITE_HASH = [
        'read' => [
            'r' => true,
            'w+' => true,
            'r+' => true,
            'x+' => true,
            'c+' => true,
            'rb' => true,
            'w+b' => true,
            'r+b' => true,
            'x+b' => true,
            'c+b' => true,
            'rt' => true,
            'w+t' => true,
            'r+t' => true,
            'x+t' => true,
            'c+t' => true,
            'a+' => true,
        ],
        'write' => [
            'w' => true,
            'w+' => true,
            'rw' => true,
            'r+' => true,
            'x+' => true,
            'c+' => true,
            'wb' => true,
            'w+b' => true,
            'r+b' => true,
            'x+b' => true,
            'c+b' => true,
            'w+t' => true,
            'r+t' => true,
            'x+t' => true,
            'c+t' => true,
            'a' => true,
            'a+' => true,
        ],
    ];

    /**
     * Creates a new PSR-7 stream.
     *
     * @param string|resource|StreamInterface $body
     *
     * @return StreamInterface
     * @throws InvalidArgumentException
     */
    public static function create($body = ''): StreamInterface
    {
        if ($body instanceof StreamInterface) {
            return $body;
        }

        if (is_string($body)) {
            $resource = fopen('php://temp', 'rw+');
            fwrite($resource, $body);
            $body = $resource;
        }

        if (is_resource($body)) {
            $new = new self();
            $new->stream = $body;
            $meta = stream_get_meta_data($new->stream);
            $new->seekable = $meta['seekable'] && 0 === \fseek($new->stream, 0, \SEEK_CUR);
            $new->readable = isset(self::READ_WRITE_HASH['read'][$meta['mode']]);
            $new->writable = isset(self::READ_WRITE_HASH['write'][$meta['mode']]);
            $new->uri = $new->getMetadata('uri');

            return $new;
        }

        throw new InvalidArgumentException('First argument to Stream::create() must be a string, resource or StreamInterface.');
    }

    /**
     * Closes the stream when the destructed.
     */
    public function __destruct()
    {
        $this->close();
    }

    public function __toString(): string
    {
        if ($this->isSeekable()) {
            $this->seek(0);
        }

        return $this->getContents();
    }

    public function close(): void
    {
        if (isset($this->stream) && is_resource($this->stream)) {
            fclose($this->stream);
            $this->detach();
        }
    }

    public function detach()
    {
        if ($this->stream === null) {
            return null;
        }

        $result = $this->stream;
        $this->stream = null;

        $this->size = $this->uri = null;
        $this->readable = $this->writable = $this->seekable = false;

        return $result;
    }

    public function getSize(): ?int
    {
        if (null !== $this->size) {
            return $this->size;
        }

        if (!isset($this->stream)) {
            return null;
        }

        // Clear the stat cache if the stream has a URI
        if ($this->uri) {
            clearstatcache(true, $this->uri);
        }

        $stats = fstat($this->stream);
        if (isset($stats['size'])) {
            $this->size = $stats['size'];
            return $this->size;
        }

        return null;
    }

    public function tell(): int
    {
        $position = $this->stream === null ? false : ftell($this->stream);
        if (false === $position) {
            throw new RuntimeException('Unable to determine stream position');
        }

        return $position;
    }

    public function eof(): bool
    {
        return !$this->stream || feof($this->stream);
    }

    public function isSeekable(): bool
    {
        return $this->seekable;
    }

    public function seek($offset, $whence = SEEK_SET): void
    {
        if (!$this->seekable) {
            throw new RuntimeException('Stream is not seekable');
        }

        if (-1 === fseek($this->stream, $offset, $whence)) {
            throw new RuntimeException('Unable to seek to stream position ' .
                $offset .
                ' with whence ' .
                \var_export($whence, true));
        }
    }

    public function rewind(): void
    {
        $this->seek(0);
    }

    public function isWritable(): bool
    {
        return $this->writable;
    }

    public function write($string): int
    {
        if (!$this->writable) {
            throw new RuntimeException('Cannot write to a non-writable stream');
        }

        // We can't know the size after writing anything
        $this->size = null;

        if (false === $result = fwrite($this->stream, $string)) {
            throw new RuntimeException('Unable to write to stream');
        }

        return $result;
    }

    public function isReadable(): bool
    {
        return $this->readable;
    }

    public function read($length): string
    {
        if (!$this->readable) {
            throw new RuntimeException('Cannot read from non-readable stream');
        }

        $result = @fread($this->stream, $length);
        if (false === $result) {
            throw new RuntimeException('Unable to read from stream');
        }

        return $result;
    }

    public function getContents(): string
    {
        if (!isset($this->stream)) {
            throw new RuntimeException('Stream is detached');
        }

        if (false === $contents = @stream_get_contents($this->stream)) {
            throw new RuntimeException('Unable to read stream contents');
        }

        return $contents;
    }

    /**
     * @param string|null $key
     * @return mixed
     */
    public function getMetadata($key = null)
    {
        if (!isset($this->stream)) {
            return $key ? null : [];
        }

        $meta = stream_get_meta_data($this->stream);

        if (null === $key) {
            return $meta;
        }

        return $meta[$key] ?? null;
    }
}