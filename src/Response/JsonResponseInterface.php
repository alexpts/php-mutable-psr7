<?php
declare(strict_types=1);

namespace PTS\Psr7\Response;

interface JsonResponseInterface extends ServerMessageInterface
{
    public function getData(): array;

    public function setData(array $data): static;

    public function reset(): static;

}