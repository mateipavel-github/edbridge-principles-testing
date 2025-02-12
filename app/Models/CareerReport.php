<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CareerReport extends Model
{
    protected $fillable = [
        'student_id',
        'onet_soc_code',
        'report_template',
        'content'
    ];

    protected $casts = [
        'report_template' => 'json',
        'content' => 'json'
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
} 