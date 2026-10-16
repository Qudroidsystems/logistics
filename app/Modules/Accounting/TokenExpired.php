<?php

namespace App\Modules\Accounting;

use RuntimeException;

/** The service rejected the access token; refresh it and try once more. */
class TokenExpired extends RuntimeException {}
