<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;
use PTS\Psr7\Factory\Psr17Factory;
use PTS\Psr7\Stream;
use PTS\Psr7\UploadedFile;

class UploadedFileTest extends TestCase
{
	protected array $cleanup = [];

	public function setUp(): void
	{
		parent::setUp();
		$this->cleanup = [];
	}

	public function tearDown(): void
	{
		foreach ($this->cleanup as $file) {
			if (is_scalar($file) && is_string($file) && file_exists($file)) {
				unlink($file);
			}
		}

		parent::tearDown();
	}

	public function dataProviderInvalidStreams(): array
	{
		return [
			'null' => [null],
			'true' => [true],
			'false' => [false],
			'int' => [1],
			'float' => [1.1],
			'array' => [['filename']],
			'object' => [(object)['filename']],
		];
	}

	/**
	 * @dataProvider dataProviderInvalidStreams
	 *
	 * @param mixed $streamOrFile
	 */
	public function testRaisesExceptionOnInvalidStreamOrFile($streamOrFile): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Invalid stream or file provided for UploadedFile');

		new UploadedFile($streamOrFile, 0, UPLOAD_ERR_OK);
	}

	public function dataProviderInvalidErrorStatuses(): array
	{
		return [
			'null' => [null],
			'true' => [true],
			'false' => [false],
			'float' => [1.1],
			'string' => ['1'],
			'array' => [[1]],
			'object' => [(object) [1]],
			'negative' => [-1],
			'too-big' => [9],
		];
	}

	/**
	 * @dataProvider dataProviderInvalidErrorStatuses
	 *
	 * @param mixed $status
	 */
	public function testRaisesExceptionOnInvalidErrorStatus($status): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('status');

		new UploadedFile(fopen('php://temp', 'wb+'), 0, $status);
	}

	public function dpInvalidFilenamesAndMediaTypes(): array
	{
		return [
			'true' => [true],
			'false' => [false],
			'int' => [1],
			'float' => [1.1],
			'array' => [['string']],
			'object' => [(object) ['string']],
		];
	}

	/**
	 * @dataProvider dpInvalidFilenamesAndMediaTypes
	 *
	 * @param mixed $filename
	 */
	public function testRaisesExceptionOnInvalidClientFilename($filename): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('filename');

		new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, $filename);
	}

	/**
	 * @dataProvider dpInvalidFilenamesAndMediaTypes
	 *
	 * @param mixed $mediaType
	 */
	public function testRaisesExceptionOnInvalidClientMediaType($mediaType): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('media type');

		new UploadedFile(fopen('php://temp', 'wb+'), 0, UPLOAD_ERR_OK, 'foobar.baz', $mediaType);
	}

	public function testGetStreamReturnsOriginalStreamObject(): void
	{
		$stream = Stream::create('');
		$upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

		static::assertSame($stream, $upload->getStream());
	}

	public function testGetStreamReturnsWrappedPhpStream(): void
	{
		$stream = fopen('php://temp', 'wb+');
		$upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);
		$uploadStream = $upload->getStream()->detach();

		static::assertSame($stream, $uploadStream);
	}

	public function testGetStream(): void
	{
		$upload = new UploadedFile(__DIR__.'/Resources/foo.txt', 0, UPLOAD_ERR_OK);
		$stream = $upload->getStream();
		static::assertInstanceOf(StreamInterface::class, $stream);
		static::assertSame("Foobar\n", $stream->__toString());
	}

	public function testSuccessful(): void
	{
		$stream = Stream::create('Foo bar!');
		$upload = new UploadedFile($stream, $stream->getSize(), UPLOAD_ERR_OK, 'filename.txt', 'text/plain');

		static::assertSame($stream->getSize(), $upload->getSize());
		static::assertSame('filename.txt', $upload->getClientFilename());
		static::assertSame('text/plain', $upload->getClientMediaType());

		$this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'successful');
		$upload->moveTo($to);
		static::assertFileExists($to);
		static::assertSame($stream->__toString(), file_get_contents($to));
	}

	public function dpInvalidMovePaths(): array
	{
		return [
			'null' => [null],
			'true' => [true],
			'false' => [false],
			'int' => [1],
			'float' => [1.1],
			'empty' => [''],
			'array' => [['filename']],
			'object' => [(object) ['filename']],
		];
	}

	/**
	 * @dataProvider dpInvalidMovePaths
	 *
	 * @param mixed $path
	 */
	public function testMoveRaisesExceptionForInvalidPath($path): void
	{
		$stream = (new Psr17Factory)->createStream('Foo bar!');
		$upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

		$this->cleanup[] = $path;

		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('path');
		$upload->moveTo($path);
	}

	public function testMoveCannotBeCalledMoreThanOnce(): void
	{
		$stream = (new Psr17Factory())->createStream('Foo bar!');
		$upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

		$this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'diac');
		$upload->moveTo($to);
		static::assertFileExists($to);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('moved');
		$upload->moveTo($to);
	}

	public function testCannotRetrieveStreamAfterMove(): void
	{
		$stream = (new Psr17Factory())->createStream('Foo bar!');
		$upload = new UploadedFile($stream, 0, UPLOAD_ERR_OK);

		$this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'diac');
		$upload->moveTo($to);
		static::assertFileExists($to);

		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('moved');
		$upload->getStream();
	}

	public function nonOkErrorStatus(): array
	{
		return [
			'UPLOAD_ERR_INI_SIZE' => [UPLOAD_ERR_INI_SIZE],
			'UPLOAD_ERR_FORM_SIZE' => [UPLOAD_ERR_FORM_SIZE],
			'UPLOAD_ERR_PARTIAL' => [UPLOAD_ERR_PARTIAL],
			'UPLOAD_ERR_NO_FILE' => [UPLOAD_ERR_NO_FILE],
			'UPLOAD_ERR_NO_TMP_DIR' => [UPLOAD_ERR_NO_TMP_DIR],
			'UPLOAD_ERR_CANT_WRITE' => [UPLOAD_ERR_CANT_WRITE],
			'UPLOAD_ERR_EXTENSION' => [UPLOAD_ERR_EXTENSION],
		];
	}

	/**
	 * @dataProvider nonOkErrorStatus
	 *
	 * @param int $status
	 */
	public function testConstructorDoesNotRaiseExceptionForInvalidStreamWhenErrorStatusPresent(int $status): void
	{
		$uploadedFile = new UploadedFile('not ok', 0, $status);
		static::assertSame($status, $uploadedFile->getError());
	}

	/**
	 * @dataProvider nonOkErrorStatus
	 *
	 * @param int $status
	 */
	public function testMoveToRaisesExceptionWhenErrorStatusPresent(int $status): void
	{
		$uploadedFile = new UploadedFile('not ok', 0, $status);
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('upload error');
		$uploadedFile->moveTo(__DIR__.'/'.uniqid('', true));
	}

	public function testMoveToBadTarget(): void
	{
		$uploadedFile = new UploadedFile(Stream::create('blob_hex'), 8, UPLOAD_ERR_OK);
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('Uploaded file could not be moved to asd://badTarget');
		$uploadedFile->moveTo('asd://badTarget');
	}

	/**
	 * @dataProvider nonOkErrorStatus
	 *
	 * @param int $status
	 */
	public function testGetStreamRaisesExceptionWhenErrorStatusPresent(int $status): void
	{
		$uploadedFile = new UploadedFile('not ok', 0, $status);
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage('upload error');
		$uploadedFile->getStream();
	}

	public function testMoveToCreatesStreamIfOnlyAFilenameWasProvided(): void
	{
		$this->cleanup[] = $from = tempnam(sys_get_temp_dir(), 'copy_from');
		$this->cleanup[] = $to = tempnam(sys_get_temp_dir(), 'copy_to');

		copy(__FILE__, $from);

		$uploadedFile = new UploadedFile($from, 100, UPLOAD_ERR_OK, basename($from), 'text/plain');
		$uploadedFile->moveTo($to);

		static::assertFileEquals(__FILE__, $to);
	}

	public function testConstructorBadSizeType(): void
	{
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage('Upload file size must be an integer');
		new UploadedFile('not ok', '123', UPLOAD_ERR_INI_SIZE);
	}
}