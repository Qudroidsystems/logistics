<?php

namespace App\Modules\Dispatch;

/**
 * The order to visit the stops of a driver's jobs: each job's stops stay in their own order (a parcel is never
 * dropped before it is picked up), and among the stops that can come next the nearest goes first. Greedy nearest
 * neighbour: not a perfect route, but it stops a driver crossing town and back with three parcels from one shop.
 */
class RunPlanner
{
    /**
     * @param  array<int, array{key:string, job:string, seq:int, lat:?float, lng:?float}>  $stops  stops still to do
     * @return array<int, string> stop keys in visiting order
     */
    public static function order(?float $fromLat, ?float $fromLng, array $stops): array
    {
        $byJob = [];
        foreach ($stops as $s) {
            $byJob[$s['job']][] = $s;
        }
        foreach ($byJob as &$list) {
            usort($list, fn ($a, $b) => $a['seq'] <=> $b['seq']);
        }
        unset($list);

        $out = [];
        $here = $fromLat !== null && $fromLng !== null ? [$fromLat, $fromLng] : null;
        while ($byJob) {
            $best = null;
            $bestD = INF;
            foreach ($byJob as $job => $list) {
                $c = $list[0];
                // Without a position to start from, the first job's next stop goes first (input order).
                $d = ($here && $c['lat'] !== null && $c['lng'] !== null)
                    ? ManualAssignmentService::metresBetween($here[0], $here[1], (float) $c['lat'], (float) $c['lng'])
                    : ($here ? INF - 1 : (float) count($out));
                if ($best === null || $d < $bestD) {
                    $best = $job;
                    $bestD = $d;
                }
            }
            $next = array_shift($byJob[$best]);
            $out[] = $next['key'];
            if ($next['lat'] !== null && $next['lng'] !== null) {
                $here = [(float) $next['lat'], (float) $next['lng']];
            }
            if (! $byJob[$best]) {
                unset($byJob[$best]);
            }
        }

        return $out;
    }
}
