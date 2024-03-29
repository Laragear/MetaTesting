<?php

namespace Laragear\MetaTesting\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Traits\ForwardsCalls;
use PHPUnit\Framework\Assert as PHPUnit;

use function is_array;

class PendingTestCast
{
    use ForwardsCalls;

    /**
     * Create a new Pending test for cast.
     */
    public function __construct(protected Model $model, protected string $attribute)
    {
        //
    }

    /**
     * Sets the initial raw attributes for the underlying test model.
     *
     * @return $this
     */
    public function withRawAttributes(array $attributes): static
    {
        $this->model->setRawAttributes($attributes);

        return $this;
    }

    /**
     * Assert that a cast receives a value and transforms it.
     *
     * @return $this
     */
    public function assertCastTo(mixed $incoming, mixed $expected): static
    {
        $clone = (clone $this->model)->setAttribute($this->attribute, $incoming);

        if (! is_array($expected)) {
            $expected = [$this->attribute => $expected];
        }

        foreach ($expected as $item => $value) {
            PHPUnit::assertEquals(
                $value,
                $clone->getAttribute($item),
                "Failed to assert that the attribute '$item' casts into the expected value."
            );
        }

        return $this;
    }

    /**
     * Assert that the given value get cast into a raw value.
     *
     * @return $this
     */
    public function assertCastToRaw(mixed $incoming, mixed $expected): static
    {
        $clone = (clone $this->model)->setAttribute($this->attribute, $incoming);

        if (! is_array($expected)) {
            $expected = [$this->attribute => $expected];
        }

        foreach ($expected as $item => $value) {
            PHPUnit::assertEquals(
                $value,
                $clone->getAttributes()[$item],
                "Failed to assert that the attribute '$item' casts into the expected raw value."
            );
        }

        return $this;
    }
}
