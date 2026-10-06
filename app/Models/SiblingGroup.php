<?php
// app/Models/SiblingGroup.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SiblingGroup extends Model
{
    use HasFactory;

    protected $table = 'sibling_groups';

    protected $fillable = [
        'group_no', 'family_name', 'parent_phone', 'parent_email',
        'address', 'total_children', 'discount_type', 'discount_value', 'primary_contact_id'
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'total_children' => 'integer',
    ];

    // IMPORTANT: Use the correct pivot table name 'sibling_group_students'
    public function students()
    {
        return $this->belongsToMany(Student::class, 'sibling_group_students', 'sibling_group_id', 'student_id')
                    ->withTimestamps()
                    ->withPivot('birth_order'); // If you have birth_order column
    }

    public function primaryContact()
    {
        return $this->belongsTo(User::class, 'primary_contact_id');
    }

    public function discountAssignments()
    {
        return $this->hasMany(DiscountAssignment::class, 'sibling_group_id');
    }

    // Get all siblings of a student
    public static function getSiblings($studentId)
    {
        $group = self::whereHas('students', function($q) use ($studentId) {
            $q->where('student_id', $studentId);
        })->first();

        if (!$group) return collect([]);

        return $group->students()->where('student_id', '!=', $studentId)->get();
    }

    // Calculate sibling discount for a student
    public function calculateSiblingDiscount($studentId)
    {
        $siblings = $this->students;
        $siblingCount = $siblings->count();
        $studentOrder = 0;

        foreach ($siblings as $index => $sibling) {
            if ($sibling->id == $studentId) {
                $studentOrder = $index + 1;
                break;
            }
        }

        if ($this->discount_type === 'percentage') {
            $discountValue = $this->discount_value;
            // Additional discount for later children
            if ($studentOrder > 1) {
                $discountValue += ($studentOrder - 1) * 5; // Additional 5% per child
            }
            return min($discountValue, 50); // Cap at 50%
        }

        // Fixed amount per child
        return $this->discount_value;
    }
}
