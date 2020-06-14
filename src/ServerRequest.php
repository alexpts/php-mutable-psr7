<?php
declare(strict_types=1);

namespace PTS\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;
use PTS\Psr7\Response\ServerMessageInterface;
use function is_array;
use function is_object;

class ServerRequest extends Request implements ServerRequestInterface, ServerMessageInterface
{
	use ServerMessage;

	protected array $cookieParams = [];

	/** @var array|object|null */
	protected $parsedBody;
	protected array $queryParams = [];
	protected array $serverParams;

	/** @var UploadedFileInterface[] */
	protected array $uploadedFiles = [];


	public function __construct(
		string $method,
		UriInterface $uri,
		array $headers = [],
		$body = null,
		string $version = '1.1',
		array $serverParams = []
	) {
		parent::__construct($method, $uri, $headers, $body, $version);

		$this->serverParams = $serverParams;
		parse_str($uri->getQuery(), $this->queryParams);
	}

	public function getServerParams(): array
	{
		return $this->serverParams;
	}

	public function getUploadedFiles(): array
	{
		return $this->uploadedFiles;
	}

	public function withUploadedFiles(array $uploadedFiles)
	{
		$this->uploadedFiles = $uploadedFiles;
		return $this;
	}

	public function getCookieParams(): array
	{
		return $this->cookieParams;
	}

	public function withCookieParams(array $cookies)
	{
		$this->cookieParams = $cookies;
		return $this;
	}

	public function getQueryParams(): array
	{
		return $this->queryParams;
	}

	public function withQueryParams(array $query)
	{
		$this->queryParams = $query;
		return $this;
	}

	public function getParsedBody()
	{
		return $this->parsedBody;
	}

	public function withParsedBody($data)
	{
		if (!is_array($data) && !is_object($data) && null !== $data) {
			throw new InvalidArgumentException('First parameter to withParsedBody MUST be object, array or null');
		}

		$this->parsedBody = $data;
		return $this;
	}
}
