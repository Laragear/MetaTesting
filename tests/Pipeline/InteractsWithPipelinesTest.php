<?php

namespace Tests\Pipeline;

use Illuminate\Container\Container;
use Illuminate\Pipeline\Pipeline;
use Laragear\MetaTesting\Pipeline\InteractsWithPipelines;
use Mockery;
use Tests\TestCase;

class InteractsWithPipelinesTest extends TestCase
{
    use InteractsWithPipelines;

    public function test_creates_pipeline_through_container(): void
    {
        $original = $this->app;

        $this->app = Mockery::mock(Container::class);
        $this->app
            ->expects('make')
            ->with(TestPipelineForTrait::class)
            ->andReturn(new TestPipelineForTrait());

        $this->pipeline(TestPipelineForTrait::class);

        $this->app = $original;
    }

    public function test_doesnt_creates_pipeline_if_object(): void
    {
        $original = $this->app;

        $this->app = Mockery::mock(Container::class);
        $this->app->expects('make')->never();

        $this->pipeline(new TestPipelineForTrait());

        $this->app = $original;
    }
}

class TestPipelineForTrait extends Pipeline
{
    //
}
