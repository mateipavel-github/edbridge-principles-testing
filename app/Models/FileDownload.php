<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FileDownload extends Model
{
    protected $fillable = ['filename', 'download_count'];
} 