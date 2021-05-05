<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use PTS\Psr7\Response;
use PTS\Psr7\Stream;

class ResponseTest extends TestCase
{
    public function testDefaultConstructor(): void
    {
        $response = new Response;
        static::assertSame(200, $response->getStatusCode());
        static::assertSame('1.1', $response->getProtocolVersion());
        static::assertSame('OK', $response->getReasonPhrase());
        static::assertSame([], $response->getHeaders());
        static::assertInstanceOf(StreamInterface::class, $response->getBody());
        static::assertSame('', (string)$response->getBody());
    }

    public function testCanConstructWithStatusCode(): void
    {
        $response = new Response(404);
        static::assertSame(404, $response->getStatusCode());
        static::assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testCanConstructWithUndefinedStatusCode(): void
    {
        $response = new Response(999);
        static::assertSame(999, $response->getStatusCode());
        static::assertSame('', $response->getReasonPhrase());
    }

    public function testCanConstructWithStatusCodeAndEmptyReason(): void
    {
        $response = new Response(404, [], null, '1.1', '');
        static::assertSame(404, $response->getStatusCode());
        static::assertSame('', $response->getReasonPhrase());
    }

    public function testWithStatusCodeMore500(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Status code has to be an integer between 100 and 599');
        (new Response)->withStatus(600);
    }

    public function testConstructorDoesNotReadStreamBody(): void
    {
        $body = $this->getMockBuilder(StreamInterface::class)->getMock();
        $body->expects($this->never())
            ->method('__toString');

        $r = new Response(200, [], $body);
        static::assertSame($body, $r->getBody());
    }

    public function testCanConstructWithHeaders(): void
    {
        $response = new Response(200, ['Foo' => 'Bar']);
        static::assertSame(['foo' => ['Bar']], $response->getHeaders());
        static::assertSame('Bar', $response->getHeaderLine('Foo'));
        static::assertSame(['Bar'], $response->getHeader('Foo'));
    }

    public function testCanConstructWithHeadersAsArray(): void
    {
        $response = new Response(200, [
            'Foo' => ['baz', 'bar'],
        ]);
        static::assertSame(['foo' => ['baz', 'bar']], $response->getHeaders());
        static::assertSame('baz, bar', $response->getHeaderLine('Foo'));
        static::assertSame(['baz', 'bar'], $response->getHeader('Foo'));
    }

    public function testCanConstructWithBody(): void
    {
        $response = new Response(200, [], 'baz');
        static::assertInstanceOf(StreamInterface::class, $response->getBody());
        static::assertSame('baz', (string)$response->getBody());
    }

    public function testNullBody(): void
    {
        $response = new Response(200, [], null);
        static::assertInstanceOf(StreamInterface::class, $response->getBody());
        static::assertSame('', (string)$response->getBody());
    }

    public function testFalseyBody(): void
    {
        $response = new Response(200, [], '0');
        static::assertInstanceOf(StreamInterface::class, $response->getBody());
        static::assertSame('0', (string)$response->getBody());
    }

    public function testCanConstructWithReason(): void
    {
        $response = new Response(200, [], null, '1.1', 'bar');
        static::assertSame('bar', $response->getReasonPhrase());

        $response = new Response(200, [], null, '1.1', '0');
        static::assertSame('0', $response->getReasonPhrase(), 'Falsey reason works');
    }

    public function testCanConstructWithProtocolVersion(): void
    {
        $response = new Response(200, [], null, '1000');
        static::assertSame('1000', $response->getProtocolVersion());
    }

    public function testWithStatusCodeAndNoReason(): void
    {
        $response = (new Response)->withStatus(201);
        static::assertSame(201, $response->getStatusCode());
        static::assertSame('Created', $response->getReasonPhrase());
    }

    public function testWithStatusCodeAndReason(): void
    {
        $response = (new Response)->withStatus(201, 'Foo');
        static::assertSame(201, $response->getStatusCode());
        static::assertSame('Foo', $response->getReasonPhrase());

        $response = (new Response())->withStatus(201, '0');
        static::assertSame(201, $response->getStatusCode());
        static::assertSame('0', $response->getReasonPhrase(), 'Falsey reason works');
    }

    public function testWithProtocolVersion(): void
    {
        $response = (new Response)->withProtocolVersion('1000');
        static::assertSame('1000', $response->getProtocolVersion());
    }

    public function testSameInstanceWhenSameProtocol(): void
    {
        $response = new Response;
        static::assertSame($response, $response->withProtocolVersion('1.1'));
    }

    public function testWithBody(): void
    {
        $stream = Stream::create('0');
        $response = (new Response)->withBody($stream);
        static::assertInstanceOf(StreamInterface::class, $response->getBody());
        static::assertSame('0', (string)$response->getBody());
    }

    public function testSameInstanceWhenSameBody(): void
    {
        $response = new Response();
        $body = $response->getBody();
        static::assertSame($response, $response->withBody($body));
    }

    public function testWithHeader(): void
    {
        $response = new Response(200, ['Foo' => 'Bar']);
        $response->withHeader('baZ', 'Bam');
        static::assertSame(['foo' => ['Bar'], 'baz' => ['Bam']], $response->getHeaders());
        static::assertSame('Bam', $response->getHeaderLine('baz'));
        static::assertSame(['Bam'], $response->getHeader('baz'));
    }

    public function testWithHeaderAsArray(): void
    {
        $response = new Response(200, ['Foo' => 'Bar']);
        static::assertSame(['foo' => ['Bar']], $response->getHeaders());

        $response->withHeader('baZ', ['Bam', 'Bar']);
        static::assertSame(['foo' => ['Bar'], 'baz' => ['Bam', 'Bar']], $response->getHeaders());
        static::assertSame('Bam, Bar', $response->getHeaderLine('baz'));
        static::assertSame(['Bam', 'Bar'], $response->getHeader('baz'));
    }

    public function testWithAddedHeader(): void
    {
        $response = new Response(200, ['Foo' => 'Bar']);
        static::assertSame(['foo' => ['Bar']], $response->getHeaders());

        $response->withAddedHeader('foO', 'Baz');
        static::assertSame(['foo' => ['Bar', 'Baz']], $response->getHeaders());
        static::assertSame('Bar, Baz', $response->getHeaderLine('foo'));
        static::assertSame(['Bar', 'Baz'], $response->getHeader('foo'));
    }

    public function testWithAddedHeaderAsArray(): void
    {
        $response = new Response(200, ['Foo' => 'Bar']);
        static::assertSame(['foo' => ['Bar']], $response->getHeaders());

        $response->withAddedHeader('foO', ['Baz', 'Bam']);
        static::assertSame(['foo' => ['Bar', 'Baz', 'Bam']], $response->getHeaders());
        static::assertSame('Bar, Baz, Bam', $response->getHeaderLine('foo'));
        static::assertSame(['Bar', 'Baz', 'Bam'], $response->getHeader('foo'));
    }

    public function testWithAddedHeaderThatDoesNotExist(): void
    {
        $response = new Response(200, ['Foo' => 'Bar']);
        static::assertSame(['foo' => ['Bar']], $response->getHeaders());

        $response->withAddedHeader('nEw', 'Baz');
        static::assertSame(['foo' => ['Bar'], 'new' => ['Baz']], $response->getHeaders());
        static::assertSame('Baz', $response->getHeaderLine('new'));
        static::assertSame(['Baz'], $response->getHeader('new'));
    }

    public function testWithoutHeaderThatExists(): void
    {
        $response = new Response(200, ['Foo' => 'Bar', 'Baz' => 'Bam']);
        $response->withoutHeader('Foo');

        static::assertFalse($response->hasHeader('foo'));
        static::assertSame(['baz' => ['Bam']], $response->getHeaders());
    }

    public function testWithoutHeaderThatDoesNotExist(): void
    {
        $response = new Response(200, ['Baz' => 'Bam']);
        $response->withoutHeader('foO');
        static::assertFalse($response->hasHeader('foo'));
        static::assertSame(['baz' => ['Bam']], $response->getHeaders());
    }

    public function testSameInstanceWhenRemovingMissingHeader(): void
    {
        $response = new Response;
        static::assertSame($response, $response->withoutHeader('foo'));
    }

    public function trimmedHeaderValues(): array
    {
        return [
            [new Response(200, ['OWS' => " \t \tFoo\t \t "])],
            [(new Response)->withHeader('OWS', " \t \tFoo\t \t ")],
            [(new Response)->withAddedHeader('OWS', " \t \tFoo\t \t ")],
        ];
    }

    /**
     * @dataProvider trimmedHeaderValues
     */
    public function testHeaderValuesAreTrimmed(ResponseInterface $response): void
    {
        static::assertSame(['ows' => ['Foo']], $response->getHeaders());
        static::assertSame('Foo', $response->getHeaderLine('OWS'));
        static::assertSame(['Foo'], $response->getHeader('OWS'));
    }

    public function testReset(): void
    {
        $response = new Response;
        $response->withBody(Stream::create('responseBody'));

        static::assertSame('responseBody', (string)$response->getBody());
        $response->reset();
        static::assertSame('', (string)$response->getBody());
    }

    /**
     * @param string $headerValue
     *
     * @dataProvider unSupportHeaderValue
     */
    public function testSetBadValueHeader(string $headerValue): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Header values must be RFC 7230 compatible strings');

        $response = new Response;
        $response->withHeader('header', $headerValue);
    }

    public function unSupportHeaderValue(): array
    {
        return [
            ["Not a line \r\n_"],
        ];
    }
}