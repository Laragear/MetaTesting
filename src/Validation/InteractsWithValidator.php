<?php

namespace Laragear\MetaTesting\Validation;

use function is_array;

/**
 * @internal
 */
trait InteractsWithValidator
{
    /**
     * Runs the validation name with data and rules
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
     */
    protected function assertValidationPasses(array|string $data, array|string $rules): void
    {
        static::assertFalse($this->runValidation($data, $rules), 'The rule has not passed validation.');
    }

    /**
     * Assert a given rule fails.
     */
    protected function assertValidationFails(array|string $data, array|string $rules): void
    {
        static::assertTrue($this->runValidation($data, $rules), 'The rule has not failed validation.');
    }
}
