<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollegeSubject extends Model
{
    protected $guarded = ['id'];

    public function college()
    {
        return $this->belongsTo(College::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
