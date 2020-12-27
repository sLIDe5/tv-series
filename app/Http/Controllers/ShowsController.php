<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Show;
use Illuminate\Support\Facades\View;

class ShowsController extends Controller
{
    public function index()
    {
        $shows = Show::orderBy('title', 'asc')->get();
        return view('shows.index', [
            'shows' => $shows
        ]);
    }

    public function view(Show $show)
    {
        $episodes = $show->episodes()->with('watched')->orderBy('season', 'desc')->orderBy('episode', 'desc')->paginate(15);
        View::share($show->title);
        return view('shows.view')->with([
            'show' => $show,
            'episodes' => $episodes
        ]);
    }

    public function watch(Episode $episode)
    {
        View::share('title', $episode->show->title . ' - ' . $episode->episodeNumber . ' (' . $episode->title . ')');
        return view('shows.watch')->with([
            'episode' => $episode
        ]);
    }

    public function episodeWatched(Episode $episode)
    {
        $episode->watched()->updateOrCreate([
            'user_id' => auth()->user()->id
        ], [
            'time' => 0,
            'finished' => true
        ]);
        return redirect()->back();
    }

    public function seasonWatched(Show $show, int $season)
    {
        $episodes = $show->episodes()->where('season', $season)->get();
        foreach( $episodes as $episode ){
            $episode->watched()->updateOrCreate([
                'user_id' => auth()->user()->id
            ], [
                'time' => 0,
                'finished' => true
            ]);
        }
        return redirect()->back();
    }

    public function watched(Show $show)
    {
        $episodes = $show->episodes()->get();
        foreach( $episodes as $episode ){
            $episode->watched()->updateOrCreate([
                'user_id' => auth()->user()->id
            ], [
                'time' => 0,
                'finished' => true
            ]);
        }
        return redirect()->back();
    }
}
