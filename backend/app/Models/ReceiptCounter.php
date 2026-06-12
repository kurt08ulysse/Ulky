<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReceiptCounter extends Model
{
    protected $fillable = [
        'commune_id',
        'year',
        'last_number',
    ];
}
