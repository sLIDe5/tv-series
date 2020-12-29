<?php

namespace App\Libraries;

use App\Models\Episode;
use App\Models\Season;
use App\Models\Show;
use App\Models\Torrent;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Transmission\Transmission;

class Helper
{
    public static function clearName(string $name): string
    {
        return str_replace([':'], '', $name);
    }

    public static function generateThumbnail(string $mediaPath, string $thumbnailPath)
    {
        $directory = dirname($thumbnailPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }
        $args = [
            '-ss' => '00:05:00',
            '-i' => $mediaPath,
            '-filter:v' => 'scale=400:-1',
            '-vframes' => 1,
            '-q:v' => 1,
            '-y' => '',
            $thumbnailPath => ''
        ];
        passthru(escapeshellcmd(env('FFMPEG')) . ' ' . self::args($args));
    }

    public static function args($par)
    {
        $re = array();
        foreach ($par as $k => $value) {
            if (!is_array($value)) {
                $value = [$value];
            }
            foreach ($value as $v) {
                $re[] = escapeshellarg(trim($k));
                if ($v !== '') {
                    if (is_numeric($v)) {
                        $re[] = $v;
                    } else {
                        $re[] = escapeshellarg($v);
                    }
                }
            }
        }
        return implode(' ', $re);
    }

    public static function transmission()
    {
        $transmission = new Transmission(env('TRANSMISSION_HOST'), env('TRANSMISSION_PORT', 9091));
        return $transmission;
    }

    public static function getShow($title)
    {
        $tvdb = resolve(TheTVDB::class);
        $data = $tvdb->search($title);
        if (!$data) {
            Log::error('Failed to find show ' . $title);
            return null;
        }
        $showData = $tvdb->getSeries($data['id']);
        if (!$showData) {
            Log::error('Failed to get show data for ' . $title);
            return null;
        }
        $show = Show::create([
            'title' => $showData['seriesName'],
            'torrent_title' => $title,
            'tvdb_id' => $showData['id'],
            'description' => $showData['overview'],
            'server' => 2
        ]);
        self::getShowPoster($show->id, $showData['id']);
        $path = storage_path('app/public/header-images');
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 493, true);
        }
        try {
            $client = new Client();
            $response = $client->get(TheTVDB::IMAGES_URL . $showData['banner']);
            if ($response->getStatusCode() == 200) {
                File::put($path . '/' . $show->id . '.jpg', $response->getBody()->getContents());
            } else {
                Log::error('Failed to get banner for ' . $show->title);
            }
        } catch (GuzzleException $e) {
            Log::error('Failed to get banner for ' . $show->title);
        }
        return $show;
    }

    private static function getShowPoster($showId, $tvdbId)
    {
        $tvdb = resolve(TheTVDB::class);
        $posterFile = $tvdb->getPosterUrl($tvdbId);
        $path = storage_path('app/public/posters');
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 493, true);
        }
        try {
            $client = new Client();
            $response = $client->get(TheTVDB::IMAGES_URL . $posterFile);
            if ($response->getStatusCode() == 200) {
                File::put($path . '/' . $showId . '.jpg', $response->getBody()->getContents());
            } else {
                Log::error('Failed to get poster for ' . $showId);
            }
        } catch (GuzzleException $e) {
            Log::error('Failed to get poster for ' . $showId);
        }
    }

    public static function getSeason($showId, $season)
    {
        $season = Season::create([
            'show_id' => $showId,
            'season' => $season
        ]);
        return $season;
    }

    public static function addTorrentToDatabase(Torrent $torrent, Show $show, $episodeData)
    {
        $episode = Episode::create([
            'tvdb_id' => $episodeData['id'],
            'title' => $episodeData['episodeName'],
            'description' => $episodeData['overview'] ?? '',
            'show_id' => $show->id,
            'season' => $torrent->season,
            'episode' => $torrent->episode,
            'torrent_id' => $torrent->id
        ]);
        $show->last_episode = Carbon::now();
        $show->save();
        $path = dirname($episode->thumbnailPath);
        if (!File::isDirectory($path)) {
            File::makeDirectory($path, 493, true);
        }
        try {
            $client = new Client();
            $response = $client->get(TheTVDB::IMAGES_URL . $episodeData['filename']);
            if ($response->getStatusCode() == 200) {
                File::put($path . '/' . $episode->episode . '.jpg', $response->getBody()->getContents());
            } else {
                self::generateThumbnail($episode->mediaPath, $episode->thumbnailPath);
            }
        } catch (GuzzleException $e) {
            self::generateThumbnail($episode->mediaPath, $episode->thumbnailPath);
        }
        return $episode;
    }
}
