<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use PTS\Psr7\Response\JsonResponse;
use PTS\Psr7\Stream;

class JsonResponseTest extends TestCase
{
    public function testConstructor(): void
    {
        $body = ['message' => 'ok'];
        $expectedBody = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $response = new JsonResponse($body);
        static::assertInstanceOf(ResponseInterface::class, $response);
        static::assertSame(['content-type' => ['application/json']], $response->getHeaders());

        static::assertSame($expectedBody, (string)$response->getBody());
        static::assertSame(200, $response->getStatusCode());
    }

    public function testConstructorIgnoreContentTypeHeader(): void
    {
        $body = ['message' => 'ok'];
        $response = new JsonResponse($body, 200, [
            'Content-Type' => ['text/plain'],
        ]);
        static::assertInstanceOf(ResponseInterface::class, $response);
        static::assertSame(['content-type' => ['application/json']], $response->getHeaders());
    }

    public function testGetData(): void
    {
        $body = ['message' => 'ok'];
        $response = new JsonResponse($body);
        static::assertSame($body, $response->getData());
    }

    public function testGetBody(): void
    {
        $body = ['message' => 'ok'];
        $expectedBody = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $response = new JsonResponse($body);
        static::assertSame($expectedBody, (string)$response->getBody());
        // case isSyncedBody with data
        static::assertSame($expectedBody, (string)$response->getBody());
    }

    public function testWithBody(): void
    {
        $body = ['message' => 'ok'];
        $jsonBody = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $response = new JsonResponse([]);
        static::assertSame('[]', (string)$response->getBody());

        $response->withBody(Stream::create($jsonBody));
        self::assertSame($body, $response->getData());
        self::assertSame($jsonBody, (string)$response->getBody());
    }

    public function testSetData(): void
    {
        $body = ['message' => 'ok'];
        $jsonBody = json_encode($body, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        $response = new JsonResponse([]);
        $response->setData($body);
        self::assertSame($body, $response->getData());
        self::assertSame($jsonBody, (string)$response->getBody());
    }

    public function testClear(): void
    {
        $body = ['message' => 'ok'];
        $response = new JsonResponse($body, 400, [
            'Header' => 'value',
        ], '2.0');
        $response->getBody();
        $response->withAttribute('attr', 'val');

        $response->reset();
        self::assertSame(200, $response->getStatusCode());
        self::assertSame(['content-type' => ['application/json']], $response->getHeaders());
        self::assertSame('[]', (string)$response->getBody());
        self::assertSame([], $response->getAttributes());
    }
}