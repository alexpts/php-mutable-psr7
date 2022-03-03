<?php
declare(strict_types=1);

namespace PTS\Psr7\Factory;

use InvalidArgumentException;
use Nyholm\Psr7Server\ServerRequestCreator;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileFactoryInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use PTS\Psr7\Request;
use PTS\Psr7\Response;
use PTS\Psr7\ServerRequest;
use PTS\Psr7\Stream;
use PTS\Psr7\UploadedFile;
use PTS\Psr7\Uri;
use RuntimeException;
use Throwable;
use function error_get_last;
use function fopen;
use function func_num_args;
use function in_array;
use function sprintf;

class Psr17Factory
    implements RequestFactoryInterface, ResponseFactoryInterface, ServerRequestFactoryInterface, StreamFactoryInterface,
    UploadedFileFactoryInterface, UriFactoryInterface
{

    /** @var ServerRequestCreator|null */
    protected ?ServerRequestCreator $creatorFromGlobal = null;

    public function createRequest(string $method, $uri): RequestInterface
    {
        if (is_string($uri)) {
            $uri = $this->createUri($uri);
        }

        return new Request($method, $uri);
    }

    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        if (2 > func_num_args()) {
            $reasonPhrase = null;
        }

        return new Response($code, [], null, '1.1', $reasonPhrase);
    }

    public function createStream(string $content = ''): StreamInterface
    {
        return Stream::create($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        $resource = @fopen($filename, $mode);
        if (false === $resource) {
            if ('' === $mode || false === in_array($mode[0], ['r', 'w', 'a', 'x', 'c'], true)) {
                throw new InvalidArgumentException(sprintf('The mode "%s" is invalid.', $mode));
            }

            throw new RuntimeException(sprintf('The file "%s" cannot be opened.', $filename));
        }

        return Stream::create($resource);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return Stream::create($resource);
    }

    public function createUploadedFile(
        StreamInterface $stream,
        int $size = null,
        int $error = \UPLOAD_ERR_OK,
        string $clientFilename = null,
        string $clientMediaType = null
    ): UploadedFileInterface {
        if (null === $size) {
            $size = $stream->getSize();
        }

        return new UploadedFile($stream, $size, $error, $clientFilename, $clientMediaType);
    }

    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }

    public function createServerRequest(string $method, $uri, array $serverParams = []): ServerRequestInterface
    {
        if (is_string($uri)) {
            $uri = $this->createUri($uri);
        }

        return new ServerRequest($method, $uri, [], null, '1.1', $serverParams);
    }

    public function fromGlobals(): ServerRequestInterface
    {
        if ($this->creatorFromGlobal === null) {
            $psr17 = new static;
            $this->creatorFromGlobal = new ServerRequestCreator($psr17, $psr17, $psr17, $psr17);
        }

        return $this->creatorFromGlobal->fromGlobals();
    }
}