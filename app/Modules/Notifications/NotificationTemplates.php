<?php

namespace App\Modules\Notifications;

/**
 * Every message the platform sends on its own. Placeholders in {braces} are filled by NotificationService::render().
 * `email` => true also sends an email; everything is always stored in-app so the user sees it in the notification list.
 */
class NotificationTemplates
{
    public const ALL = [
        // ---- provider onboarding
        'provider.approved' => ['title' => 'You are approved', 'body' => '{name} is now active and listed. Customers can send you requests.', 'email' => true],
        'provider.changes_requested' => ['title' => 'Changes needed on your application', 'body' => 'Our team asked for changes: {note}', 'email' => true],
        'provider.rejected' => ['title' => 'Application not approved', 'body' => 'We could not approve {name}. {note}', 'email' => true],
        'provider.suspended' => ['title' => 'Your account is suspended', 'body' => '{name} has been taken out of the directory. {note}', 'email' => true],
        'provider.reinstated' => ['title' => 'Your account is active again', 'body' => '{name} is back in the directory.', 'email' => true],

        // ---- negotiation
        'request.invited' => ['title' => 'New request for you', 'body' => 'A customer posted a {type} request. Send your offer before it expires.', 'email' => false],
        'offer.received' => ['title' => 'New offer', 'body' => '{provider} offered {amount} for your request.', 'email' => false],
        'offer.counter_to_provider' => ['title' => 'Customer countered', 'body' => 'The customer proposed {amount}. Accept or reply.', 'email' => false],
        'offer.counter_to_customer' => ['title' => 'Provider countered', 'body' => '{provider} proposed {amount}. Accept or reply.', 'email' => false],

        // ---- delivery lifecycle (customer side)
        'delivery.assigned' => ['title' => 'Rider assigned', 'body' => 'Your order {order} has a rider and is on its way to pickup.', 'email' => false],
        'delivery.arrived' => ['title' => 'Rider has arrived', 'body' => 'The rider for {order} has arrived.', 'email' => false],
        'delivery.picked_up' => ['title' => 'Order picked up', 'body' => 'Order {order} is on its way. You can follow it live.', 'email' => false],
        'delivery.delivered' => ['title' => 'Delivered - please confirm', 'body' => 'Order {order} was delivered. Confirm to pay the provider, or report a problem.', 'email' => true],
        'delivery.dispute_resolved_customer' => ['title' => 'Dispute decided', 'body' => 'Our team has decided the dispute on order {order}.', 'email' => true],
        'delivery.cancelled_customer' => ['title' => 'Order cancelled', 'body' => 'Order {order} was cancelled. Any refund is on its way to your wallet.', 'email' => true],

        // ---- delivery lifecycle (provider side)
        'delivery.created_provider' => ['title' => 'New paid job', 'body' => 'Order {order} is paid and in escrow. It is ready to dispatch.', 'email' => true],
        'delivery.delivered_provider' => ['title' => 'Delivery completed', 'body' => 'Order {order} was delivered. Payment is released when the customer confirms, or automatically after the confirmation window.', 'email' => false],
        'delivery.confirmed_provider' => ['title' => 'Customer confirmed', 'body' => 'Order {order} was confirmed. Your earnings are in your wallet.', 'email' => true],
        'delivery.disputed_provider' => ['title' => 'Delivery disputed', 'body' => 'The customer reported a problem with order {order}. The payment is held until staff decide.', 'email' => true],
        'delivery.dispute_resolved_provider' => ['title' => 'Dispute decided', 'body' => 'Our team has decided the dispute on order {order}.', 'email' => true],
        'delivery.cancelled_provider' => ['title' => 'Order cancelled', 'body' => 'Order {order} was cancelled.', 'email' => true],

        // ---- money
        'payout.processing' => ['title' => 'Payout on its way', 'body' => 'Your payout of {amount} was approved and is being sent.', 'email' => false],
        'payout.paid' => ['title' => 'Payout sent', 'body' => '{amount} has been sent to your bank account.', 'email' => true],
        'payout.failed' => ['title' => 'Payout failed', 'body' => 'Your payout of {amount} could not be completed. The money is back in your wallet. {note}', 'email' => true],

        // ---- ratings
        'rating.received' => ['title' => 'New rating', 'body' => 'You received {score} out of 5 for order {order}.', 'email' => false],
    ];
}
