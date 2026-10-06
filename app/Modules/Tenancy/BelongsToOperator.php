<?php

namespace App\Modules\Tenancy;

use App\Modules\Tenancy\Models\Operator;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Use on every model whose table carries operator_id. */
trait BelongsToOperator
{
    public static function bootBelongsToOperator(): void
    {
        static::addGlobalScope(new OperatorScope());

        static::creating(function ($model) {
            if (empty($model->operator_id)) {
                $current = app(CurrentOperator::class)->id();
                if ($current !== null) {
                    $model->operator_id = $current;
                }
            }
        });
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }
}
