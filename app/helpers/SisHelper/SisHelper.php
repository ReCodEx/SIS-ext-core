<?php

namespace App\Helpers;

use App\Exceptions\ConfigException;
use App\Exceptions\InvalidArgumentException;
use App\Exceptions\SisException;
use Generator;
use Nette;
use GuzzleHttp;
use Nette\Utils\Arrays;
use Tracy\Debugger;

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

    /** @var string secret token for module 'rozvrhng' */
    private string $secretRozvrhng;

    /** @var string secret token for module 'kdojekdo' */
    private string $secretKdojekdo;

    private GuzzleHttp\Client $client;

    /** @var bool whether to verify SSL certificate */
    private bool $verify;

    /**
     * @param array $config
     * @param GuzzleHttp\HandlerStack|null $handler An optional HTTP handler (mainly for unit testing purposes)
     */
    public function __construct(array $config, ?GuzzleHttp\HandlerStack $handler = null)
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

        $this->secretRozvrhng = Arrays::get($config, "secretRozvrhng", "");
        if (!$this->secretRozvrhng) {
            throw new ConfigException("SIS secret token is missing.");
        }

        $this->secretKdojekdo = Arrays::get($config, "secretKdojekdo", "");
        if (!$this->secretKdojekdo) {
            throw new ConfigException("SIS secret token is missing.");
        }

        $this->verify = (bool)Arrays::get($config, "verifySSL", true);

        if (!str_ends_with($this->apiBase, '/')) {
            $this->apiBase .= '/';
        }

        $options = ['base_uri' => $this->apiBase];
        if ($handler !== null) {
            $options['handler'] = $handler;
        }
        $this->client = new GuzzleHttp\Client($options);
    }

    /**
     * Helper function that assembles request options.
     * @param array $query parameters to be encoded in URL
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
     * @param string $sisUserId UKCO
     * @return SisUserRecord
     * @throws SisException
     */
    public function getUserRecord(string $sisUserId): SisUserRecord
    {
        $salt = time();
        $params = [
            'oidos' => [$sisUserId],
            'response_fmt' => 'json',
            'do' => 'osoba',
            'token' => $salt . '$' . hash('sha256', "$salt,$this->secretKdojekdo"),
        ];

        Debugger::log("SIS::getUserRecord($sisUserId)", Debugger::DEBUG);
        try {
            $response = $this->client->get('kdojekdo/rest.php', $this->prepareOptions($params));
        } catch (GuzzleHttp\Exception\ClientException $e) {
            throw new SisException("Kdojekdo module API call failed.", $e);
        }

        $body = $response->getBody()->getContents();
        Debugger::log($body, Debugger::DEBUG);
        $data = json_decode($body, true);

        if (
            !$data || !is_array($data) || ($data['status'] ?? '') !== 'OK' || !is_array($data['data'])
            || count($data['data']) !== 1
        ) {
            if (!empty($data['errors'])) {
                throw new SisException("Kdojekdo module API call returned error: " . json_encode($data['errors']));
            }
            throw new SisException("Kdojekdo module API call returned malformed answer.");
        }

        $record = current($data['data']);
        return SisUserRecord::fromArray($sisUserId, $record);
    }

    /**
     * @param string $sisUserId UKCO
     * @param string|array|null $terms a term or a list of terms in the format 'year-term';
     *                                 null = return all available terms
     * @return SisCourseRecord[]|Generator
     * @throws InvalidArgumentException
     */
    public function getCourses(string $sisUserId, mixed $terms = null)
    {
        $salt = join(',', [time(), $this->faculty, $sisUserId]);
        $hash = hash('sha256', "$salt,$this->secretRozvrhng");

        $params = [
            'endpoint' => 'muj_rozvrh',
            'ukco' => $sisUserId,
            'auth_token' => "$salt\$$hash",
            'fak' => $this->faculty,
            'extras' => ['annotations']
        ];

        if ($terms !== null) {
            if (!is_array($terms)) {
                $terms = [$terms];
            }
            $params['semesters'] = $terms;
            $termsStr = join(', ', $terms);
        } else {
            $termsStr = 'null';
        }

        Debugger::log("SIS::getCourses($sisUserId, $termsStr)", Debugger::DEBUG);
        try {
            $response = $this->client->get('rozvrhng/rest.php', $this->prepareOptions($params));
        } catch (GuzzleHttp\Exception\ClientException $e) {
            throw new InvalidArgumentException("Invalid year or semester number");
        }

        $body = $response->getBody()->getContents();
        Debugger::log($body, Debugger::DEBUG);
        $data = json_decode($body, true);

        foreach ($data["events"] as $course) {
            yield SisCourseRecord::fromArray($sisUserId, $course);
        }
    }
}
