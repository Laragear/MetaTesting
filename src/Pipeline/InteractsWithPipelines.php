<?php

namespace Laragear\MetaTesting\Pipeline;

use Illuminate\Pipeline\Pipeline;

use function is_string;

trait InteractsWithPipelines
{
    /**
     * Create a new pending test for a pipeline.
     *
     * @param  \Illuminate\Pipeline\Pipeline|class-string<\Illuminate\Pipeline\Pipeline>  $pipeline
     */
    protected function pipeline(Pipeline|string $pipeline): PendingTestPipeline
    {
        if (is_string($pipeline)) {
            $pipeline = $this->app->make($pipeline);
        }

        return new PendingTestPipeline($this->app, $pipeline);
    }
}
