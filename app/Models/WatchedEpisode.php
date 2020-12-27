<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WatchedEpisode extends Model
{

    protected $fillable = [
        'user_id',
        'episode_id',
        'time',
        'finished',
    ];

    public function show()
    {
        return $this->belongsTo(Show::class);
    }

    public function watched()
    {
        $relation = $this->hasOne(WatchedEpisode::class);
        if (auth()->user()) {
            $relation = $relation->where('user_id', auth()->user()->id);
        }
        return $relation;
    }
}
