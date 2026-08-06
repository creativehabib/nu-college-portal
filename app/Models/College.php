<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class College extends Model
{
    protected $guarded = ['id'];

    public function subjects(): HasMany
    {
        return $this->hasMany(CollegeSubject::class);
    }

    public function userDetail()
    {
        return $this->hasOne(CollegeUserDetail::class, 'college_id');
    }

    public function collegeSubjects()
    {
        return $this->hasMany(CollegeSubject::class, 'college_id');
    }
}
