<?php

namespace App\Console\Commands;

use App\Libraries\Helper;
use App\Libraries\TheTVDB;
use App\Models\Episode;
use App\Models\Season;
use App\Models\Show;
use App\Models\Torrent;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class ProcessTorrents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tv-series:process-torrents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process downloaded episode torrents';

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
        $this->info('Starting torrent processing');
        $transmission = Helper::transmission();
        $torrents = $transmission->all();
        $this->info('Torrents found: ' . count($torrents));
        foreach ($torrents as $torrentFile) {
            $this->info('Processing torrent file ' . $torrentFile->getName());
            if (!$torrentFile->isFinished()) {
                $this->info('Torrent not finished');
                continue;
            }
            $torrent = Torrent::where('info_hash', strtoupper($torrentFile->getHash()))->first();
            if (!$torrent) {
                $this->error('Torrent not found');
                $transmission->remove($torrentFile, true);
                continue;
            }
            $torrent->status = Torrent::STATUS_PROCESSING;
            $torrent->save();

            try {
                $show = Show::where('torrent_title', $torrent->title)->first();
                if (!$show) {
                    $this->info('Show not found, creating');
                    $show = Helper::getShow($torrent->title);
                    if (!$show) {
                        continue;
                    }
                }
                $season = Season::where('show_id', $show->id)->where('season', $torrent->season)->first();
                if (!$season) {
                    Helper::getSeason($show->id, $torrent->season);
                }
                $tvdb = resolve(TheTVDB::class);
                $episodeData = $tvdb->getEpisode($show->tvdb_id, $torrent->season, $torrent->episode);
                if (!$episodeData) {
                    Log::error('Failed to get episode data from TVDB for ' . $show->title . ' Season ' . $torrent->season . ' Episode ' . $torrent->episode);
                    continue;
                }
            } catch (\Exception $e) {
                Log::error('Failed to get episode data for ' . $torrent->title . ' Season ' . $torrent->season . ' Episode ' . $torrent->episode . ': ' . $e->getMessage());
                continue;
            }

            /** @var \Transmission\Model\File[] $files */
            $files = $torrentFile->getFiles();
            $largestFile = $this->getLargestFile($files);
            sleep(1);

            $existingEpisode = Episode::where('show_id', $show->id)->where('season', $torrent->season)->where('episode', $torrent->episode)->first();
            if ($existingEpisode && ($existingEpisode->torrent->is_proper || !$torrent->is_proper)) {
                $transmission->remove($torrentFile, true);
                continue;
            }

            $sourcePath = $torrentFile->getDownloadDir() . DIRECTORY_SEPARATOR . $largestFile->getName();
            $name = Helper::clearName($torrent->title) . ' S' . str_pad($torrent->season, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($torrent->episode, 2, '0', STR_PAD_LEFT) . '.' . 'mp4';
            $name = preg_replace('/[^0-9^a-z^A-Z^_^.]/', '.', $name);
            $name = preg_replace('/\.+/', '.', $name);
            $destinationPath = Helper::clearName($torrent->title) . '/Season ' . $torrent->season;
            $destinationDir = env('MEDIA_PATH') . '/' . $destinationPath;
            $this->info($destinationDir);
            if (!File::isDirectory($destinationDir)) {
                File::makeDirectory($destinationDir, 493, true);
            }
            $args = [
                '-i' => $sourcePath,
                '-c:v' => 'copy',
                '-c:a' => 'aac',
                '-ac' => '2',
                '-strict' => '',
                '-2' => '',
                '-b:a' => '384k',
                '-movflags' => '+faststart',
                '-y' => '',
                $destinationDir . '/TMP-' . $name => ''
            ];
            passthru(escapeshellcmd(env('FFMPEG')) . ' '. Helper::args($args), $err);
            File::move($destinationDir . '/TMP-' . $name, $destinationDir . '/' . $name);
            $transmission->remove($torrentFile, true);
            if ($existingEpisode) {
                $existingEpisode->update([
                    'is_proper' => 1,
                    'torrent_id' => $torrent->id
                ]);
                continue;
            }
            Helper::addTorrentToDatabase($torrent, $show, $episodeData);
        }
        return 0;
    }

    /**
     * @param \Transmission\Model\File[] $files
     * @return \Transmission\Model\File
     */
    private function getLargestFile(array $files)
    {
        /** @var \Transmission\Model\File|null $file */
        $file = null;
        foreach ($files as $f) {
            if (!$file || $f->getSize() > $file->getSize()) {
                $file = $f;
            }
        }
        return $file;
    }
}
