<?php

namespace App\Console\Commands;

use App\Models\Torrent;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class GetTorrents extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tv-series:get-torrents';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get episode torrent files from ShowRSS feed';

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
        $rssUrl = env('SHOWRSS_URL');
        if (!$rssUrl) {
            Log::error('No ShowRSS URL');
            return 1;
        }
        $httpClient = new Client();
        try {
            $response = $httpClient->get($rssUrl);
        } catch (GuzzleException $e) {
            Log::error('Failed to retrieve ShowRSS feed: ' . $e->getMessage());
            return 2;
        }
        $body = $response->getBody()->getContents();
        $xml = simplexml_load_string($body);
        $ns = $xml->getNamespaces(true);
        foreach ($xml->channel->item as $item) {
            $tv = $item->children($ns['tv']);
            $episodeData = $this->getSeasonAndEpisode((string)$item->title, (string)$tv->show_name);

            if (!$episodeData || !$episodeData['season'] || !$episodeData['episode']) {
                continue;
            }

            $isProper = false;
            $title = (string)$item->title;
            $title = trim($title);
            $titleParts = preg_split('/\s+/', $title);
            $lastPart = last($titleParts);
            if (in_array(strtoupper($lastPart), ['PROPER', 'REPACK'])) {
                $isProper = true;
            }
            if ((string)$tv->show_name == 'Rush Hour') {
                $tv->show_name = 'Rush Hour (2016)';
            }
            Torrent::firstOrCreate([
                'title' => (string)$tv->show_name,
                'info_hash' => (string)$tv->info_hash,
                'magnet_link' => (string)$item->link,
                'season' => $episodeData['season'],
                'episode' => $episodeData['episode'],
                'guid' => (string)$item->guid,
                'is_proper' => (int)$isProper
            ]);
        }
        return 0;
    }

    private function getSeasonAndEpisode($fullTitle, $showTitle)
    {
        $title = str_replace($showTitle . ' ', '', $fullTitle);
        preg_match_all('/^([0-9]+)x([0-9]+)/', $title, $matches);
        if (!isset($matches[1][0]) || !isset($matches[2][0])) {
            return null;
        }
        return [
            'season' => (int)$matches[1][0],
            'episode' => (int)$matches[2][0]
        ];
    }
}
