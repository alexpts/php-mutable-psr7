<?php
declare(strict_types=1);

namespace PTS\Psr7;

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use PTS\Psr7\Response\ServerMessageInterface;
use function sprintf;

class Response extends Message implements ServerMessageInterface, ResponseInterface
{

    use ServerMessage;

    /** @var array Map of standard HTTP status code/reason phrases */
    protected const PHRASES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-status',
        208 => 'Already Reported',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Time-out',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Request Entity Too Large',
        414 => 'Request-URI Too Large',
        415 => 'Unsupported Media Type',
        416 => 'Requested range not satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Time-out',
        505 => 'HTTP Version not supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        511 => 'Network Authentication Required',
    ];

    protected string $reasonPhrase = '';
    protected int $statusCode = 200;

    /**
     * @param int $status Status code
     * @param array $headers Response headers
     * @param string|resource|StreamInterface|null $body Response body
     * @param string $version Protocol version
     * @param string|null $reason Reason phrase (when empty a default will be used based on the status code)
     */
    public function __construct(
        int $status = 200,
        array $headers = [],
        $body = null,
        string $version = '1.1',
        string $reason = null
    ) {
        // If we got no body, defer initialization of the stream until Response::getBody()
        if ('' !== $body && null !== $body) {
            $this->stream = Stream::create($body);
        }

        $this->statusCode = $status;
        $this->reasonPhrase = $reason ?? self::PHRASES[$this->statusCode] ?? '';
        $this->protocol = $version;
        $this->setHeaders($headers);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function withStatus($code, $reasonPhrase = ''): self
    {
        if ($code < 100 || $code > 599) {
            $message = sprintf(
                'Status code has to be an integer between 100 and 599. A status code of %d was given',
                $code
            );
            throw new InvalidArgumentException($message);
        }

        $this->statusCode = $code;
        $this->reasonPhrase = $reasonPhrase !== '' ? $reasonPhrase : (self::PHRASES[$this->statusCode] ?? '');
        return $this;
    }
}