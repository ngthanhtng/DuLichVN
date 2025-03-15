<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tour;

class TourController extends Controller
{
    public function getTours()
    {
        return response()->json(Tour::all());
    }

    public function getTourByDestination($destination)
    {
        $tours = Tour::where('destination', 'LIKE', "%$destination%")->get();
        return response()->json($tours);
    }
}
