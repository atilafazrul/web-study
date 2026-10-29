<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudyMaterial extends Model
{
    protected $fillable = [
        'title',
        'filename',
        'file_size',
        'slides_count',
        'mode',
        'data'
    ];

    protected $casts = [
        'data' => 'array',
        'slides_count' => 'integer'
    ];
}
