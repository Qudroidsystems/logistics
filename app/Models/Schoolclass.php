<?php
// app/Models/Schoolclass.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\DB;

class Schoolclass extends Model
{
    use HasFactory;

    protected $table = "schoolclass";

    protected $fillable = ['schoolclass', 'arm', 'description'];

    public function arms()
    {
        return $this->belongsTo(Schoolarm::class, 'arm', 'id');
    }

    public function classcategories()
    {
        return $this->belongsToMany(
            Classcategory::class,
            'schoolclass_classcategory',
            'schoolclass_id',
            'classcategory_id'
        )->withPivot('promotion_pass_average')
         ->withTimestamps();
    }

    // Add this method to handle singular queries that might be looking for 'classcategory'
    public function classcategory()
    {
        return $this->belongsToMany(
            Classcategory::class,
            'schoolclass_classcategory',
            'schoolclass_id',
            'classcategory_id'
        )->withPivot('promotion_pass_average')
         ->withTimestamps()
         ->limit(1);
    }

    public function arm()
    {
        return $this->belongsTo(Schoolarm::class, 'arm');
    }

    public function armRelation()
    {
        return $this->belongsTo(Schoolarm::class, 'arm', 'id');
    }

    public function subjectClasses()
    {
        return $this->hasMany(Subjectclass::class, 'schoolclassid', 'id');
    }

    public function studentCurrentTerms()
    {
        return $this->hasMany(StudentCurrentTerm::class, 'schoolclassId', 'id');
    }

    public function currentStudents()
    {
        return $this->hasManyThrough(
            Student::class,
            StudentCurrentTerm::class,
            'schoolclassId',
            'id',
            'id',
            'studentId'
        )->where('student_current_term.is_current', true);
    }

    public function getPromotionPassAverageAttribute()
    {
        $pivot = DB::table('schoolclass_classcategory')
            ->where('schoolclass_id', $this->id)
            ->first();

        return $pivot ? $pivot->promotion_pass_average : null;
    }

    public function setPromotionPassAverageAttribute($value)
    {
        DB::table('schoolclass_classcategory')
            ->where('schoolclass_id', $this->id)
            ->update(['promotion_pass_average' => $value]);
    }
}
