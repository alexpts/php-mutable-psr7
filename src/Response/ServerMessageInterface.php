<?php
declare(strict_types=1);

namespace PTS\Psr7\Response;

interface ServerMessageInterface
{
	/**
	 * @return array
	 */
	public function getAttributes(): array;

	/**
	 * @param string $attribute
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getAttribute($attribute, $default = null);

	/**
	 * @param string $attribute
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function withAttribute($attribute, $value);

	/**
	 * @param string $attribute
	 *
	 * @return $this
	 */
	public function withoutAttribute($attribute);
}