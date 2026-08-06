<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CollegeUserDetail extends Model
{
    protected $guarded = [];

    public function college()
    {
        return $this->belongsTo(College::class, 'college_id');
    }
}
