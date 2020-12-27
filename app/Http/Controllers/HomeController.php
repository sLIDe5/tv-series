<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Show;

class HomeController extends Controller
{
    public function index()
    {
        $episodes = auth()->user()->episodes()->with(['show', 'watched'])->orderBy('episodes.id', 'DESC')->paginate(15);
        if ($episodes->isEmpty()) {
            $episodes = Episode::with(['show', 'watched'])->orderBy('id', 'DESC')->paginate(15);
        }
        return view('home', [
            'shows' => $this->_getLatestShows(),
            'episodes' => $episodes
        ]);
    }

    public function unseen()
    {
        $episodes = auth()->user()->episodes()->whereDoesntHave('watched', function($query){
            $query->where('finished', true);
        })->with(['show', 'watched'])->orderBy('episodes.id', 'DESC')->paginate(15);
        if ($episodes->isEmpty()) {
            $episodes = Episode::doesntHave('watched')->with(['show', 'watched'])->orderBy('id', 'DESC')->paginate(15);
        }
        return view('home', [
            'shows' => $this->_getLatestShows(),
            'episodes' => $episodes
        ]);
    }

    private function _getLatestShows()
    {
        return Show::orderBy('last_episode', 'desc')->take(8)->get();
    }

    public function home()
    {
        if (auth()->user()->is_subscriber) {
            return redirect()->route('home');
        }
        return view('unsubscribed');
    }
}
