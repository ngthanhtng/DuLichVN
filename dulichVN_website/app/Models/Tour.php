<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tour extends Model
{
    use HasFactory;

    protected $table = 'tbl_tours';
    protected $primaryKey = 'tourId';
    public $timestamps = false;

    protected $fillable = [
        'title', 'description', 'quantity', 'priceAdult', 'priceChild',
        'time', 'destination', 'availability', 'startDate', 'endDate', 'reviews', 'domain'
    ];
}
