<?php

namespace App\Helpers;

use App\Exceptions\ConfigException;
use App\Exceptions\InvalidArgumentException;
use Generator;
use Nette;
use GuzzleHttp;
use Nette\Utils\Arrays;

/**
 * Helper for accessing REST API of the SIS.
 */
class SisHelper
{
    use Nette\SmartObject;

    /** @var string URL prefix for SIS API */
    private string $apiBase;

    /** @var string ID of the faculty */
    private string $faculty;

    /** @var string secret token */
    private string $secret;

    private GuzzleHttp\Client $client;

    /** @var bool whether to verify SSL certificate */
    private bool $verify;

    /**
     * @param string $apiBase
     * @param string $faculty
     * @param string $secret
     * @param GuzzleHttp\HandlerStack|null $handler An optional HTTP handler (mainly for unit testing purposes)
     */
    public function __construct(array $config, GuzzleHttp\HandlerStack $handler = null)
    {
        //$apiBase, $faculty, $secret,
        $this->apiBase = Arrays::get($config, "apiBase", "");
        if (!$this->apiBase) {
            throw new ConfigException("SIS apiBase URL is missing.");
        }

        $this->faculty = Arrays::get($config, "faculty", "");
        if (!$this->faculty) {
            throw new ConfigException("SIS faculty identifier is missing.");
        }

        $this->secret = Arrays::get($config, "secret", "");
        if (!$this->secret) {
            throw new ConfigException("SIS secret token is missing.");
        }

        $this->verify = (bool)Arrays::get($config, "verifySSL", true);

        if (!str_ends_with($this->apiBase, '/')) {
            $this->apiBase .= '/';
        }

        $options = [
            'base_uri' => $this->apiBase . 'rozvrhng/rest.php'
        ];

        if ($handler !== null) {
            $options['handler'] = $handler;
        }

        $this->client = new GuzzleHttp\Client($options);
    }

    /**
     * Helper function that assembles request options.
     * @param array $query parameters to be encoded in URL
     * @param string|array|null $body (array is encoded as JSON)
     * @param array $headers initial HTTP headers
     * @return array options for GuzzleHttp request
     */
    private function prepareOptions(array $query = [], array $headers = []): array
    {
        $options = [
            GuzzleHttp\RequestOptions::ALLOW_REDIRECTS => true,
            GuzzleHttp\RequestOptions::HEADERS => $headers,
            GuzzleHttp\RequestOptions::VERIFY => $this->verify,
        ];

        if ($query) {
            $options[GuzzleHttp\RequestOptions::QUERY] = $query;
        }
        return $options;
    }

    /**
     * @param string $sisUserId
     * @param int|null $year
     * @param int $term
     * @return SisCourseRecord[]|Generator
     * @throws InvalidArgumentException
     */
    public function getCourses(string $sisUserId, ?int $year = null, int $term = 1)
    {
        $salt = join(',', [time(), $this->faculty, $sisUserId]);
        $hash = hash('sha256', "$salt,$this->secret");

        $params = [
            'endpoint' => 'muj_rozvrh',
            'ukco' => $sisUserId,
            'auth_token' => "$salt\$$hash",
            'fak' => $this->faculty,
            'extras' => ['annotations']
        ];

        if ($year !== null) {
            $params['semesters'] = [sprintf("%s-%s", $year, $term)];
        }

        try {
            $response = $this->client->get('', $this->prepareOptions($params));
        } catch (GuzzleHttp\Exception\ClientException $e) {
            throw new InvalidArgumentException("Invalid year or semester number");
        }

        $data = json_decode($response->getBody()->getContents(), true);

        foreach ($data["events"] as $course) {
            yield SisCourseRecord::fromArray($sisUserId, $course);
        }
    }
}
