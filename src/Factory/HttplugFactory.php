<?php
declare(strict_types=1);

namespace PTS\Psr7\Factory;

use Http\Message\MessageFactory;
use Http\Message\StreamFactory;
use Http\Message\UriFactory;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use PTS\Psr7\Request;
use PTS\Psr7\Response;
use PTS\Psr7\Stream;
use PTS\Psr7\Uri;

class HttplugFactory implements MessageFactory, StreamFactory, UriFactory
{
    public function createRequest($method, $uri, array $headers = [], $body = null, $protocolVersion = '1.1'): RequestInterface
    {
        $uri = $this->createUri($uri);
        return new Request($method, $uri, $headers, $body, $protocolVersion);
    }

    public function createResponse(
        $statusCode = 200,
        $reasonPhrase = null,
        array $headers = [],
        $body = null,
        $version = '1.1'
    ): ResponseInterface {
        return new Response((int)$statusCode, $headers, $body, $version, $reasonPhrase);
    }

    public function createStream($body = null): StreamInterface
    {
        return Stream::create($body ?? '');
    }

    public function createUri($uri = ''): UriInterface
    {
        if ($uri instanceof UriInterface) {
            return $uri;
        }

        return new Uri($uri);
    }
}