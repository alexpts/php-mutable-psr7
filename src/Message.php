<?php
declare(strict_types=1);

namespace PTS\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;
use function is_numeric;
use function is_string;
use function preg_match;
use function trim;

class Message implements MessageInterface
{

    /**
     * RFC-2616, RFC-7230 - case-insensitive; HTTP2 pack convert all header to lowercase
     *
     * @var array - all header name will be convert to lowercase
     */
    protected array $headers = [];
    protected string $protocol = '1.1';
    protected ?StreamInterface $stream = null;

    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    public function withProtocolVersion($version): self
    {
        if ($this->protocol !== $version) {
            $this->protocol = $version;
        }

        return $this;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader($name): bool
    {
        $name = strtolower($name);
        $hasHeader = $this->headers[$name] ?? null;
        return $hasHeader !== null;
    }

    public function getHeader($name): array
    {
        $name = strtolower($name);
        return $this->headers[$name] ?? [];
    }

    public function getHeaderLine($name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader($name, $value): self
    {
        $name = strtolower($name);
        $value = array_map('trim', (array)$value);
        $this->validateHeader($name, $value);

        $this->headers[$name] = $value;
        return $this;
    }

    public function withoutHeader($name): self
    {
        $name = strtolower($name);
        unset($this->headers[$name]);
        return $this;
    }

    public function getBody(): StreamInterface
    {
        if (!$this->stream) {
            $this->stream = Stream::create('');
        }

        return $this->stream;
    }

    public function withBody(StreamInterface $body): self
    {
        $this->stream = $body;
        return $this;
    }

    public function reset(): static
    {
        $this->headers = [];
        if ($this->stream) {
            $this->stream->close();
            $this->stream = null;
        }

        return $this;
    }

    protected function validateHeader(string $name, array $values): void
    {
        if (preg_match("@^[!#$%&'*+.^_`|~0-9A-Za-z-]+$@", $name) !== 1) {
            throw new InvalidArgumentException('Header name must be an RFC 7230 compatible string.');
        }

        foreach ($values as $v) {
            if (!is_string($v) || 1 !== preg_match("@^[ \x09\x21-\x7E\x80-\xFF]*$@", (string)$v)) {
                throw new InvalidArgumentException('Header values must be RFC 7230 compatible strings.');
            }
        }
    }

    public function withAddedHeader($name, $value): self
    {
        $this->setHeaders([$name => $value]);
        return $this;
    }

    protected function setHeaders(array $headers): void
    {
        $this->validateHeaders($headers);

        foreach ($headers as $name => $values) {
            $values = (array)$values;
            $name = strtolower((string)$name);

            if (!($this->headers[$name] ?? false)) {
                $this->headers[$name] = [];
            }

            foreach ($values as &$value) {
                $value = trim((string)$value);
            }
            $this->headers[$name] = [...$this->headers[$name], ...$values];
        }
    }

    /**
     * @param array $headers
     *
     * It is more strict validate than RFC-7230
     */
    public function validateHeaders(array $headers): void
    {
        if (count($headers)) {
            $names = implode('', array_keys($headers));
            if (preg_match("/^[~0-9A-Za-z-+_.]+$/", $names) !== 1) {
                throw new InvalidArgumentException("Header names is incorrect: $names");
            }

            $this->validateHeadersValues($headers);
        }
    }

    protected function validateHeadersValues(array $headers): void
    {
        $allValues = '';
        foreach ($headers as $values) {
            foreach ((array)$values as $value) {
                $allValues .= $value;
            }
        }

        # https://donsnotes.com/tech/charsets/ascii.html
        if ($allValues && preg_match("/^[\x09\x20-\x7E\x80-\xFF]+$/", $allValues) !== 1) {
            throw new InvalidArgumentException('The value is incorrect for one of the header.');
        }
    }
}
