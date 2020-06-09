<?php
declare(strict_types=1);

namespace PTS\Psr7;

use function array_key_exists;

trait ServerMessage
{
	protected array $attributes = [];

	/**
	 * @return array
	 */
	public function getAttributes(): array
	{
		return $this->attributes;
	}

	/**
	 * @param string $attribute
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public function getAttribute($attribute, $default = null)
	{
		if (false === array_key_exists($attribute, $this->attributes)) {
			return $default;
		}

		return $this->attributes[$attribute];
	}

	/**
	 * @param string $attribute
	 * @param mixed $value
	 *
	 * @return $this
	 */
	public function withAttribute($attribute, $value)
	{
		$this->attributes[$attribute] = $value;
		return $this;
	}

	public function withoutAttribute($attribute)
	{
		if (false === array_key_exists($attribute, $this->attributes)) {
			return $this;
		}

		unset($this->attributes[$attribute]);

		return $this;
	}
}
