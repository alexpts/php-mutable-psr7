<?php
declare(strict_types=1);

namespace PTS\Psr7\Response;

use Psr\Http\Message\StreamInterface;
use PTS\Psr7\Message;
use PTS\Psr7\Response as HttpResponse;
use PTS\Psr7\Stream;

class JsonResponse extends HttpResponse implements JsonResponseInterface
{
    protected array $data = [];
    protected bool $isSyncBody = false;

    public function __construct(
        array $data,
        int $status = 200,
        array $headers = [],
        string $version = '1.1'
    ) {
        $headers['Content-Type'] = 'application/json';
        parent::__construct($status, $headers, null, $version);
        $this->data = $data;
    }

    public function getData(): array
    {
        return $this->data;
    }

    // @todo very slow, need php bench test and add method getContent
    public function getBody(): StreamInterface
    {
        if (!$this->isSyncBody) {
            $this->isSyncBody = true;
            $data = json_encode($this->data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE, 512);
            parent::withBody(Stream::create($data));
        }

        return parent::getBody();
    }

    public function getContent(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    }

    public function withBody(StreamInterface $body): Message
    {
        $this->data = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
        $this->isSyncBody = true;
        return parent::withBody($body);
    }

    public function setData(array $data): static
    {
        $this->isSyncBody = false;
        $this->data = $data;
        return $this;
    }

    public function reset(): static
    {
        $this->isSyncBody = false;
        $this->data = [];
        $this->protocol = '1.1';
        $this->statusCode = 200;
        $this->attributes = [];
        $this->headers = ['content-type' => ['application/json']];

        if ($this->stream) {
            $this->stream->close();
            $this->stream = null;
        }

        return $this;
    }
}