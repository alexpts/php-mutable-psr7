<?php

namespace PTS\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\UriInterface;
use function is_string;
use function parse_url;
use function preg_replace_callback;
use function rawurlencode;
use function sprintf;

class Uri implements UriInterface
{
	use LowercaseTrait;

	protected const SCHEMES = ['http' => 80, 'https' => 443];
	protected const CHAR_UNRESERVED = 'a-zA-Z0-9_\-\.~';
	protected const CHAR_SUB_DELIMS = '!\$&\'\(\)\*\+,;=';

	protected string $scheme = '';
	protected string $userInfo = '';
	protected string $host = '';

	protected ?int $port = null;
	protected string $path = '';
	protected string $query = '';
	protected string $fragment = '';

	public function __construct(string $uri = '')
	{
		if ('' !== $uri) {
			$parts = parse_url($uri);
			if ($parts === false) {
				throw new InvalidArgumentException("Unable to parse URI: $uri");
			}

			$this->withScheme($parts['scheme'] ?? '');
			$this->userInfo = $parts['user'] ?? '';
			$this->withHost($parts['host'] ?? '');
			$this->port = isset($parts['port']) ? $this->filterPort($parts['port']) : null;
			$this->path = isset($parts['path']) ? $this->filterPath($parts['path']) : '';
			$this->query = isset($parts['query']) ? $this->filterQueryAndFragment($parts['query']) : '';
			$this->fragment = isset($parts['fragment']) ? $this->filterQueryAndFragment($parts['fragment']) : '';
			if (isset($parts['pass'])) {
				$this->userInfo .= ':' . $parts['pass'];
			}
		}
	}

	public function __toString(): string
	{
		return self::createUriString($this->scheme, $this->getAuthority(), $this->path, $this->query, $this->fragment);
	}

	public function getScheme(): string
	{
		return $this->scheme;
	}

	public function getAuthority(): string
	{
		if ('' === $this->host) {
			return '';
		}

		$authority = $this->host;
		if ('' !== $this->userInfo) {
			$authority = $this->userInfo . '@' . $authority;
		}

		if (null !== $this->port) {
			$authority .= ':' . $this->port;
		}

		return $authority;
	}

	public function getUserInfo(): string
	{
		return $this->userInfo;
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getPort(): ?int
	{
		return $this->port;
	}

	public function getPath(): string
	{
		return $this->path;
	}

	public function getQuery(): string
	{
		return $this->query;
	}

	public function getFragment(): string
	{
		return $this->fragment;
	}

	public function withScheme($scheme): self
	{
		$this->scheme = self::lowercase($scheme);
		$this->port = $this->port ? $this->filterPort($this->port) : null;

		return $this;
	}

	public function withUserInfo($user, $password = null): self
	{
		$info = $user;
		if (null !== $password && '' !== $password) {
			$info .= ':' . $password;
		}

		$this->userInfo = $info;

		return $this;
	}

	public function withHost($host): self
	{
		$this->host = self::lowercase($host);
		return $this;
	}

	public function withPort($port): self
	{
		$this->port = $port === null ? null : $this->filterPort($port);
		return $this;
	}

	public function withPath($path): self
	{
		$path = $this->filterPath($path);
		if ($this->path !== $path) {
			$this->path = $path;
		}

		return $this;
	}

	public function withQuery($query): self
	{
		$query = $this->filterQueryAndFragment($query);
		if ($this->query !== $query) {
			$this->query = $query;
		}

		return $this;
	}

	public function withFragment($fragment): self
	{
		$fragment = $this->filterQueryAndFragment($fragment);
		if ($this->fragment !== $fragment) {
			$this->fragment = $fragment;
		}

		return $this;
	}

	private static function createUriString(string $scheme, string $authority, string $path, string $query, string $fragment): string
	{
		$uri = '';
		if ('' !== $scheme) {
			$uri .= $scheme . ':';
		}

		if ('' !== $authority) {
			$uri .= '//' . $authority;
		}

		if ('' !== $path) {
			if ('/' !== $path[0]) {
				if ('' !== $authority) {
					// If the path is rootless and an authority is present, the path MUST be prefixed by "/"
					$path = '/' . $path;
				}
			} elseif (isset($path[1]) && '/' === $path[1]) {
				if ('' === $authority) {
					// If the path is starting with more than one "/" and no authority is present, the
					// starting slashes MUST be reduced to one.
					$path = '/' . \ltrim($path, '/');
				}
			}

			$uri .= $path;
		}

		if ('' !== $query) {
			$uri .= '?' . $query;
		}

		if ('' !== $fragment) {
			$uri .= '#' . $fragment;
		}

		return $uri;
	}

	private static function isNonStandardPort(string $scheme, int $port): bool
	{
		return !isset(self::SCHEMES[$scheme]) || $port !== self::SCHEMES[$scheme];
	}

	protected function filterPort(int $port): ?int
	{
		if ($port < 1 || $port > 65535) {
			throw new InvalidArgumentException(sprintf('Invalid port: %d. Must be between 0 and 65535', $port));
		}

		return self::isNonStandardPort($this->scheme, $port) ? $port : null;
	}

	private function filterPath($path): string
	{
		if (!is_string($path)) {
			throw new InvalidArgumentException('Path must be a string');
		}

		return preg_replace_callback('/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/]++|%(?![A-Fa-f0-9]{2}))/', [__CLASS__, 'rawurlencodeMatchZero'], $path);
	}

	private function filterQueryAndFragment($str): string
	{
		if (!is_string($str)) {
			throw new InvalidArgumentException('Query and fragment must be a string');
		}

		return preg_replace_callback('/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\/\?]++|%(?![A-Fa-f0-9]{2}))/', [__CLASS__, 'rawurlencodeMatchZero'], $str);
	}

	private static function rawUrlEncodeMatchZero(array $match): string
	{
		return rawurlencode($match[0]);
	}
}