<?php

namespace App\Http\Controllers;

use App\Models\Episode;

class WatchController extends Controller
{
    public function time(Episode $episode)
    {
        $time = request('timeWatched', null);
        if (is_null($time)) {
            abort(400);
        }
        $episode->watched()->updateOrCreate([
            'user_id' => auth()->user()->id
        ], [
            'time' => $time
        ]);
    }
    public function ended(Episode $episode)
    {
        $episode->watched()->updateOrCreate([
            'user_id' => auth()->user()->id
        ], [
            'finished' => true
        ]);
    }
}
