<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PTS\Psr7\Factory\Psr17Factory;
use PTS\Psr7\Stream;

class Psr17FactoryTest extends TestCase
{
    public function testCreateResponse(): void
    {
        $factory = new Psr17Factory;
        $r = $factory->createResponse(200);
        static::assertSame('OK', $r->getReasonPhrase());

        $r = $factory->createResponse(200, '');
        static::assertSame('', $r->getReasonPhrase());

        $r = $factory->createResponse(200, 'Foo');
        static::assertSame('Foo', $r->getReasonPhrase());

        /*
         * Test for non-standard response codes
         */
        $r = $factory->createResponse(567);
        static::assertSame('', $r->getReasonPhrase());

        $r = $factory->createResponse(567, '');
        static::assertSame(567, $r->getStatusCode());
        static::assertSame('', $r->getReasonPhrase());

        $r = $factory->createResponse(567, 'Foo');
        static::assertSame(567, $r->getStatusCode());
        static::assertSame('Foo', $r->getReasonPhrase());
    }

    public function testCreateRequest(): void
    {
        $factory = new Psr17Factory;
        $request = $factory->createRequest('GET', '/');
        static::assertSame('GET', $request->getMethod());
        static::assertSame('/', (string)$request->getUri());
    }

    public function testCreateStreamFromFile(): void
    {
        $factory = new Psr17Factory;
        $stream = $factory->createStreamFromFile(dirname(__DIR__) . '/Resources/foo.txt');

        static::assertSame("Foobar\n", (string)$stream);
    }

    public function testCreateStreamFromBadFile(): void
    {
        $factory = new Psr17Factory;

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('The file bad.txt cannot be opened.');
        $factory->createStreamFromFile('bad.txt');
    }

    public function testCreateStreamFromBadMode(): void
    {
        $factory = new Psr17Factory;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The mode ud is invalid.');
        $factory->createStreamFromFile(dirname(__DIR__) . '/Resources/foo.txt', 'ud');
    }

    public function testCreateStreamFromResource(): void
    {
        $factory = new Psr17Factory;
        $resource = @fopen(dirname(__DIR__) . '/Resources/foo.txt', 'r+');

        $stream = $factory->createStreamFromResource($resource);
        static::assertSame("Foobar\n", (string)$stream);
    }

    public function testCreateUploadedFile(): void
    {
        $factory = new Psr17Factory;
        $body = 'file_content';
        $stream = Stream::create($body);
        $fileUploaded = $factory->createUploadedFile($stream);

        self::assertSame(strlen($body), $fileUploaded->getSize());
    }

    public function testCreateServerRequest(): void
    {
        $factory = new Psr17Factory;
        $request = $factory->createServerRequest('GET', '/');
        static::assertSame('GET', $request->getMethod());
        static::assertSame('/', (string)$request->getUri());
    }
}