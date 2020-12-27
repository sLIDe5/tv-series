<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Show extends Model
{

    protected $fillable = [
        'tvdb_id',
        'title',
        'torrent_title',
        'description',
        'server',
    ];

    protected $dates = [
        'last_episode',
    ];

    public function bannerImage()
    {
        return asset('storage/header-images/' . $this->id . '.jpg');
    }

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }
}
