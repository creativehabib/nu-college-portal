<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    protected $guarded = ['id'];

    public function collegeSubjects()
    {
        return $this->hasMany(CollegeSubject::class);
    }
}
