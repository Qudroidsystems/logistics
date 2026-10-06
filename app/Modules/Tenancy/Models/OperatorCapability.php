<?php

namespace App\Modules\Tenancy\Models;

use Illuminate\Database\Eloquent\Model;

class OperatorCapability extends Model
{
    public const OWN_FLEET = 'own_fleet';
    public const ACCEPT_MARKETPLACE_JOBS = 'accept_marketplace_jobs';
    public const POST_MARKETPLACE_JOBS = 'post_marketplace_jobs';
    public const COD = 'cod';
    public const CORPORATE_BILLING = 'corporate_billing';
    public const HUBS = 'hubs';
    public const STORES = 'stores';
    public const API_ACCESS = 'api_access';
    public const LIST_IN_DIRECTORY = 'list_in_directory';
    public const NEGOTIATE = 'negotiate';
    public const INSTANT_BOOK = 'instant_book';
    public const SHOPPING_ERRANDS = 'shopping_errands';
    public const PARTNER_API = 'partner_api';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['enabled' => 'boolean', 'limits' => 'array'];
    }

    /** Default capabilities granted when an operator of this type is approved. */
    public static function defaultsFor(string $type): array
    {
        return match ($type) {
            Operator::TYPE_PLATFORM => [
                self::OWN_FLEET, self::ACCEPT_MARKETPLACE_JOBS, self::POST_MARKETPLACE_JOBS, self::COD,
                self::CORPORATE_BILLING, self::HUBS, self::STORES, self::API_ACCESS,
                self::LIST_IN_DIRECTORY, self::NEGOTIATE, self::INSTANT_BOOK, self::SHOPPING_ERRANDS,
            ],
            Operator::TYPE_COMPANY, Operator::TYPE_FRANCHISE => [
                self::OWN_FLEET, self::ACCEPT_MARKETPLACE_JOBS, self::LIST_IN_DIRECTORY, self::NEGOTIATE,
                self::INSTANT_BOOK, self::COD,
            ],
            Operator::TYPE_INDEPENDENT_DRIVER => [
                self::ACCEPT_MARKETPLACE_JOBS, self::LIST_IN_DIRECTORY, self::NEGOTIATE,
            ],
            Operator::TYPE_MARKET_SHOPPER => [
                self::ACCEPT_MARKETPLACE_JOBS, self::LIST_IN_DIRECTORY, self::NEGOTIATE, self::SHOPPING_ERRANDS,
            ],
            Operator::TYPE_MERCHANT => [self::POST_MARKETPLACE_JOBS, self::API_ACCESS, self::PARTNER_API],
            Operator::TYPE_CORPORATE_FLEET => [self::OWN_FLEET, self::POST_MARKETPLACE_JOBS, self::CORPORATE_BILLING],
            default => [],
        };
    }
}
