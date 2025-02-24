<?php

namespace Eaglewatch\SearchEngines;

use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;

class Darkdump
{
    private Client $client;
    private $defaultConfig = array("api_url" => "https://ahmia.fi/search/");
    private $options = array();
    public function __construct(array $options = [])
    {
        $this->options = array_merge($this->defaultConfig, $options);
        $this->client = new Client([
            'base_uri' => $this->options['api_url'],
            'headers' => [
                'accept' => 'application/json',
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

    public function cleanText(string $htmlContent): string
    {
        $crawler = new Crawler($htmlContent);
        $text = $crawler->text();
        $text = preg_replace('/[\r\n]+/', ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        $text = preg_replace('/[^a-zA-Z0-9\s]/', '', $text);
        return trim($text);
    }

    public function extractKeywords(string $text): array
    {
        $cleanText = $this->cleanText($text);
        $stopWords = array_flip(explode("\n", file_get_contents('stopwords.txt')));
        $wordTokens = array_filter(explode(' ', strtolower($cleanText)), function ($word) use ($stopWords) {
            return ctype_alnum($word) && !isset($stopWords[$word]);
        });
        $freqDist = array_count_values($wordTokens);
        arsort($freqDist);
        return array_slice(array_keys($freqDist), 0, 18);
    }

    public function analyzeText(string $text): array
    {
        $words = explode(' ', $text);
        $stopWords = array_flip(explode("\n", file_get_contents('stopwords.txt')));
        $filteredWords = array_filter($words, function ($word) use ($stopWords) {
            return ctype_alnum($word) && !isset($stopWords[$word]);
        });
        $freqDist = array_count_values($filteredWords);
        arsort($freqDist);
        $topWords = array_slice($freqDist, 0, 10);

        // Basic sentiment analysis
        $positiveWords = array_flip(explode("\n", file_get_contents('positive-words.txt')));
        $negativeWords = array_flip(explode("\n", file_get_contents('negative-words.txt')));

        $positiveCount = 0;
        $negativeCount = 0;

        foreach ($filteredWords as $word) {
            if (isset($positiveWords[$word])) {
                $positiveCount++;
            } elseif (isset($negativeWords[$word])) {
                $negativeCount++;
            }
        }

        $totalWords = count($filteredWords);
        $polarity = ($positiveCount - $negativeCount) / $totalWords;
        $subjectivity = ($positiveCount + $negativeCount) / $totalWords;

        return [
            'top_words' => $topWords,
            'sentiment' => [
                'polarity' => $polarity,
                'subjectivity' => $subjectivity,
            ],
        ];
    }

    public function sanitizeFilename(string $url): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '', $url);
    }

    public function generateHtml(array $imageUrls, string $baseUrl): string
    {
        $filename = $this->sanitizeFilename($baseUrl) . '.html';
        $filepath = 'dd_scrape_image_dump/' . $filename;
        if (!file_exists('dd_scrape_image_dump')) {
            mkdir('dd_scrape_image_dump', 0777, true);
        }
        $htmlContent = '<html><head><title>Image Gallery</title></head><body>';
        foreach ($imageUrls as $url) {
            $htmlContent .= '<img src="' . $url . '" alt="Image" style="padding: 10px; height: 200px;"><br>';
        }
        $htmlContent .= '</body></html>';
        file_put_contents($filepath, $htmlContent);
        return $filepath;
    }

    public function extractLinks(Crawler $crawler): array
    {
        return $crawler->filter('a')->each(function (Crawler $node) {
            return $node->attr('href');
        });
    }

    public function extractMetadata(Crawler $crawler): array
    {
        $metaData = [];
        $crawler->filter('meta')->each(function (Crawler $node) use (&$metaData) {
            $metaName = $node->attr('name') ?: $node->attr('property');
            if ($metaName) {
                $metaData[$metaName] = $node->attr('content');
            }
        });
        return $metaData;
    }

    public function extractEmails(Crawler $crawler): array
    {
        $text = $crawler->text();
        preg_match_all('/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', $text, $matches);
        return $matches[0];
    }

    public function extractDocumentLinks(Crawler $crawler): array
    {
        $docTypes = [
            '.pdf',
            '.doc',
            '.docx',
            '.xlsx',
            '.xls',
            '.ppt',
            '.pptx',
            '.txt',
            '.csv',
            '.rtf',
            '.odt',
            '.ods',
            '.odp',
            '.epub',
            '.mobi',
            '.log',
            '.msg',
            '.wpd',
            '.wps',
            '.tex',
            '.vsd',
            '.xml',
            '.json',
            '.xps',
            '.md',
            '.code',
            '.mp3',
            '.wav',
            '.mp4',
            '.avi',
            '.mov',
            '.flv',
            '.wma',
            '.aac',
            '.dll',
            '.exe',
            '.zip',
            '.tar',
            '.gz',
            '.rar',
            '.7z',
            '.bz2',
            '.vmdk',
            '.iso',
            '.bin',
            '.img',
            '.dmg'
        ];
        return $crawler->filter('a')->each(function (Crawler $node) use ($docTypes) {
            $href = $node->attr('href');
            foreach ($docTypes as $docType) {
                if (str_ends_with($href, $docType)) {
                    return $href;
                }
            }
            return null;
        });
    }

    public function crawl(string $query, int $amount, bool $useProxy = false, bool $scrapeSites = false, bool $scrapeImages = false, bool $returnData = false)
    {
        $headers = ['User-Agent' => 'Mozilla/5.0'];
        $proxyConfig = $useProxy ? ['proxy' => 'socks5h://localhost:9050'] : [];

        try {
            $url = $this->options['api_url'] . "?q={$query}";
            $response = $this->client->request('GET', $url, [
                'headers' => $headers,
                'proxy' => $proxyConfig,
            ]);
            $crawler = new Crawler($response->getBody()->getContents());
            $results = $crawler->filter('#ahmiaResultsPage .result');
        } catch (\Exception $e) {
            echo "Error in fetching Ahmia.fi: " . $e->getMessage();
            return;
        }

        $seenUrls = [];
        $data = [];

        foreach ($results as $idx => $result) {
            if ($idx >= $amount) break;
            $resultCrawler = new Crawler($result);
            $siteUrl = $resultCrawler->filter('cite')->text();
            if (!str_starts_with($siteUrl, 'http')) {
                $siteUrl = 'http://' . $siteUrl;
            }
            if (in_array($siteUrl, $seenUrls)) {
                continue;
            }
            $seenUrls[] = $siteUrl;

            $title = $resultCrawler->filter('a')->text() ?: "No title available";
            $description = $resultCrawler->filter('p')->text() ?: "No description available";

            if ($scrapeSites) {
                try {
                    $siteResponse = $this->client->request('GET', $siteUrl, [
                        'headers' => $headers,
                        'proxy' => $proxyConfig,
                    ]);
                    $siteCrawler = new Crawler($siteResponse->getBody()->getContents());

                    $textAnalysis = $this->analyzeText($siteCrawler->text());
                    $metadata = $this->extractMetadata($siteCrawler);
                    $links = $this->extractLinks($siteCrawler);
                    $emails = $this->extractEmails($siteCrawler);
                    $documents = $this->extractDocumentLinks($siteCrawler);

                    if ($scrapeImages) {
                        $images = $siteCrawler->filter('img')->each(function (Crawler $node) {
                            return $node->attr('src');
                        });
                        $imageUrls = array_map(function ($url) use ($siteUrl) {
                            return str_starts_with($url, 'http') ? $url : $siteUrl . $url;
                        }, $images);
                        $htmlPath = $this->generateHtml($imageUrls, $siteUrl);
                        $imagesStr = "Images Gallery: " . realpath($htmlPath) . "\n";
                    }

                    $siteData = [
                        'title' => $title,
                        'description' => $description,
                        'site_url' => $siteUrl,
                        'keywords' => $this->extractKeywords($siteCrawler->text()),
                        'sentiment' => $textAnalysis['sentiment'],
                        'metadata' => $metadata,
                        'links' => $links,
                        'emails' => $emails,
                        'documents' => $documents,
                        'images' => $scrapeImages ? $imageUrls : [],
                    ];

                    if ($returnData) {
                        $data[] = $siteData;
                    } else {
                        echo str_repeat('-', 50) . "\n";
                        echo ($idx + 1) . ".\n --- [+] Website: " . $title . "\n";
                        echo "| Information: " . $description . "\n";
                        echo "| Onion Link: " . $siteUrl . "\n";
                        echo "| Keywords: " . implode(', ', $siteData['keywords']) . "\n";
                        echo "\t- Sentiment: Polarity = " . $siteData['sentiment']['polarity'] . ", Subjectivity = " . $siteData['sentiment']['subjectivity'] . "\n";
                        echo "| Metadata: " . json_encode($siteData['metadata']) . "\n";
                        echo "| Links Found: " . count($siteData['links']) . "\n";
                        echo "| Emails Found: " . (empty($siteData['emails']) ? 'No emails found.' : implode(', ', $siteData['emails'])) . "\n";
                        echo "| Documents Found: " . (empty($siteData['documents']) ? 'No document links found.' : implode(', ', $siteData['documents'])) . "\n";

                        if ($scrapeImages) {
                            echo $imagesStr;
                        }
                    }
                } catch (\Exception $e) {
                    echo "Dead onion, skipping...: " . $siteUrl . "\n";
                }
            } else {
                if ($returnData) {
                    $data[] = [
                        'title' => $title,
                        'description' => $description,
                        'site_url' => $siteUrl,
                    ];
                } else {
                    echo ($idx + 1) . ". --- [+] Website: " . $title . "\n";
                    echo "\t Information: " . $description . "\n";
                    echo "| Onion Link: " . $siteUrl . "\n";
                }
            }
        }

        if ($returnData) {
            return $data;
        }
    }
}
