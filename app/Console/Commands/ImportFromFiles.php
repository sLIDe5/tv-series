<?php

namespace App\Console\Commands;

use App\Libraries\Helper;
use App\Libraries\TheTVDB;
use App\Models\Episode;
use App\Models\Show;
use App\Models\Torrent;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ImportFromFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tv-series:from-files {path} {name} {--format=mp4} {--from-season=1} {--to-season=1} {--encode=0}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import TV Shows from files';

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
        $path = $this->argument('path');
        $showName = $this->argument('name');
        $format = $this->option('format');
        $fromSeason = (int)$this->option('from-season');
        $toSeason = (int)$this->option('to-season');
        $encode = (bool)$this->option('encode');

        $show = Show::where('title', $showName)->first();
        if (!$show) {
            $this->info('Show not found, trying to get data');
            $show = Helper::getShow($showName);
        }
        if (!$show) {
            $this->error('Failed to get show data');
            return 1;
        }

        for ($s = $fromSeason; $s <= $toSeason; $s++) {
            $fullPath = str_replace('%SEASON%', $s, $path);
            if (!File::exists($fullPath)) {
                $this->error('Folder for season' . $s . ' not found!');
                continue;
            }
            $fullPath .= DIRECTORY_SEPARATOR . '*.' . $format;
            $files = File::glob($fullPath);
            $this->info('Found ' . count($files) . ' files');
            foreach ($files as $file) {
                $baseName = basename($file);
                $this->info($baseName);
                if (preg_match_all('/S([0-9]{2,})\s?E([0-9]{2,})/i', $baseName, $matches)) {
                    $season = intval($matches[1][0]);
                    $episode = intval($matches[2][0]);
                } elseif(preg_match_all('/([0-9]+)x([0-9]+)/i', $baseName, $matches)) {
                    $season = intval($matches[1][0]);
                    $episode = intval($matches[2][0]);
                } elseif(preg_match_all('/([0-9])([0-9]{2})\s\-\s/i', $baseName, $matches)) {
                    $season = intval($matches[1][0]);
                    $episode = intval($matches[2][0]);
                } elseif(preg_match_all('/^Episode\s([0-9]{1,2})\s/i', $baseName, $matches)) {
                    $season = $s;
                    $episode = intval($matches[1][0]);
                } else {
                    $this->error('Failed to get season and episode numbers');
                    continue;
                }
                $this->info('S' . str_pad($season, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($episode, 2, '0', STR_PAD_LEFT));

                $existingEpisode = Episode::where('show_id', $show->id)->where('season', $season)->where('episode', $episode)->first();
                if ($existingEpisode) {
                    $this->info('Episode already exists in site');
                    continue;
                }

                $tvdb = resolve(TheTVDB::class);
                $episodeData = $tvdb->getEpisode($show->tvdb_id, $season, $episode);
                if (!$episodeData) {
                    $this->error('Failed to get episode data');
                    continue;
                }

                /** @var Torrent $torrent */
                $torrent = Torrent::firstOrCreate([
                    'title' => $showName,
                    'info_hash' => null,
                    'magnet_link' => null,
                    'season' => $season,
                    'episode' => $episode,
                    'guid' => null,
                    'is_proper' => 1,
                    'created_at' => new Carbon,
                    'updated_at' => new Carbon,
                    'status' => Torrent::STATUS_PROCESSING
                ]);

                $sourcePath = $file;
                $name = Helper::clearName($torrent->title) . ' S' . str_pad($torrent->season, 2, '0', STR_PAD_LEFT) . 'E' . str_pad($torrent->episode, 2, '0', STR_PAD_LEFT) . '.' . 'mp4';
                $name = preg_replace('/[^0-9^a-z^A-Z^_^.]/', '.', $name);
                $name = preg_replace('/\.+/', '.', $name);
                $destinationPath = Helper::clearName($torrent->title) . '/Season ' . $torrent->season;
                $destinationDir = env('MEDIA_PATH') . '/' . $destinationPath;
                $this->info($destinationDir);
                if (!File::isDirectory($destinationDir)) {
                    File::makeDirectory($destinationDir, 493, true);
                }

                if ($encode) {
                    $args = [
                        '-i' => $sourcePath,
                        '-sn' => '',
                        '-c:v' => 'libx264',
                        '-c:a' => 'aac',
                        '-ac' => '2',
                        '-strict' => '',
                        '-2' => '',
                        '-b:a' => '384k',
                        '-vf' => 'scale=-1:720',
                        '-movflags' => '+faststart',
                        '-threads' => 6,
                        '-y' => '',
                        $destinationDir . '/TMP-' . $name => ''
                    ];
                } else {
                    $args = [
                        '-i' => $sourcePath,
                        '-c:v' => 'copy',
                        '-c:a' => 'copy',
                        '-strict' => '',
                        '-2' => '',
                        '-movflags' => '+faststart',
                        '-threads' => 6,
                        '-y' => '',
                        $destinationDir . '/TMP-' . $name => ''
                    ];
                }
                passthru(escapeshellcmd(env('FFMPEG')) . ' '. Helper::args($args), $err);
                File::move($destinationDir . '/TMP-' . $name, $destinationDir . '/' . $name);
                Helper::addTorrentToDatabase($torrent, $show, $episodeData);
                $torrent->status = Torrent::STATUS_PROCESSED;
                $torrent->save();
            }
        }
        return 0;
    }
}
