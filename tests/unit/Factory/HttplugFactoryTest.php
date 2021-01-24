<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use PTS\Psr7\Factory\HttplugFactory;
use PTS\Psr7\Uri;

class HttplugFactoryTest extends TestCase
{
    public function testCreateRequest(): void
    {
        $factory = new HttplugFactory;
        $r = $factory->createRequest('POST', 'https://site.io', ['Content-Type' => 'text/html'], 'foobar', '2.0');

        static::assertSame('POST', $r->getMethod());
        static::assertSame('https://site.io', $r->getUri()->__toString());
        static::assertSame('2.0', $r->getProtocolVersion());
        static::assertSame('foobar', $r->getBody()->__toString());

        $headers = $r->getHeaders();
        static::assertCount(2, $headers); // Including HOST
        static::assertArrayHasKey('content-type', $headers);
        static::assertSame('text/html', $headers['content-type'][0]);
    }

    public function testCreateResponse(): void
    {
        $factory = new HttplugFactory;
        $r = $factory->createResponse(217, 'Perfect', ['Content-Type' => 'text/html'], 'foobar', '2.0');

        static::assertSame(217, $r->getStatusCode());
        static::assertSame('Perfect', $r->getReasonPhrase());
        static::assertSame('2.0', $r->getProtocolVersion());
        static::assertSame('foobar', $r->getBody()->__toString());

        $headers = $r->getHeaders();
        static::assertCount(1, $headers);
        static::assertArrayHasKey('content-type', $headers);
        static::assertSame('text/html', $headers['content-type'][0]);
    }

    public function testCreateStream(): void
    {
        $factory = new HttplugFactory;
        $stream = $factory->createStream('foobar');

        static::assertInstanceOf(StreamInterface::class, $stream);
        static::assertSame('foobar', $stream->__toString());
    }

    public function testCreateUri(): void
    {
        $factory = new HttplugFactory;
        $uri = $factory->createUri('https://site.io/foo');

        static::assertInstanceOf(UriInterface::class, $uri);
        static::assertSame('https://site.io/foo', $uri->__toString());

        $uri = $factory->createUri(new Uri('https://site.io'));
        static::assertInstanceOf(UriInterface::class, $uri);
        static::assertSame('https://site.io', $uri->__toString());
    }
}