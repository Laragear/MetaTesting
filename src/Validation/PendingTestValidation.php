<?php

namespace Laragear\MetaTesting\Validation;

use Illuminate\Contracts\Validation\Validator;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\ExpectationFailedException;

use function implode;

use const PHP_EOL;

class PendingTestValidation
{
    /**
     * Create a new Pending Test Validation instance.
     */
    public function __construct(
        protected TestCase $testCase,
        protected Validator $validator,
        protected string $key,
    ) {
        //
    }

    /**
     * Assert a given rule passes.
     */
    public function assertPasses(): void
    {
        $this->testCase::assertFalse(
            $this->validator->fails(),
            "The rule [$this->key] didn't pass validation. Errors:".PHP_EOL.'- '.
            implode(PHP_EOL.'- ', $this->validator->getMessageBag()->all())
        );
    }

    /**
     * Assert a given rule fails, optionally with the given message.
     */
    public function assertFails(?string $message = null): void
    {
        $this->testCase::assertTrue($this->validator->fails(), "The rule [$this->key] passed validation.");

        if ($message === null) {
            return;
        }

        $messages = $this->validator->getMessageBag()->get($this->key);

        foreach ($messages as $output) {
            try {
                $this->testCase::assertSame($message, $output);

                return;
            } catch (ExpectationFailedException) {
                continue;
            }
        }

        $this->testCase::fail(
            "No message is equal to [$message]. Found:".PHP_EOL.'- '.implode(PHP_EOL.'- ', $messages)
        );
    }
}
