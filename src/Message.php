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

	/** @var array - all header name must be convert to lowercase */
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
		$this->headers[$name] = $this->validateAndTrimHeader($name, (array)$value);
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

	public function reset(): self
	{
		$this->headers = [];
		if ($this->stream) {
			$this->stream->close();
			$this->stream = null;
		}

		return $this;
	}

	protected function validateAndTrimHeader(string $name, array $values): array
	{
		if (preg_match("@^[!#$%&'*+.^_`|~0-9A-Za-z-]+$@", $name) !== 1) {
			throw new InvalidArgumentException('Header name must be an RFC 7230 compatible string.');
		}

		$returnValues = [];
		foreach ($values as $v) {
			if ((!is_numeric($v) && !is_string($v)) || 1 !== preg_match("@^[ \t\x21-\x7E\x80-\xFF]*$@", (string) $v)) {
				throw new InvalidArgumentException('Header values must be RFC 7230 compatible strings.');
			}

			$returnValues[] = trim((string)$v);
		}

		return $returnValues;
	}

	public function withAddedHeader($name, $value): self
	{
		$this->setHeaders([$name => $value]);
		return $this;
	}

	protected function setHeaders(array $headers): void
	{
		foreach ($headers as $name => $values) {
			$values = (array)$values;
			$name = strtolower((string)$name);

			if (!$this->hasHeader($name)) {
				$this->headers[$name] = [];
			}

			foreach ($values as $value) {
				$value = $this->validateAndTrimHeader($name, (array)$value)[0];
				$this->headers[$name][] = $value;
			}
		}
	}
}
