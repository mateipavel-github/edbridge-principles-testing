<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OccupationData extends Model
{
    use HasFactory;

    protected $table = 'onet__occupation_data';
    protected $primaryKey = 'onetsoc_code';
    public $incrementing = false; // Primary key is not auto-incrementing
    protected $keyType = 'string';

    protected $fillable = [
        'onetsoc_code',
        'title',
        'description',
    ];
}
