<?php
declare(strict_types=1);

namespace PTS\Test\Psr7\bench\Response;

use PhpBench\Benchmark\Metadata\Annotations\Assert;
use PhpBench\Benchmark\Metadata\Annotations\Iterations;
use PhpBench\Benchmark\Metadata\Annotations\OutputTimeUnit;
use PhpBench\Benchmark\Metadata\Annotations\Revs;
use PhpBench\Benchmark\Metadata\Annotations\Warmup;
use PTS\Psr7\Response\JsonResponse;

class JsonResponseBench
{
    protected JsonResponse $response;

    public function __construct()
    {
        $this->response = new JsonResponse([], 200, [
            'h1' => 1,
            'h2' => 2,
            'h3' => 3,
            'X-lame' => 'some values',
            'foo' => 'asdasd asd asd21 123e12 / 12  /sad',
        ]);
    }

    /**
     * @Revs(10)
     * @Iterations(4)
     * @OutputTimeUnit("microseconds", precision=3)
     * @Warmup(1)
     *
     * @Assert("mode(variant.time.avg) < 0.3 microseconds")
     */
    public function benchCreateViaClone(): void
    {
        $response = clone $this->response;
        $response->reset();
    }

    /**
     * @Revs(10)
     * @Iterations(4)
     * @OutputTimeUnit("microseconds", precision=3)
     * @Warmup(1)
     *
     * @Assert("mode(variant.time.avg) < 4 microseconds")
     */
    public function benchCreate(): void
    {
        new JsonResponse([], 200, [
            'h1' => 1,
            'h2' => 2,
            'h3' => 3,
            'X-lame' => 'some values',
            'foo' => 'asdasd asd asd21 123e12 / 12  /sad',
        ]);
    }
}