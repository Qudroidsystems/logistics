<?php

namespace App\Support\Database;

use Illuminate\Database\Schema\Blueprint;

/**
 * Column helpers shared by every logistics migration.
 *
 *  - publicId()   ULID exposed in URLs and APIs (bigint ids stay internal)
 *  - money()      integer kobo, never a float
 *  - geoPoint()   PostGIS geography(Point, 4326)
 *  - operatorId() tenant owner, indexed first in composite indexes
 */
class SchemaMacros
{
    public static function register(): void
    {
        if (Blueprint::hasMacro('publicId')) {
            return;
        }

        Blueprint::macro('publicId', function () {
            /** @var Blueprint $this */
            return $this->ulid('public_id')->unique();
        });

        Blueprint::macro('money', function (string $column, bool $nullable = false) {
            /** @var Blueprint $this */
            $col = $this->bigInteger($column);
            return $nullable ? $col->nullable() : $col->default(0);
        });

        Blueprint::macro('geoPoint', function (string $column, bool $nullable = true) {
            /** @var Blueprint $this */
            $col = $this->geography($column, 'point', 4326);
            return $nullable ? $col->nullable() : $col;
        });

        Blueprint::macro('operatorId', function (string $column = 'operator_id') {
            /** @var Blueprint $this */
            return $this->foreignId($column)->constrained('operators');
        });
    }
}
