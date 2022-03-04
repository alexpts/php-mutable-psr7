<?php
declare(strict_types=1);

namespace PTS\Test\Psr7\bench\Stream;

use PhpBench\Attributes\ParamProviders;
use PhpBench\Benchmark\Metadata\Annotations\Assert;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\OutputTimeUnit;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Subject;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use Psr\Http\Message\StreamInterface;
use PTS\Psr7\Stream;
use PTS\Psr7\Stream\MemoryStream;
use PTS\Psr7\Stream\SimpleMemoryStream;

class StreamBench
{
    public function streamProvider(): array
    {
        return [
            'stream' => [[Stream::class, 'create']],
            'memory' => [[MemoryStream::class, 'create']],
            'stupid-memory' => [[SimpleMemoryStream::class, 'create']],
        ];
    }

    /**
     * @Subject()
     * @Revs(30)
     * @Iterations(4)
     * @OutputTimeUnit("microseconds", precision=3)
     * @Warmup(1)
     *
     * @Assert("mode(variant.time.avg) < 2 microseconds")
     *
     * @ParamProviders("streamProvider")
     */
    public function create(array $params): void
    {
        $streamFactory = $params[0];

        /** @var StreamInterface $stream */
        $streamFactory('data');
    }

    /**
     * @Subject()
     * @Revs(30)
     * @Iterations(4)
     * @OutputTimeUnit("microseconds", precision=3)
     * @Warmup(1)
     *
     * @Assert("mode(variant.time.avg) < 2 microseconds")
     *
     * @ParamProviders("streamProvider")
     */
    public function getContents(array $params): void
    {
        $streamFactory = $params[0];

        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');
        $stream->getContents();
    }

    /**
     * @Subject()
     * @Revs(30)
     * @Iterations(4)
     * @OutputTimeUnit("microseconds", precision=3)
     * @Warmup(1)
     *
     * @Assert("mode(variant.time.avg) < 2 microseconds")
     *
     * @ParamProviders("streamProvider")
     */
    public function read(array $params): void
    {
        $streamFactory = $params[0];

        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');
        $stream->read(3);
    }

    /**
     * @Subject()
     * @Revs(30)
     * @Iterations(4)
     * @OutputTimeUnit("microseconds", precision=3)
     * @Warmup(1)
     *
     * @Assert("mode(variant.time.avg) < 2 microseconds")
     *
     * @ParamProviders("streamProvider")
     */
    public function write(array $params): void
    {
        $streamFactory = $params[0];

        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');
        $stream->write('hello world');
    }

    /**
     * @Subject()
     * @Revs(30)
     * @Iterations(4)
     * @OutputTimeUnit("microseconds", precision=3)
     * @Warmup(1)
     *
     * @Assert("mode(variant.time.avg) < 2 microseconds")
     *
     * @ParamProviders("streamProvider")
     */
    public function doubleRead(array $params): void
    {
        $streamFactory = $params[0];

        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');
        $stream->write('hello world');
        $stream->getContents();
        $stream->rewind();
        $stream->getContents();
    }

    /**
     * @Subject()
     * @Revs(30)
     * @Iterations(4)
     * @OutputTimeUnit("microseconds", precision=3)
     * @Warmup(1)
     *
     * @Assert("mode(variant.time.avg) < 2 microseconds")
     *
     * @ParamProviders("streamProvider")
     */
    public function doubleWrite(array $params): void
    {
        $streamFactory = $params[0];

        /** @var StreamInterface $stream */
        $stream = $streamFactory('data');
        $stream->write('hello world');
        $stream->getContents();

        $stream->write('hello world');
        $stream->rewind();
        $stream->getContents();

        $stream->detach();
    }

    /**
     * @Subject()
     * @Revs(30)
     * @Iterations(4)
     * @OutputTimeUnit("microseconds", precision=3)
     * @Warmup(1)
     *
     * @Assert("mode(variant.time.avg) < 2 microseconds")
     *
     * @ParamProviders("streamProvider")
     */
    public function writeToMiddle(array $params): void
    {
        $streamFactory = $params[0];
        /** @var StreamInterface $stream */
        $stream = $streamFactory('');
        $stream->write('hello world');
        $stream->seek(6);

        $stream->write('alex');
        $stream->rewind();
        $stream->getContents();
    }
}