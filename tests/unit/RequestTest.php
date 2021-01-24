<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use PTS\Psr7\Request;
use PTS\Psr7\Uri;

class RequestTest extends TestCase
{
    public function testCanConstructWithBody(): void
    {
        $uri = new Uri('/');
        $request = new Request('GET', $uri, [], 'baz');
        static::assertInstanceOf(StreamInterface::class, $request->getBody());
        static::assertEquals('baz', (string)$request->getBody());
    }

    public function testNullBody(): void
    {
        $uri = new Uri('/');
        $request = new Request('GET', $uri, [], null);
        static::assertInstanceOf(StreamInterface::class, $request->getBody());
        static::assertSame('', (string)$request->getBody());
    }

    public function testFalseyBody(): void
    {
        $uri = new Uri('/');
        $request = new Request('GET', $uri, [], '0');
        static::assertInstanceOf(StreamInterface::class, $request->getBody());
        static::assertSame('0', (string)$request->getBody());
    }

    public function testConstructorDoesNotReadStreamBody(): void
    {
        $body = $this->getMockBuilder(StreamInterface::class)->getMock();
        $body->expects($this->never())
            ->method('__toString');

        $uri = new Uri('/');
        $r = new Request('GET', $uri, [], $body);
        static::assertSame($body, $r->getBody());
    }

    public function testWithUri(): void
    {
        $request = new Request('GET', new Uri('/'));
        $uri = new Uri('http://www.example.com');

        $request->withUri($uri);
        static::assertSame($uri, $request->getUri()); // mutable library
    }

    public function testWithRequestTarget(): void
    {
        $uri = new Uri('/');
        $request = new Request('GET', $uri);
        static::assertEquals('/', $request->getRequestTarget());

        $request->withRequestTarget('*');
        static::assertEquals('*', $request->getRequestTarget());
    }

    public function testWithInvalidRequestTarget(): void
    {
        $uri = new Uri('/');
        $request = new Request('GET', $uri);
        $this->expectException(InvalidArgumentException::class);
        $request->withRequestTarget('foo bar');
    }

    public function testGetRequestTarget(): void
    {
        $uri = new Uri('https://some.io');
        $request = new Request('GET', $uri);
        static::assertEquals('/', $request->getRequestTarget());

        $request = new Request('GET', new Uri('https://some.io/foo?bar=baz'));
        static::assertEquals('/foo?bar=baz', $request->getRequestTarget());

        $request = new Request('GET', new Uri('https://some.io?bar=baz'));
        static::assertEquals('/?bar=baz', $request->getRequestTarget());
    }

    public function testRequestTargetDoesNotAllowSpaces(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid request target provided; cannot contain whitespace');

        $r1 = new Request('GET', new Uri('/'));
        $r1->withRequestTarget('/foo bar');
    }

    public function testRequestTargetDefaultsToSlash(): void
    {
        $request = new Request('GET', new Uri(''));
        static::assertEquals('/', $request->getRequestTarget());

        $request = new Request('GET', new Uri('*'));
        static::assertEquals('*', $request->getRequestTarget());

        $request = new Request('GET', new Uri('http://foo.com/bar baz/'));
        static::assertEquals('/bar%20baz/', $request->getRequestTarget());
    }

    public function testBuildsRequestTarget(): void
    {
        $request = new Request('GET', new Uri('http://foo.com/baz?bar=bam'));
        static::assertEquals('/baz?bar=bam', $request->getRequestTarget());
    }

    public function testBuildsRequestTargetWithFalseyQuery(): void
    {
        $request = new Request('GET', new Uri('http://foo.com/baz?0'));
        static::assertEquals('/baz?0', $request->getRequestTarget());
    }

    public function testHostIsAddedFirst(): void
    {
        $r = new Request('GET', new Uri('http://foo.com/baz?bar=bam'), ['Foo' => 'Bar']);
        static::assertEquals([
            'host' => ['foo.com'],
            'foo' => ['Bar'],
        ], $r->getHeaders());
    }

    public function testCanGetHeaderAsCsv(): void
    {
        $request = new Request('GET', new Uri('http://foo.com/baz?bar=bam'), [
            'Foo' => ['a', 'b', 'c'],
        ]);
        static::assertEquals('a, b, c', $request->getHeaderLine('Foo'));
        static::assertEquals('', $request->getHeaderLine('Bar'));
    }

    public function testHostIsNotOverwrittenWhenPreservingHost(): void
    {
        $request = new Request('GET', new Uri('http://foo.com/baz?bar=bam'), ['Host' => 'a.com']);
        static::assertEquals(['host' => ['a.com']], $request->getHeaders());

        $request->withUri(new Uri('http://www.foo.com/bar'), true);
        static::assertEquals('a.com', $request->getHeaderLine('Host'));
    }

    public function testOverridesHostWithUri(): void
    {
        $request = new Request('GET', new Uri('http://foo.com/baz?bar=bam'));
        static::assertEquals(['host' => ['foo.com']], $request->getHeaders());

        $request->withUri(new Uri('http://www.baz.com/bar'));
        static::assertEquals('www.baz.com', $request->getHeaderLine('Host'));
    }

    public function testAggregatesHeaders(): void
    {
        $request = new Request('GET', new Uri(''), [
            'ZOO' => 'zoobar',
            'zoo' => ['foobar', 'zoobar'],
        ]);
        static::assertEquals(['zoo' => ['zoobar', 'foobar', 'zoobar']], $request->getHeaders());
        static::assertEquals('zoobar, foobar, zoobar', $request->getHeaderLine('zoo'));
    }

    public function testSupportNumericHeaders(): void
    {
        $request = new Request('GET', new Uri(''), [
            'Content-Length' => 200,
        ]);
        static::assertSame(['content-length' => ['200']], $request->getHeaders());
        static::assertSame('200', $request->getHeaderLine('Content-Length'));
    }

    public function testSupportNumericHeaderNames(): void
    {
        $request = new Request(
            'GET', new Uri(''), [
                '200' => 'NumericHeaderValue',
                '0' => 'NumericHeaderValueZero',
            ]
        );

        static::assertSame(
            [
                '200' => ['NumericHeaderValue'],
                '0' => ['NumericHeaderValueZero'],
            ],
            $request->getHeaders()
        );

        static::assertSame(['NumericHeaderValue'], $request->getHeader('200'));
        static::assertSame('NumericHeaderValue', $request->getHeaderLine('200'));

        static::assertSame(['NumericHeaderValueZero'], $request->getHeader('0'));
        static::assertSame('NumericHeaderValueZero', $request->getHeaderLine('0'));

        $request->withHeader('300', 'NumericHeaderValue2')
            ->withAddedHeader('200', ['A', 'B']);

        static::assertSame(
            [
                '200' => ['NumericHeaderValue', 'A', 'B'],
                '0' => ['NumericHeaderValueZero'],
                '300' => ['NumericHeaderValue2'],
            ],
            $request->getHeaders()
        );

        $request->withoutHeader('300');
        static::assertSame(
            [
                '200' => ['NumericHeaderValue', 'A', 'B'],
                '0' => ['NumericHeaderValueZero'],
            ],
            $request->getHeaders()
        );
    }

    public function testAddsPortToHeader(): void
    {
        $request = new Request('GET', new Uri('http://foo.com:8124/bar'));
        static::assertEquals('foo.com:8124', $request->getHeaderLine('host'));
    }

    public function testAddsPortToHeaderAndReplacePreviousPort(): void
    {
        $request = new Request('GET', new Uri('http://foo.com:8124/bar'));
        $request->withUri(new Uri('http://foo.com:8125/bar'));
        static::assertEquals('foo.com:8125', $request->getHeaderLine('host'));
    }

    public function testCannotHaveHeaderWithEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header name must be an RFC 7230 compatible string.');
        $request = new Request('GET', new Uri('https://example.com/'));
        $request->withHeader('', 'Bar');
    }

    public function testCanHaveHeaderWithEmptyValue(): void
    {
        $request = new Request('GET', new Uri('https://example.com/'));
        $request = $request->withHeader('Foo', '');
        static::assertEquals([''], $request->getHeader('Foo'));
    }

    public function testUpdateHostFromUri(): void
    {
        $request = new Request('GET', new Uri('/'));
        $request = $request->withUri(new Uri('https://some.io'));
        static::assertEquals('some.io', $request->getHeaderLine('Host'));

        $request = new Request('GET', new Uri('https://example.com/'));
        static::assertEquals('example.com', $request->getHeaderLine('Host'));
        $request = $request->withUri(new Uri('https://some.io'));
        static::assertEquals('some.io', $request->getHeaderLine('Host'));

        $request = new Request('GET', new Uri('/'));
        $request = $request->withUri(new Uri('https://some.io:8080'));
        static::assertEquals('some.io:8080', $request->getHeaderLine('Host'));

        $request = new Request('GET', new Uri('/'));
        $request = $request->withUri(new Uri('https://some.io:443'));
        static::assertEquals('some.io', $request->getHeaderLine('Host'));
    }

    public function testWithSameUri(): void
    {
        $uri = new Uri('/');
        $request = new Request('GET', $uri);
        $request->withUri($uri);

        static::assertSame($uri, $request->getUri());
    }

    public function testWithMethod(): void
    {
        $uri = new Uri('/');
        $request = new Request('GET', $uri);
        static::assertSame('GET', $request->getMethod());

        $request->withMethod('POST');
        static::assertSame('POST', $request->getMethod());
    }

    public function testReset(): void
    {
        $uri = new Uri('/');
        $request = new Request('GET', $uri);
        $request->withHeader('some', 'value');

        static::assertSame(['some' => ['value']], $request->getHeaders());
        $request->reset();
        static::assertSame([], $request->getHeaders());
    }
}