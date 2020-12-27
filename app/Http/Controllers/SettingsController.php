<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingsRequest;
use App\Models\Show;

class SettingsController extends Controller
{
    public function index()
    {
        $shows = Show::orderBy('title', 'asc')->get();
        $userShows = auth()->user()->shows()->pluck('shows.id');
        return view('settings')->with([
            'shows' => $shows,
            'userShows' => $userShows->toArray()
        ]);
    }
    public function save(SettingsRequest $request)
    {
        $data = $request->validated();
        $shows = $data['shows'] ?? [];
        auth()->user()->shows()->sync($shows);
        return redirect()->route('settings')->with('success', 'Settings saved!');
    }
}
