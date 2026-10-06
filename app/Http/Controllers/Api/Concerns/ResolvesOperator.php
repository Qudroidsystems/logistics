<?php

namespace App\Http\Controllers\Api\Concerns;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

trait ResolvesOperator
{
    /** The operator the signed-in user is acting for, or 403 when they hold none of the allowed roles there. */
    protected function operatorId(Request $request, array $roles = ['owner', 'admin', 'finance', 'dispatcher']): int
    {
        $operatorId = (int) $request->user()->current_operator_id;
        $ok = $operatorId && DB::table('operator_members')->where(['operator_id' => $operatorId, 'user_id' => $request->user()->id, 'status' => 'active'])->whereIn('role', $roles)->exists();
        abort_unless($ok, 403, 'You do not have access to this account.');

        return $operatorId;
    }
}
