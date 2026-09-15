<?php

namespace Laragear\MetaTesting\Validation;

use function array_key_first;
use function is_array;

/**
 * @internal
 */
trait InteractsWithValidator
{
    /**
     * Creates a pending validation rule.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string, string|array<int, string|\Illuminate\Validation\Rule>>  $rule
     *
     * @example $this->validationRule(['foo' => 'bar'], ['foo' => 'my-custom-rule:with_params']);
     */
    public function validation(array $data, array $rule): PendingTestValidation
    {
        return new PendingTestValidation(
            $this, $this->app->make('validator')->make($data, $rule), array_key_first($rule)
        );
    }

    /**
     * Runs the validation name with data and rules.
     *
     * @internal
     */
    protected function runValidation(array|string $data, array|string $rules): bool
    {
        if (! is_array($data) && ! is_array($rules)) {
            [$data, $rules] = [[$rules => $data], [$rules => $rules]];
        }

        return $this->app->make('validator')->make($data, $rules)->fails();
    }

    /**
     * Assert a given rule passes.
     *
     * @deprecated Use validationRule
     * @see $this->validate(...)
     */
    protected function assertValidationPasses(array|string $data, array|string $rules): void
    {
        static::assertFalse($this->runValidation($data, $rules), 'The rule has not passed validation.');
    }

    /**
     * Assert a given rule fails.
     *
     * @deprecated Use validationRule
     * @see $this->validate(...)
     */
    protected function assertValidationFails(array|string $data, array|string $rules): void
    {
        static::assertTrue($this->runValidation($data, $rules), 'The rule has not failed validation.');
    }
}
