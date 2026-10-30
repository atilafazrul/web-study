<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'course',
        'category',
        'description',
        'due_date',
        'duration',
        'priority',
        'priority_score',
        'status',
        'subtasks',
        'assigned_day',
        'assigned_time',
    ];

    protected $casts = [
        'subtasks' => 'array',
        'priority_score' => 'integer',
    ];
}
