<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Validation extends Model
{
    // use HasFactory;
    protected $fillable = [
        'Id',
        'total',
        'util',
        'updated_at'
    ];
}
