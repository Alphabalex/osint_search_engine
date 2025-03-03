<?php

namespace Eaglewatch\SearchEngines;

use GuzzleHttp\Client;

class OpenCorporate
{
    private Client $client;
    private $defaultConfig = array("api_url" => "https://api.opencorporates.com/v0.4/");
    private $options = array();
    private $api_key;

    public function __construct(string $api_key, array $options = [])
    {
        $this->options = array_merge($this->defaultConfig, $options);
        $this->api_key = $api_key;
        $this->client = new Client([
            'base_uri' => $this->options['api_url'],
            'headers' => [
                'accept' => 'application/json',
            ],
        ]);
    }

    public function searchCompanies(array $queryParams)
    {
        $url = "companies/search";
        $queryParams = array_merge($queryParams, ['api_token' => $this->api_key]);
        $query = http_build_query($queryParams);

        try {
            $response = $this->client->request('GET', $url . '?' . $query, []);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data;
        } catch (\Exception $e) {
            return ['error' => "Error in fetching companies: " . $e->getMessage()];
        }
    }

    public function searchOfficers(array $queryParams)
    {
        $url = "officers/search";
        $queryParams = array_merge($queryParams, ['api_token' => $this->api_key]);
        $query = http_build_query($queryParams);

        try {
            $response = $this->client->request('GET', $url . '?' . $query, []);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data;
        } catch (\Exception $e) {
            return ['error' => "Error in fetching officers: " . $e->getMessage()];
        }
    }

    public function fetchCompany(string $jurisdictionCode, string $companyNumber)
    {
        $url = "companies/{$jurisdictionCode}/{$companyNumber}?api_token={$this->api_key}";

        try {
            $response = $this->client->request('GET', $url, []);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data;
        } catch (\Exception $e) {
            return ['error' => "Error in fetching company: " . $e->getMessage()];
        }
    }

    public function fetchOfficer(string $officerId)
    {
        $url = "officers/{$officerId}?api_token={$this->api_key}";

        try {
            $response = $this->client->request('GET', $url, []);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data;
        } catch (\Exception $e) {
            return ['error' => "Error in fetching officer: " . $e->getMessage()];
        }
    }
}
