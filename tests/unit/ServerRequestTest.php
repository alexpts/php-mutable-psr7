<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use PTS\Psr7\ServerRequest;
use PTS\Psr7\UploadedFile;
use PTS\Psr7\Uri;

class ServerRequestTest extends TestCase
{
    public function testUploadedFiles(): void
    {
        $request = new ServerRequest('GET', new Uri('/'));
        $files = [
            'file' => new UploadedFile('test', 123, UPLOAD_ERR_OK),
        ];

        $request->withUploadedFiles($files);
        static::assertSame($files, $request->getUploadedFiles());
    }

    public function testServerParams(): void
    {
        $params = ['name' => 'value'];

        $request = new ServerRequest('GET', new Uri('/'), [], null, '1.1', $params);
        static::assertSame($params, $request->getServerParams());
    }

    public function testCookieParams(): void
    {
        $request = new ServerRequest('GET', new Uri('/'));

        $params = ['name' => 'value'];
        $request->withCookieParams($params);
        static::assertSame($params, $request->getCookieParams());
    }

    public function testQueryParams(): void
    {
        $request = new ServerRequest('GET', new Uri('/'));
        $params = ['name' => 'value'];

        $request->withQueryParams($params);
        static::assertSame($params, $request->getQueryParams());
    }

    public function testQueryParamsFromUri(): void
    {
        $request = new ServerRequest('GET', new Uri('/?name=value&age=12'));
        $expected = ['name' => 'value', 'age' => '12'];
        static::assertCount(2, $request->getQueryParams());
        static::assertSame($expected, $request->getQueryParams());

        $request = new ServerRequest('GET', new Uri('/'));
        static::assertCount(0, $request->getQueryParams());
    }

    public function testParsedBody(): void
    {
        $request = new ServerRequest('GET', new Uri('/'));
        $params = ['name' => 'value'];
        $request = $request->withParsedBody($params);
        static::assertSame($params, $request->getParsedBody());
    }

    public function testAttributes(): void
    {
        $request = new ServerRequest('GET', new Uri('/'));

        static::assertEmpty($request->getAttributes());
        static::assertEmpty($request->getAttribute('name'));
        static::assertSame(
            'something',
            $request->getAttribute('name', 'something'),
            'Should return the default value'
        );

        $request->withAttribute('name', 'value');
        static::assertSame('value', $request->getAttribute('name'));
        static::assertSame(['name' => 'value'], $request->getAttributes());

        $request->withAttribute('other', 'otherValue');
        static::assertSame(['name' => 'value', 'other' => 'otherValue'], $request->getAttributes());

        $request->withoutAttribute('other');
        static::assertSame(['name' => 'value'], $request->getAttributes());

        $request->withoutAttribute('unknown');
    }

    public function testNullAttribute(): void
    {
        $request = (new ServerRequest('GET', new Uri('/')))->withAttribute('name', null);

        static::assertSame(['name' => null], $request->getAttributes());
        static::assertNull($request->getAttribute('name', 'different-default'));

        $requestWithoutAttribute = $request->withoutAttribute('name');

        static::assertSame([], $requestWithoutAttribute->getAttributes());
        static::assertSame('different-default', $requestWithoutAttribute->getAttribute('name', 'different-default'));
    }

    public function testWithParsedBodyWitnInvalidType(): void
    {
        $request = new ServerRequest('GET', new Uri('/'));
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First parameter to withParsedBody MUST be object, array or null');
        $request->withParsedBody('string');
    }
}