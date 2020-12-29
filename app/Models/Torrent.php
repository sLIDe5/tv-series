<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Torrent extends Model
{

    const STATUS_PENDING = 0;
    const STATUS_DOWNLOADING = 1;
    const STATUS_PROCESSED = 2;
    const STATUS_SKIPPED = 3;
    const STATUS_PROCESSING = 4;

    protected $fillable = [
        'title',
        'info_hash',
        'season',
        'episode',
        'guid',
        'magnet_link',
        'is_proper',
        'status',
    ];

    public function getShow()
    {
        return Show::where('torrent_title', $this->title)->first();
    }
}
