<?php

namespace Eaglewatch\SearchEngines;

use Exception;

class Bing
{
    private $api_key, $api_url;
    private $defaultConfig = array("api_url" => "https://api.bing.microsoft.com/v7.0/");
    private $options = array();
    public function __construct(string $api_key, array $options = [])
    {
        $this->options = array_merge($this->defaultConfig, $options);
        $this->api_key = $api_key;
        $this->api_url = $this->options['api_url'];
    }

    function search($query)
    {
        if (strlen($this->api_key) !== 32) {
            throw new Exception('Invalid Bing Search API subscription key!');
        }
        $url = $this->api_url . "search";
        $headers = "Ocp-Apim-Subscription-Key: $this->api_key\r\n";
        $options = array('http' => array('header' => $headers, 'method' => 'GET'));
        $context = stream_context_create($options);
        $result = file_get_contents($url . "?q=" . urlencode($query), false, $context);
        $headers = array();
        foreach ($http_response_header as $k => $v) {
            $h = explode(":", $v, 2);
            if (isset($h[1]))
                if (preg_match("/^BingAPIs-/", $h[0]) || preg_match("/^X-MSEdge-/", $h[0]))
                    $headers[trim($h[0])] = trim($h[1]);
        }
        //return array($headers, $result);
        return json_decode($result, true);
    }
}
