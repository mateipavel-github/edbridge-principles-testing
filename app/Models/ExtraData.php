<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExtraData extends Model
{
    use HasFactory;
    protected $table = 'onet__extra_data';

    protected $fillable = [
        'onet_soc_code',
        'employment',
        'projected_employment',
        'projected_growth',
        'projected_annual_openings',
        'median_hourly_wage',
        'median_annual_wage',
    ];
}
