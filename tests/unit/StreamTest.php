<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PTS\Psr7\Stream;

class StreamTest extends TestCase
{

    public function testConstructorInitializesProperties(): void
    {
        $handle = fopen('php://temp', 'rb+');
        fwrite($handle, 'data');
        $stream = Stream::create($handle);
        static::assertTrue($stream->isReadable());
        static::assertTrue($stream->isWritable());
        static::assertTrue($stream->isSeekable());
        static::assertSame('php://temp', $stream->getMetadata('uri'));
        static::assertIsArray($stream->getMetadata());
        static::assertSame(4, $stream->getSize());
        static::assertFalse($stream->eof());
        $stream->close();
    }

    public function testStreamClosesHandleOnDestruct(): void
    {
        $handle = fopen('php://temp', 'r');
        $stream = Stream::create($handle);
        unset($stream);
        static::assertFalse(is_resource($handle));
    }

    public function testConvertsToString(): void
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = Stream::create($handle);
        static::assertSame('data', (string)$stream);
        static::assertSame('data', (string)$stream);
        $stream->close();
    }

    public function testGetsContents(): void
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = Stream::create($handle);
        static::assertSame('', $stream->getContents());
        $stream->seek(0);
        static::assertSame('data', $stream->getContents());
        static::assertSame('', $stream->getContents());
    }

    public function testChecksEof(): void
    {
        $handle = fopen('php://temp', 'w+');
        fwrite($handle, 'data');
        $stream = Stream::create($handle);
        static::assertFalse($stream->eof());
        $stream->read(4);
        static::assertTrue($stream->eof());
        $stream->close();
    }

    public function testGetSize(): void
    {
        $size = filesize(__FILE__);
        $handle = fopen(__FILE__, 'r');
        $stream = Stream::create($handle);
        static::assertSame($size, $stream->getSize());
        // Load from cache
        static::assertSame($size, $stream->getSize());
        $stream->close();
    }

    public function testEnsuresSizeIsConsistent(): void
    {
        $h = fopen('php://temp', 'w+');
        static::assertSame(3, fwrite($h, 'foo'));
        $stream = Stream::create($h);
        static::assertSame(3, $stream->getSize());
        static::assertSame(4, $stream->write('test'));
        static::assertSame(7, $stream->getSize());
        static::assertSame(7, $stream->getSize());
        $stream->close();
    }

    public function testProvidesStreamPosition(): void
    {
        $handle = fopen('php://temp', 'w+');
        $stream = Stream::create($handle);
        static::assertSame(0, $stream->tell());
        $stream->write('foo');
        static::assertSame(3, $stream->tell());
        $stream->seek(1);
        static::assertSame(1, $stream->tell());
        static::assertSame(ftell($handle), $stream->tell());
        $stream->close();
    }

    public function testCanDetachStream(): void
    {
        $r = fopen('php://temp', 'w+');
        $stream = Stream::create($r);
        $stream->write('foo');
        static::assertTrue($stream->isReadable());
        static::assertSame($r, $stream->detach());
        $stream->detach();

        static::assertFalse($stream->isReadable());
        static::assertFalse($stream->isWritable());
        static::assertFalse($stream->isSeekable());

        $throws = function (callable $fn) use ($stream) {
            try {
                $fn($stream);
                $this->fail();
            } catch (\Exception $e) {
                // Suppress the exception
            }
        };

        $throws(function ($stream) {
            $stream->read(10);
        });
        $throws(function ($stream) {
            $stream->write('bar');
        });
        $throws(function ($stream) {
            $stream->seek(10);
        });
        $throws(function ($stream) {
            $stream->tell();
        });
        $throws(function ($stream) {
            $stream->eof();
        });
        $throws(function ($stream) {
            $stream->getSize();
        });
        $throws(fn($stream) => $stream->getContents());

        if (\PHP_VERSION_ID >= 70400) {
            $throws(fn($stream) => (string)$stream);
        } else {
            static::assertSame('', (string)$stream);
        }

        $stream->close();
    }

    public function testCloseClearProperties(): void
    {
        $handle = fopen('php://temp', 'r+');
        $stream = Stream::create($handle);
        $stream->close();

        static::assertFalse($stream->isSeekable());
        static::assertFalse($stream->isReadable());
        static::assertFalse($stream->isWritable());
        static::assertNull($stream->getSize());
        static::assertEmpty($stream->getMetadata());
    }

    public function testUnSeekableStreamWrapper(): void
    {
        stream_wrapper_register('stream-psr7-test', TestStreamWrapper::class);
        $handle = fopen('stream-psr7-test://', 'r');
        stream_wrapper_unregister('stream-psr7-test');

        $stream = Stream::create($handle);
        static::assertFalse($stream->isSeekable());
    }

    public function testBodyNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First argument to Stream::create() must be a string, resource or StreamInterface');
        Stream::create(1234);
    }

    public function testRewind(): void
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'data');
        $stream = Stream::create($handle);

        static::assertSame('', $stream->getContents());

        $stream->rewind();
        static::assertSame('data', $stream->getContents());
    }

    public function testSeekToMoreMax(): void
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, 'data');
        $stream = Stream::create($handle);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to seek to stream position 999 with whence 0');
        $stream->seek(999);
    }

    public function testWriteToReadOnlyStream(): void
    {
        $handle = fopen('php://temp', 'r');
        $stream = Stream::create($handle);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cannot write to a non-writable stream');
        $stream->write('try write to only read stream');
    }
}

class TestStreamWrapper
{
    public $context;

    public function stream_open()
    {
        return true;
    }

    public function stream_seek(int $offset, int $whence = SEEK_SET)
    {
        return false;
    }

    public function stream_eof()
    {
        return true;
    }
}