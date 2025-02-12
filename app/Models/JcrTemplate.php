<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JcrTemplate extends Model
{
    protected $fillable = ['name', 'content', 'is_default'];

    protected $casts = [
        'is_default' => 'boolean'
    ];

    public function setContentAttribute($value)
    {
        $this->attributes['content'] = is_string($value) ? $value : json_encode($value);
    }

    public function getContentAttribute($value)
    {
        return json_decode($value, true);
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($template) {
            if ($template->is_default) {
                // Set all other templates to not default
                static::where('id', '!=', $template->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
} 