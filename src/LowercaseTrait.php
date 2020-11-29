<?php
declare(strict_types=1);

namespace PTS\Psr7;

use function strtr;

trait LowercaseTrait
{
    protected static function lowercase(string $value): string
    {
        return strtr($value, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
    }
}