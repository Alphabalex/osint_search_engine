<?php

namespace Eaglewatch\SearchEngines;

use Exception;
use PiplApi_SearchRequestConfiguration;
use PiplApi_SearchAPIRequest;

class Pipl
{
    private $config;
    public function __construct()
    {
        $configuration = new PiplApi_SearchRequestConfiguration();
        $configuration->api_key = config('pipl.api_key');
        $configuration->minimum_probability = 0.9;
        $configuration->minimum_match = 0.8;
        $configuration->hide_sponsored = true;
        $configuration->live_feeds = false;
        $configuration->show_sources = 'all';
        $this->config = $configuration;
    }

    public function search(array $search)
    {
        try {
            $request = new PiplApi_SearchAPIRequest($search, $this->config);
            $response = $request->send();
            return $response;
        } catch (Exception $e) {
            return $e->getMessage();
        }
    }
}
