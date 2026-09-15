<?php

namespace Tests\Validation;

use Illuminate\Support\Facades\Validator;
use Laragear\MetaTesting\Validation\InteractsWithValidator;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\AssertionFailedError;

class InteractsWithValidatorTest extends TestCase
{
    use InteractsWithValidator;

    protected function setUp(): void
    {
        parent::setUp();

        Validator::extend('test_rule', function ($key, $value): bool {
            return $value === 'bar';
        }, 'test failed');
    }

    public function test_validates_rule(): void
    {
        $this->assertValidationPasses(['foo' => 'bar'], ['foo' => 'test_rule']);
        $this->assertValidationFails(['foo' => 'invalid'], ['foo' => 'test_rule']);
    }

    public function test_validates_simple_rule(): void
    {
        $this->assertValidationPasses('bar', 'test_rule');
        $this->assertValidationFails('invalid', 'test_rule');
    }

    public function test_validation_passes_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The rule has not passed validation.');

        $this->assertValidationPasses(['foo' => 'invalid'], ['foo' => 'test_rule']);
    }

    public function test_validation_fails_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The rule has not failed validation.');

        $this->assertValidationFails(['foo' => 'bar'], ['foo' => 'test_rule']);
    }

    public function test_pending_validation_passes(): void
    {
        $pending = $this->validation(['foo' => 'bar'], ['foo' => 'test_rule']);

        $pending->assertPasses();
    }

    public function test_pending_validation_does_not_passes(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(<<<'MESSAGE'
The rule [foo] didn't pass validation. Errors:
- test failed
MESSAGE
        );

        $pending = $this->validation(['foo' => 'quz'], ['foo' => 'test_rule']);

        $pending->assertPasses();
    }

    public function test_pending_validation_fails(): void
    {
        $pending = $this->validation(['foo' => 'quz'], ['foo' => 'test_rule']);

        $pending->assertFails();
    }

    public function test_pending_validation_does_not_fail(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The rule [foo] passed validation.');

        $pending = $this->validation(['foo' => 'bar'], ['foo' => 'test_rule']);

        $pending->assertFails();
    }

    public function test_pending_validation_fails_with_message(): void
    {
        $pending = $this->validation(['foo' => 'quz'], ['foo' => 'test_rule']);

        $pending->assertFails('test failed');
    }

    public function test_pending_validation_does_not_fail_with_message(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage('The rule [foo] passed validation.');

        $pending = $this->validation(['foo' => 'bar'], ['foo' => 'test_rule']);

        $pending->assertFails('test failed');
    }

    public function test_pending_validation_without_message(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage(<<<'MESSAGE'
No message is equal to [invalid]. Found:
- test failed
MESSAGE
        );

        $pending = $this->validation(['foo' => 'quz'], ['foo' => 'test_rule']);

        $pending->assertFails('invalid');
    }
}
