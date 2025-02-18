<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ImportHistory extends Model
{
    use SoftDeletes;

    protected $table = 'import_history';

    protected $fillable = [
        'filename',
        'records_count',
        'import_date',
        'status',
        'errors'
    ];

    protected $casts = [
        'import_date' => 'datetime',
        'errors' => 'array'
    ];
}
