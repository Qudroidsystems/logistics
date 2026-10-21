<?php

/*
| Live tracking: the fleet map and speed monitoring.
*/
return [
    // A fresh position faster than this marks the driver as speeding on the fleet map and raises a "speeding" flag
    // for staff to review (at most once per driver per `speeding_cooldown_minutes`).
    'speed_limit_kmh' => (int) env('TRACKING_SPEED_LIMIT_KMH', 80),
    'speeding_cooldown_minutes' => (int) env('TRACKING_SPEEDING_COOLDOWN_MINUTES', 15),

    // How often the fleet map asks for new positions, in seconds. The driver app sends one every 8-10 s.
    'fleet_poll_seconds' => (int) env('TRACKING_FLEET_POLL_SECONDS', 10),
];
