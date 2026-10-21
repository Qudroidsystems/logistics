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

        // ---- merchants
        'merchant.live_key_requested' => ['title' => 'Live key requested', 'body' => '{merchant} has asked for a live API key. Review the request on the merchant page.', 'email' => false],
        'merchant.live_key_decided' => ['title' => 'Your live key request', 'body' => '{message}', 'email' => true],

        // ---- negotiation
        'request.invited' => ['title' => 'New request for you', 'body' => 'A customer posted a {type} request. Send your offer before it expires.', 'email' => false],
        'offer.received' => ['title' => 'New offer', 'body' => '{provider} offered {amount} for your request.', 'email' => false],
        'offer.counter_to_provider' => ['title' => 'Customer countered', 'body' => 'The customer proposed {amount}. Accept or reply.', 'email' => false],
        'offer.counter_to_customer' => ['title' => 'Provider countered', 'body' => '{provider} proposed {amount}. Accept or reply.', 'email' => false],

        // ---- delivery lifecycle (customer side)
        'delivery.job_assigned' => ['title' => 'New job for you', 'body' => 'You have been given order {order}. Open it for the pickup details.', 'email' => false],
        'delivery.job_updated' => ['title' => 'Job details changed', 'body' => 'Dispatch updated the details of order {order}. Open it to see the new address or instructions.', 'email' => false],
        'delivery.driver_issue' => ['title' => 'Driver reported a problem', 'body' => 'On order {order}: {reason}. Open the job to decide what to do.', 'email' => true],
        'delivery.dispatch_message' => ['title' => 'Message from {name}', 'body' => 'On order {order}: {message}', 'email' => false],
        'fleet.reminder' => ['title' => '{item}: {whose}', 'body' => '{item} for {whose} {when}.', 'email' => true],
        'driver.speeding' => ['title' => 'Driver speeding', 'body' => '{name} was doing {speed} km/h (your limit is {limit} km/h). See them on the live fleet map.', 'email' => false],
        'delivery.driver_released' => ['title' => 'Driver handed a job back', 'body' => 'Order {order} needs a new driver ({reason}). Assign someone from the job page.', 'email' => true],
        'delivery.failed_customer' => ['title' => 'We could not deliver', 'body' => 'Delivery of order {order} did not go through because the receiver could not be reached. Your payment has been settled under the agreement you accepted; open the order to see the details.', 'email' => true],
        'delivery.failed_provider' => ['title' => 'Delivery failed', 'body' => 'Order {order} could not be delivered: the receiver was not reachable. The agreement terms have been applied.', 'email' => false],
        'delivery.returned_customer' => ['title' => 'Parcel returned', 'body' => 'The parcel for order {order} has been taken back to the pickup address.', 'email' => false],
        'delivery.at_pickup_point_customer' => ['title' => 'Waiting at the pickup point', 'body' => 'The parcel for order {order} has reached the pickup point. The receiver has been texted a code to collect it.', 'email' => false],
        'delivery.returned_provider' => ['title' => 'Parcel returned', 'body' => 'The driver has returned the parcel for order {order} to the pickup address.', 'email' => false],
        'delivery.assigned' => ['title' => 'Rider assigned', 'body' => 'Your order {order} has a rider and is on its way to pickup.', 'email' => false],
        'delivery.approaching' => ['title' => 'Your rider is close', 'body' => 'The rider for {order} is {when} from {place}.', 'email' => false],
        'delivery.delayed' => ['title' => 'Your rider is running late', 'body' => 'The rider for {order} is held up. Now expected at {place} in {when}.', 'email' => false],
        'driver.receiver_replied' => ['title' => 'The receiver replied', 'body' => 'Order {order}: {reply}', 'email' => false],
        'delivery.arrived' => ['title' => 'Rider has arrived', 'body' => 'The rider for {order} has arrived.', 'email' => false],
        'delivery.picked_up' => ['title' => 'Order picked up', 'body' => 'Order {order} is on its way. You can follow it live.', 'email' => false],
        'cod.collected' => ['title' => 'Cash collected', 'body' => 'The rider collected {amount} for order {order}. It will be paid to you by {provider}.', 'email' => false],
        'cod.paid' => ['title' => 'Cash on delivery paid', 'body' => '{provider} paid you {amount} for {count} delivery(ies). Reference: {reference}.', 'email' => true],
        'cod.overdue' => ['title' => 'Cash not handed in', 'body' => '{rider} has held {amount} of cash on delivery for over a day.', 'email' => false],
        'delivery.delivered' => ['title' => 'Delivered - please confirm', 'body' => 'Order {order} was delivered. Confirm to pay the provider, or report a problem.', 'email' => true],
        'delivery.dispute_resolved_customer' => ['title' => 'Dispute decided', 'body' => 'Our team has decided the dispute on order {order}.', 'email' => true],
        'delivery.cancelled_customer' => ['title' => 'Order cancelled', 'body' => 'Order {order} was cancelled. Any refund is on its way to your wallet.', 'email' => true],

        // ---- delivery lifecycle (provider side)
        'delivery.created_provider' => ['title' => 'New paid job', 'body' => 'Order {order} is paid and in escrow. It is ready to dispatch.', 'email' => true],
        'delivery.delivered_provider' => ['title' => 'Delivery completed', 'body' => 'Order {order} was delivered. Payment is released when the customer confirms, or automatically after the confirmation window.', 'email' => false],
        'delivery.confirmed_provider' => ['title' => 'Customer confirmed', 'body' => 'Order {order} was confirmed. Your earnings are in your wallet.', 'email' => true],
        'delivery.disputed_provider' => ['title' => 'Delivery disputed', 'body' => 'The customer reported a problem with order {order}. The payment is held until staff decide.', 'email' => true],
        'delivery.dispute_resolved_provider' => ['title' => 'Dispute decided', 'body' => 'Our team has decided the dispute on order {order}.', 'email' => true],
        'delivery.exception_provider' => ['title' => 'Driver needs an exception', 'body' => 'On order {order} the driver cannot provide: {proof}. Approve or decline it on the job page.', 'email' => false],
        'delivery.cancelled_provider' => ['title' => 'Order cancelled', 'body' => 'Order {order} was cancelled.', 'email' => true],

        // ---- money
        'payout.processing' => ['title' => 'Payout on its way', 'body' => 'Your payout of {amount} was approved and is being sent.', 'email' => false],
        'referral.rewarded' => ['title' => 'You earned a referral credit', 'body' => '{friend} finished their first delivery, so {amount} is now in your wallet.', 'email' => true],
        'review.request' => ['title' => 'How did we do?', 'body' => 'Thanks for using {provider}. Would you leave a quick Google review? {link}', 'email' => true],
        'loyalty.earned' => ['title' => 'Points earned', 'body' => 'You earned {points} loyalty points. You now have {balance}.', 'email' => false],
        'payout.paid' => ['title' => 'Payout sent', 'body' => '{amount} has been sent to your bank account.', 'email' => true],
        'payout.failed' => ['title' => 'Payout failed', 'body' => 'Your payout of {amount} could not be completed. The money is back in your wallet. {note}', 'email' => true],

        // ---- teams
        'team.invited' => ['title' => 'Team invitation', 'body' => '{provider} invited you to join as {role}.', 'email' => false],
        'team.joined' => ['title' => 'New team member', 'body' => '{name} joined your team as {role}.', 'email' => false],

        // ---- ratings
        'rating.received' => ['title' => 'New rating', 'body' => 'You received {score} out of 5 for order {order}.', 'email' => false],
    ];
}
