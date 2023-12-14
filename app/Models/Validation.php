<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Validation extends Model
{
    // use HasFactory;
    protected $fillable = [
        'Id',
        // 'total',
        'monto',
        'threshold',
        'currency',
        'util',
        'updated_at',
        'created_at'
    ];
}
