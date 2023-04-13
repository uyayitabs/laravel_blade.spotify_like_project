<?php
/**
 * Created by Mark Lawrence Tabamo.
 * Date: 2023-01-05
 * Time: 22:00
 */
namespace App\Http\Controllers\Frontend;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use View;

class ArtistSignupController
{
    public function index(Request $request)
    {
        if (auth()->check()) {
            if (!auth()->user()->artist_id) {
                return redirect()->route('frontend.homepage');
            }
        }

        return View::make('signup.artist.index');
    }
}