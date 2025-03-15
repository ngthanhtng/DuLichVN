<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $primaryKey = 'promotionId';

    protected $fillable = [
        'description',
        'discount',
        'startDate',
        'endDate',
        'quantity',
    ];
}