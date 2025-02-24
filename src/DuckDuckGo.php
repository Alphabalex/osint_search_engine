<?php

namespace Eaglewatch\SearchEngines;

use GuzzleHttp\Client;

class DuckDuckGo
{
    private Client $client;
    private $defaultConfig = array("api_url" => "https://duckduckgo8.p.rapidapi.com/");
    private $options = array();
    public function __construct(string $api_key, array $options = [])
    {
        $this->options = array_merge($this->defaultConfig, $options);
        $this->client = new Client([
            'base_uri' => $this->options['api_url'],
            'headers' => [
                'accept' => 'application/json',
                'x-rapidapi-key' => $api_key
            ],
        ]);
    }

    public function search(string $param): array
    {
        $searchParam = urlencode($param);
        $url = "?q={$searchParam}";
        $response = $this->client->request('GET', $url);
        return json_decode($response->getBody()->getContents(), true);
    }
}
