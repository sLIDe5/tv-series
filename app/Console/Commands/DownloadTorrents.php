<?php

namespace App\Console\Commands;

use App\Libraries\Helper;
use App\Models\Torrent;
use Illuminate\Console\Command;

class DownloadTorrents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tv-series:download-torrents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads episode torrent file';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $torrents = Torrent::where('status', Torrent::STATUS_PENDING)->get();
        $transmission = Helper::transmission();
        /** @var Torrent $torrent */
        foreach ($torrents as $torrent) {
            // If torrent is not proper and there is a torrent that is proper and already exists skip downloading
            if (!$torrent->is_proper && Torrent::where('title', $torrent->title)->where('season', $torrent->season)->where('episode', $torrent->episode)->where('is_proper', 1)->count()) {
                $torrent->status = Torrent::STATUS_SKIPPED;
                $torrent->save();
                continue;
            }
            if ($torrent->is_proper) {
                $current = Torrent::where('title', $torrent->title)->where('season', $torrent->season)->where('episode', $torrent->episode)->where('is_proper', 0)->where('status', Torrent::STATUS_DOWNLOADING)->get();
                foreach ($current as $currentTorrent) {
                    $currentTorrentFile = $transmission->get($currentTorrent->torrent_id);
                    if ($currentTorrentFile && !$currentTorrentFile->isFinished()) {
                        $transmission->remove($currentTorrentFile);
                    }
                }
            }
            $torrentFile = $transmission->add($torrent->magnet_link, false, env('DOWNLOAD_PATH'));
            $torrent->torrent_id = $torrentFile->getId();
            $torrent->status = Torrent::STATUS_DOWNLOADING;
            $torrent->save();
        }
        return 0;
    }
}
