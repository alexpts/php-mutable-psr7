<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use PTS\Psr7\Stream;
use PTS\Psr7\Stream\MemoryStream;

class StreamTest extends TestCase
{

    public function streamProvider(): array
    {
        return [
            'stream' => [[Stream::class, 'create']],
            'memory' => [[MemoryStream::class, 'create']],
        ];
    }


    public function testStreamConstructorInitializesProperties(): void
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


    /**
     * @param callable $streamFactory
     * @return void
     *
     * @dataProvider streamProvider
     */
    public function testConstructorInitializesProperties(callable $streamFactory): void
    {
        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');

        static::assertTrue($stream->isReadable());
        static::assertTrue($stream->isWritable());
        static::assertTrue($stream->isSeekable());
        static::assertIsArray($stream->getMetadata());
        static::assertSame(4, $stream->getSize());
        static::assertSame(4, $stream->tell());
        static::assertSame('', $stream->getContents());
        static::assertTrue($stream->eof());
    }

    /**
     * @param callable $streamFactory
     * @return void
     *
     * @dataProvider streamProvider
     */
    public function testConvertsToString(callable $streamFactory): void
    {
        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');

        static::assertSame('data', (string)$stream);
        static::assertSame('data', (string)$stream);
        $stream->close();
    }

    /**
     * @param callable $streamFactory
     * @return void
     *
     * @dataProvider streamProvider
     */
    public function testGetsContents(callable $streamFactory): void
    {
        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');
        $stream->seek(0);

        static::assertSame('data', $stream->getContents());
        static::assertSame('', $stream->getContents());
    }

    /**
     * @param callable $streamFactory
     * @return void
     *
     * @dataProvider streamProvider
     */
    public function testChecksEof(callable $streamFactory): void
    {
        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');

        $stream->seek(0);
        static::assertFalse($stream->eof());
        $stream->read(5);
        static::assertTrue($stream->eof());
        $stream->close();
    }

    /**
     * @param callable $streamFactory
     * @return void
     *
     * @dataProvider streamProvider
     */
    public function testGetSize(callable $streamFactory): void
    {
        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');

        static::assertSame(4, $stream->getSize());
        // Load from cache
        static::assertSame(4, $stream->getSize());
        $stream->close();
    }

    /**
     * @param callable $streamFactory
     * @return void
     *
     * @dataProvider streamProvider
     */
    public function testEnsuresSizeIsConsistent(callable $streamFactory): void
    {
        /** @var StreamInterface $stream */
        $stream = $streamFactory('foo');

        static::assertSame(3, $stream->getSize());
        static::assertSame(3, $stream->tell());
        static::assertSame(4, $stream->write('test'));
        static::assertSame(7, $stream->getSize());
        static::assertSame(7, $stream->tell());
        $stream->close();
    }

    /**
     * @param callable $streamFactory
     * @return void
     *
     * @dataProvider streamProvider
     */
    public function testProvidesStreamPosition(callable $streamFactory): void
    {
        /** @var StreamInterface $stream */
        $stream = $streamFactory('');

        static::assertSame(0, $stream->tell());
        $stream->write('foo');
        static::assertSame(3, $stream->tell());
        $stream->seek(1);
        static::assertSame(1, $stream->tell());
        $stream->close();
    }

    public function testStreamCanDetachStream(): void
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

    /**
     * @param callable $streamFactory
     * @return void
     *
     * @dataProvider streamProvider
     */
    public function testCloseClearProperties(callable $streamFactory): void
    {
        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');
        $stream->close();

        static::assertFalse($stream->isSeekable());
        static::assertFalse($stream->isReadable());
        static::assertFalse($stream->isWritable());
        static::assertNull($stream->getSize());
        static::assertEmpty($stream->getMetadata());
    }

    public function testStreamUnSeekableStreamWrapper(): void
    {
        stream_wrapper_register('stream-psr7-test', TestStreamWrapper::class);
        $handle = fopen('stream-psr7-test://', 'r');
        stream_wrapper_unregister('stream-psr7-test');

        $stream = Stream::create($handle);
        static::assertFalse($stream->isSeekable());
    }

    public function testStreamBodyNotString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First argument to Stream::create() must be a string, resource or StreamInterface');
        Stream::create(1234);
    }

    /**
     * @param callable $streamFactory
     * @return void
     *
     * @dataProvider streamProvider
     */
    public function testRewind(callable $streamFactory): void
    {
        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');

        static::assertSame('', $stream->getContents());

        $stream->rewind();
        static::assertSame('data', $stream->getContents());
    }

    /**
     * @param callable $streamFactory
     * @return void
     *
     * @dataProvider streamProvider
     */
    public function testSeekToMoreMax(callable $streamFactory): void
    {
        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');

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