<?php

/*
| How this install runs.
|   company      one company with its own fleet: every customer request goes to the house operator,
|                and the provider directory, public provider pages and provider sign-up are switched off.
|   marketplace  many providers (companies, riders, market shoppers) compete for customer requests.
|   both         marketplace, with the house operator also taking jobs (default).
*/
return [
    'mode' => env('PLATFORM_MODE', 'both'),

    // Company mode: the id of the approved company operator that owns the fleet. Register and approve
    // the company while the mode is still "both", then put its operator id here and switch to "company".
    'house_operator_id' => env('PLATFORM_HOUSE_OPERATOR_ID') ? (int) env('PLATFORM_HOUSE_OPERATOR_ID') : null,
];
