<?php

namespace App\Libraries;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;

class TheTVDB
{
    const API_URL = 'https://api.thetvdb.com/';
    const IMAGES_URL = 'http://thetvdb.com/banners/';

    private $apiKey;
    private $authorizationToken;
    private $client;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
        $this->client = new Client([
            'verify' => false,
            'base_uri' => self::API_URL
        ]);
        $this->getToken();
    }

    private function getHeaders()
    {
        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];
        if ($this->authorizationToken) {
            $headers['Authorization'] = 'Bearer ' . $this->authorizationToken;
        }
        return $headers;
    }

    private function get($method, $data)
    {
        try {
            $response = $this->client->get('/' . $method, [
                'query' => $data,
                'headers' => $this->getHeaders()
            ]);
        } catch (GuzzleException $e) {
            Log::error('Failed to get data from TheTVDB: ' . $e->getMessage());
            return null;
        }
        $response = json_decode($response->getBody()->getContents(), true);
        return $response;
    }

    private function post($method, $data)
    {
        try {
            $response = $this->client->post('/' . $method, [
                'body' => json_encode($data, JSON_FORCE_OBJECT),
                'headers' => $this->getHeaders()
            ]);
        } catch (GuzzleException $e) {
            Log::error('Failed to get data from TheTVDB: ' . $e->getMessage());
            return null;
        }
        $response = json_decode($response->getBody()->getContents(), true);
        return $response;
    }

    private function getToken()
    {
        $response = $this->post('login', [
            'apikey' => $this->apiKey
        ]);
        if (!$response) {
            throw new \Exception('Failed to initialize TheTVDB');
        }
        $this->authorizationToken = $response['token'];
    }

    public function search($name)
    {
        $response = $this->get('search/series', [
            'name' => $name
        ]);
        if (!$response) {
            return null;
        }
        $show = null;
        foreach ($response['data'] as $s) {
            if (!$show || $s['seriesName'] == $name) {
                $show = $s;
            }
        }
        return $show;
    }

    public function getSeries($id)
    {
        $response = $this->get('series/' . $id, []);
        if (!$response) {
            return null;
        }
        return $response['data'];
    }

    public function getEpisode($seriesId, $season, $episode)
    {
        $response = $this->get('series/' . $seriesId . '/episodes/query', [
            'airedSeason' => $season,
            'airedEpisode' => $episode
        ]);
        if (!$response) {
            return null;
        }
        $data = reset($response['data']);
        $response = $this->get('episodes/' . $data['id'], []);
        return $response['data'];
    }

    public function getPosterUrl($seriesId)
    {
        $response = $this->get('series/' . $seriesId . '/images/query', [
            'keyType' => 'poster'
        ]);
        if (!$response) {
            return null;
        }
        $poster = null;
        foreach ($response['data'] as $p) {
            if (!$poster || $p['ratingsInfo']['average'] > $poster['ratingsInfo']['average']) {
                $poster = $p;
            }
        }
        return $poster['fileName'];
    }
}
