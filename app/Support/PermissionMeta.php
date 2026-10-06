<?php

namespace App\Support;

/**
 * Turns a raw permission name (e.g. "View student", "Manage payroll settings")
 * into a friendly action chip + a plain-English description, so the role editor
 * can explain what each permission actually allows. Works for every permission
 * with no database changes; specific names can be given an exact description in
 * the OVERRIDES map below.
 */
class PermissionMeta
{
    /** Verb => [chip label, sentence template]. {x} = the rest of the permission name. */
    protected const VERBS = [
        'View'     => ['View',    'See {x} — read-only, no changes.'],
        'Show'     => ['View',    'See {x}.'],
        'Preview'  => ['Preview', 'Preview {x} before it is finalised.'],
        'Create'   => ['Create',  'Add new {x}.'],
        'Add'      => ['Add',     'Add {x}.'],
        'Update'   => ['Edit',    'Edit existing {x}.'],
        'Edit'     => ['Edit',    'Edit existing {x}.'],
        'Manage'   => ['Manage',  'Full control of {x} — add, edit and remove.'],
        'Delete'   => ['Delete',  'Permanently delete {x}. This cannot be undone.'],
        'Remove'   => ['Remove',  'Remove {x}.'],
        'Approve'  => ['Approve', 'Approve {x}.'],
        'Submit'   => ['Submit',  'Submit {x} for approval.'],
        'Release'  => ['Release', 'Release {x} (money leaves the account).'],
        'Pay'      => ['Pay',     'Pay {x}.'],
        'Post'     => ['Post',    'Post {x} to the records / ledger.'],
        'Close'    => ['Close',   'Close {x}.'],
        'Download' => ['Download','Download {x}.'],
        'Print'    => ['Print',   'Print {x}.'],
        'Generate' => ['Generate','Generate {x}.'],
        'Export'   => ['Export',  'Export {x}.'],
        'Import'   => ['Import',  'Import {x}.'],
        'Send'     => ['Send',    'Send {x}.'],
        'Assign'   => ['Assign',  'Assign {x}.'],
    ];

    /** Exact-name overrides for permissions the generic rule can't phrase well. */
    protected const OVERRIDES = [
        'dashboard'                 => ['View',   'Open the main administration dashboard.'],
        'finance dashboard'         => ['View',   'See the finance analytics dashboard.'],
        'academics dashboard'       => ['View',   'See the academics analytics dashboard.'],
        'View management dashboard' => ['View',   'See the management overview dashboard.'],
        'Add user-role'             => ['Assign', 'Add users to this role.'],
        'Update user-role'          => ['Edit',   'Change the users assigned to this role.'],
        'Remove user-role'          => ['Remove', 'Remove users from this role.'],
        'Manage maintenance mode'   => ['Manage', 'Turn the portal\'s maintenance mode on or off.'],
        'Manage feature flags'      => ['Manage', 'Switch portal modules on or off (module access).'],
        'Release salary payments'   => ['Release','Release staff salary payments — the money is sent.'],
        'Manage payment gateways'   => ['Manage', 'Configure the payment gateways (Paystack, OPay).'],
        'Close accounting period'   => ['Close',  'Lock the accounting books up to a date.'],
        'Post journal entries'      => ['Post',   'Post entries directly into the general ledger.'],
    ];

    /** ['action' => chip word, 'label' => readable name, 'desc' => sentence] */
    public static function for(string $name): array
    {
        $label = ucfirst(trim($name));

        if (isset(self::OVERRIDES[$name])) {
            [$action, $desc] = self::OVERRIDES[$name];
            return ['action' => $action, 'label' => $label, 'desc' => $desc];
        }

        $parts = explode(' ', trim($name), 2);
        $verb = ucfirst(strtolower($parts[0]));
        $rest = isset($parts[1]) ? self::pretty($parts[1]) : '';

        if (isset(self::VERBS[$verb]) && $rest !== '') {
            [$chip, $tpl] = self::VERBS[$verb];
            return ['action' => $chip, 'label' => $label, 'desc' => str_replace('{x}', $rest, $tpl)];
        }

        return ['action' => 'Access', 'label' => $label, 'desc' => 'Grants the “' . $label . '” permission.'];
    }

    public static function action(string $name): string
    {
        return self::for($name)['action'];
    }

    /** "student-report" / "online-fee-payments" => "student report / online fee payments" */
    protected static function pretty(string $s): string
    {
        return trim(strtolower(str_replace(['-', '_'], ' ', $s)));
    }

    /** Chip colour class by action word. */
    public static function chipClass(string $action): string
    {
        return [
            'View' => 'pm-chip-view', 'Preview' => 'pm-chip-view',
            'Create' => 'pm-chip-create', 'Add' => 'pm-chip-create',
            'Edit' => 'pm-chip-edit', 'Assign' => 'pm-chip-edit',
            'Manage' => 'pm-chip-manage',
            'Delete' => 'pm-chip-delete', 'Remove' => 'pm-chip-delete', 'Close' => 'pm-chip-delete',
            'Approve' => 'pm-chip-approve', 'Submit' => 'pm-chip-approve', 'Post' => 'pm-chip-approve',
            'Release' => 'pm-chip-money', 'Pay' => 'pm-chip-money',
        ][$action] ?? 'pm-chip-other';
    }
}
