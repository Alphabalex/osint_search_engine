<?php

namespace Eaglewatch\SearchEngines;

use GuzzleHttp\Client;

class OpenCorporate
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('opencorporate.api_url'),
            'headers' => [
                'accept' => 'application/json',
                'api_token' => config('opencorporate.api_key')
            ],
        ]);
    }

    public function searchCompanies(array $queryParams)
    {
        $url = "companies/search";
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
        $url = "companies/{$jurisdictionCode}/{$companyNumber}";

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
        $url = "officers/{$officerId}";

        try {
            $response = $this->client->request('GET', $url, []);
            $data = json_decode($response->getBody()->getContents(), true);
            return $data;
        } catch (\Exception $e) {
            return ['error' => "Error in fetching officer: " . $e->getMessage()];
        }
    }
}
