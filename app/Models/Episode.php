<?php

namespace App\Models;

use App\Libraries\Helper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{

    protected $fillable = [
        'tvdb_id',
        'show_id',
        'season',
        'episode',
        'title',
        'description',
        'torrent_id',
    ];

    public function show()
    {
        return $this->belongsTo(Show::class);
    }

    public function torrent()
    {
        return $this->belongsTo(Torrent::class);
    }

    public function watched()
    {
        $relation = $this->hasOne(WatchedEpisode::class);
        if (auth()->user()) {
            $relation = $relation->where('user_id', auth()->user()->id);
        }
        return $relation;
    }

    public function getEpisodeNumberAttribute()
    {
        return 'S' . str_pad($this->season, 2, '0', STR_PAD_LEFT) . 'E' .  str_pad($this->episode, 2, '0', STR_PAD_LEFT);
    }

    public function thumbnailImage()
    {
        return asset('storage/episodes/' . $this->show_id . '/' . $this->season . '/' . $this->episode . '.jpg');
    }

    public function getThumbnailPathAttribute()
    {
        return storage_path('app/public/episodes/' . $this->show_id . '/' . $this->season . '/' . $this->episode . '.jpg');
    }

    public function getMediaNameAttribute()
    {
        $name = Helper::clearName($this->torrent->title) . ' S' . str_pad($this->season, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($this->episode, 2, '0', STR_PAD_LEFT) . '.mp4';
        $name = preg_replace('/[^0-9^a-z^A-Z^_^.]/', '.', $name);
        return preg_replace('/\.+/', '.', $name);
    }

    public function getMediaPathAttribute()
    {
        return env('MEDIA_PATH') . '/' . Helper::clearName($this->torrent->title) . '/' . 'Season ' . $this->season . '/' . $this->mediaName;
    }

    public function url()
    {
        $name = $this->mediaName;
        $path = '/tv-series/' . Helper::clearName($this->torrent->title) . '/' . 'Season ' . $this->season . '/' . $name;
        $expireTime = Carbon::now()->addHours(6)->getTimestamp();
        $hash = md5($expireTime . $path . ' ' . env('SECURE_SALT'), true);
        $hash = base64_encode($hash);
        $hash = str_replace(['+', '/'], ['-', '_'], $hash);
        $hash = str_replace('=', '', $hash);
        return $path . '?md5=' . $hash . '&expires=' . $expireTime;
    }
}
