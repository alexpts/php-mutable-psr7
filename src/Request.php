<?php
declare(strict_types=1);

namespace PTS\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use function preg_match;

class Request extends Message implements RequestInterface
{
    protected string $method;

    protected ?string $requestTarget = null;
    protected ?UriInterface $uri;

    /**
     * @param string $method HTTP method
     * @param UriInterface $uri URI
     * @param array $headers Request headers
     * @param string|resource|StreamInterface|null $body Request body
     * @param string $version Protocol version
     */
    public function __construct(
        string $method,
        UriInterface $uri,
        array $headers = [],
        $body = null,
        string $version = '1.1'
    ) {
        $this->method = $method;
        $this->uri = $uri;
        $this->setHeaders($headers);
        $this->protocol = $version;

        if (!$this->hasHeader('host')) {
            $this->updateHostFromUri();
        }

        if ('' !== $body && null !== $body) {
            $this->stream = Stream::create($body);
        }
    }

    public function getRequestTarget(): string
    {
        if (null !== $this->requestTarget) {
            return $this->requestTarget;
        }

        $target = $this->uri->getPath();
        $target = $target ?: '/';

        if ('' !== $this->uri->getQuery()) {
            $target .= '?' . $this->uri->getQuery();
        }

        $this->requestTarget = $target;
        return $target;
    }

    public function withRequestTarget($requestTarget): self
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException('Invalid request target provided; cannot contain whitespace');
        }

        $this->requestTarget = $requestTarget;
        return $this;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): self
    {
        $this->method = $method;
        return $this;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): self
    {
        if ($uri === $this->uri) {
            return $this;
        }

        $this->uri = $uri;

        if (!$preserveHost || !$this->hasHeader('Host')) {
            $this->updateHostFromUri();
        }

        return $this;
    }

    protected function updateHostFromUri(): void
    {
        if ('' === $host = $this->uri->getHost()) {
            return;
        }

        if (null !== ($port = $this->uri->getPort())) {
            $host .= ':' . $port;
        }

        $this->headers['host'] = [$host];
    }
}
