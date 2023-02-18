<?php

namespace Laragear\MetaTesting\Eloquent;

use Illuminate\Database\Eloquent\Model;

trait InteractsWithCast
{
    /**
     * Create a new cast test.
     *
     * @param  string  $cast
     * @param  string  $attribute
     * @return \Laragear\MetaTesting\Eloquent\PendingTestCast
     */
    public function cast(string $cast, string $attribute = 'test'): PendingTestCast
    {
        $model = new class() extends Model
        {
            protected $table = 'cast_tests';
            public $timestamps = false;
        };

        return new PendingTestCast($model->mergeCasts([$attribute => $cast]), $attribute);
    }
}
