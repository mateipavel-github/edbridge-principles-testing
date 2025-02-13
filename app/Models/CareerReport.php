<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\URL;

class CareerReport extends Model
{
    protected $fillable = [
        'student_id',
        'onet_soc_code',
        'report_template',
        'content',
        'job_id',
        'processed_template',
        'generation_log'
    ];

    protected $casts = [
        'report_template' => 'json',
        'content' => 'json',
        'processed_template' => 'json',
        'generation_log' => 'json'
    ];

    protected $appends = ['url'];  // This will automatically append the url to the model

    public function getUrlAttribute(): string
    {
        return URL::to("/career-reports/{$this->id}");
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(Job::class);
    }

    public function addToLog(string $message): void
    {
        $this->generation_log[] = [
            'timestamp' => now()->toDateTimeString(),
            'message' => $message
        ];
        $this->save();
    }

    public function addToContent(string $sectionId, array $sectionData): void
    {
        $content = $this->content ?? [];
        $content[$sectionId] = $sectionData;
        $this->content = $content;
        $this->save();
    }

    public function updateStatus(string $status): void
    {
        $this->status = $status;
        $this->save();
    }
} 