<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Bank, tax and pension details for a staff member (staffbioinfo.id).
 * The account number is stored encrypted; only the last 4 digits are shown.
 */
class StaffPayProfile extends Model
{
    protected $fillable = [
        'staff_id', 'employment_type', 'bank_code', 'bank_name', 'account_name', 'account_verified_at',
        'paystack_recipient_code', 'tin', 'tax_state', 'pfa_name', 'rsa_pin', 'nhf_number',
        'paye_enabled', 'pension_enabled', 'nhf_enabled', 'nhia_enabled', 'annual_rent', 'rent_evidence',
        'other_reliefs_annual', 'pay_status', 'hold_reason', 'exit_date', 'updated_by',
    ];

    protected $hidden = ['account_number'];

    protected $casts = [
        'paye_enabled' => 'boolean', 'pension_enabled' => 'boolean', 'nhf_enabled' => 'boolean', 'nhia_enabled' => 'boolean',
        'annual_rent' => 'float', 'other_reliefs_annual' => 'float', 'account_verified_at' => 'datetime', 'exit_date' => 'date',
    ];

    public const TYPES = ['full_time' => 'Full-time', 'part_time' => 'Part-time', 'contract' => 'Contract'];

    public function accountNumber(): ?string
    {
        if (empty($this->attributes['account_number'])) return null;
        try { return Crypt::decryptString($this->attributes['account_number']); } catch (\Throwable $e) { return null; }
    }

    public function setAccount(?string $number): void
    {
        $digits = preg_replace('/\D/', '', (string) $number);
        $changed = $digits !== (string) $this->accountNumber();
        $this->attributes['account_number'] = $digits ? Crypt::encryptString($digits) : null;
        $this->account_last4 = $digits ? substr($digits, -4) : null;
        if ($changed) {
            // A new account must be checked again and gets a new Paystack payee.
            $this->account_verified_at = null;
            $this->paystack_recipient_code = null;
        }
    }

    public function maskedAccount(): ?string
    {
        return $this->account_last4 ? '******' . $this->account_last4 : null;
    }

    /** What's missing before this person can be paid correctly. */
    public function gaps(): array
    {
        $g = [];
        if (!$this->account_last4) $g[] = 'bank account';
        elseif (!$this->account_verified_at) $g[] = 'account not verified';
        if ($this->paye_enabled && !$this->tin) $g[] = 'TIN';
        if ($this->paye_enabled && !$this->tax_state) $g[] = 'tax state';
        if ($this->pension_enabled && (!$this->rsa_pin || !$this->pfa_name)) $g[] = 'pension details';
        if ($this->nhf_enabled && !$this->nhf_number) $g[] = 'NHF number';
        return $g;
    }
}
