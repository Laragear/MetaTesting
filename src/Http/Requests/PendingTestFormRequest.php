<?php

namespace Laragear\MetaTesting\Http\Requests;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Auth\Authenticatable as UserContract;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Assert as PHPUnit;

use function get_class;

/**
 * @template TForm of \Illuminate\Foundation\Http\FormRequest
 *
 * @internal
 */
class PendingTestFormRequest
{
    /**
     * Create a new Pending Test.
     */
    public function __construct(protected TestCase $testCase, protected FormRequest $formRequest)
    {
        //
    }

    /**
     * Returns the underlying Form Request instance.
     *
     * @return TForm
     */
    public function getFormRequest(): FormRequest
    {
        return $this->formRequest;
    }

    /**
     * Resolve the form request using a PHPUnit callback assertion.
     */
    protected function resolveValidation(): void
    {
        PHPUnit::assertThat(null, PHPUnit::callback(function (): bool {
            $this->formRequest->validateResolved();

            return true;
        }));
    }

    /**
     * Set the currently logged in user for the application.
     *
     * @return $this
     */
    public function actingAs(UserContract $user, string $guard = null): static
    {
        return $this->be($user, $guard);
    }

    /**
     * Set the currently logged in user for the application.
     *
     * @return $this
     */
    public function be(UserContract $user, string $guard = null): static
    {
        $this->testCase->be($user, $guard);

        return $this;
    }

    /**
     * Assert that the Form Request allows authorization.
     *
     * @return $this
     */
    public function assertAllowed(): static
    {
        try {
            $this->resolveValidation();
        } catch (AuthorizationException) {
            $form = get_class($this->formRequest);

            PHPUnit::fail("The Form Request '$form' fails authorization.");
        }

        return $this;
    }

    /**
     * Assert that the Form Request denies authorization.
     *
     * @return $this
     */
    public function assertDenied(): static
    {
        try {
            $this->resolveValidation();
        } catch (AuthorizationException) {
            return $this;
        }

        $form = get_class($this->formRequest);

        PHPUnit::fail("The Form Request '$form' passes authorization.");
    }

    /**
     * Assert that the Form Request validation passes.
     *
     * @return $this
     */
    public function assertValidationPasses(): static
    {
        try {
            $this->resolveValidation();
        } catch (ValidationException) {
            $form = get_class($this->formRequest);

            PHPUnit::fail("The Form Request '$form' fails validation.");
        }

        return $this;
    }

    /**
     * Asserts that the form validation fails.
     *
     * @return $this
     */
    public function assertValidationFails(): static
    {
        try {
            $this->resolveValidation();
        } catch (ValidationException) {
            return $this;
        }

        $form = get_class($this->formRequest);

        PHPUnit::fail("The Form Request '$form' passes validation.");
    }

    /**
     * Assert the Form Request is authorized and validated.
     *
     * @return $this
     */
    public function assertOk(): static
    {
        try {
            $this->resolveValidation();
        } catch (AuthorizationException) {
            $form = get_class($this->formRequest);

            PHPUnit::fail("The Form Request '$form' fails authorization.");
        } catch (ValidationException) {
            $form = get_class($this->formRequest);

            PHPUnit::fail("The Form Request '$form' fails validation.");
        }

        return $this;
    }

    /**
     * Checks the form data is equal to the keys issued.
     *
     * @return $this
     */
    public function assertFormData(array $data): static
    {
        $this->assertValidationPasses();

        foreach ($data as $key => $item) {
            PHPUnit::assertTrue($this->formRequest->has($key), "The form doesn't have the key '$key'.");
            PHPUnit::assertEquals(
                $item, $this->formRequest->get($key), "The form '$key' is not equal to the value issued."
            );
        }

        return $this;
    }
}
