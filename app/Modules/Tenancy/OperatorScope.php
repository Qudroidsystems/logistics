<?php

namespace App\Modules\Tenancy;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts every tenant-owned query to the current operator.
 * With no operator and no bypass the query matches nothing, so a missing context fails closed.
 */
class OperatorScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $current = app(CurrentOperator::class);

        if ($current->bypassed()) {
            return;
        }

        $column = $model->qualifyColumn('operator_id');

        if ($current->id() === null) {
            $builder->whereRaw('1 = 0');
            return;
        }

        $builder->where($column, $current->id());
    }
}
