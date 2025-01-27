<?php

namespace App\Helpers;

use App\Exceptions\ConfigException;
use App\Exceptions\RecodexApiException;
use Nette;
use GuzzleHttp;
use Nette\Utils\Arrays;

/**
 * Wrapper for ReCodEx API calls.
 */
class RecodexApiHelper
{
    use Nette\SmartObject;

    /** @var string Base of the API URL */
    private string $apiBase;

    /** @var string ID of this extension in the ReCodEx config */
    private string $extensionId;

    /** @var bool */
    private bool $verify = true;

    /** @var string Key used to identify UKCO in ReCodEx user external logins */
    private string $sisIdKey;

    /** @var string Key used to identify SIS/LDAP login in ReCodEx user external logins */
    private string $sisLoginKey;

    /** @var string|null Authentication token that is added to headers */
    private ?string $authToken = null;

    /** @var GuzzleHttp\Client */
    private $client;

    /**
     * @param array $config
     * @param GuzzleHttp\HandlerStack|null $handler An optional HTTP handler (mainly for unit testing purposes)
     */
    public function __construct(array $config, GuzzleHttp\HandlerStack $handler = null)
    {
        $this->extensionId = Arrays::get($config, "extensionId", "");
        if (!$this->extensionId) {
            throw new ConfigException("ReCodEx extension identifier is missing.");
        }

        $this->apiBase = Arrays::get($config, "apiBase", "");
        if (!$this->apiBase) {
            throw new ConfigException("ReCodEx API base url is not set.");
        }
        if (!str_ends_with($this->apiBase, '/')) {
            $this->apiBase .= '/';
        }
        $this->apiBase .= 'v1/';

        $this->verify = (bool)Arrays::get($config, "verifySSL", true);
        $this->sisIdKey = Arrays::get($config, "sisIdKey", "cas-uk");
        $this->sisLoginKey = Arrays::get($config, "sisLoginKey", "ldap-uk");

        $options = ['base_uri' => $this->apiBase];
        if ($handler !== null) {
            $options['handler'] = $handler;
        }
        $this->client = new GuzzleHttp\Client($options);
    }

    public function getSisIdKey(): string
    {
        return $this->sisIdKey;
    }

    public function getSisLoginKey(): string
    {
        return $this->sisLoginKey;
    }

    /**
     * Set authentication token whih is send in headers of each request.
     * @param string|null $token
     */
    public function setAuthToken(?string $token): void
    {
        $this->authToken = $token;
    }

    /**
     * Helper function that assembles request options.
     * @param array $query parameters to be encoded in URL
     * @param string|array|null $body (array is encoded as JSON)
     * @param array $headers initial HTTP headers
     * @return array options for GuzzleHttp request
     */
    private function prepareOptions(array $query = [], $body = null, array $headers = []): array
    {
        $options = [
            GuzzleHttp\RequestOptions::ALLOW_REDIRECTS => true,
            GuzzleHttp\RequestOptions::HEADERS => $headers,
            GuzzleHttp\RequestOptions::VERIFY => $this->verify,
        ];

        if ($this->authToken) {
            $options[GuzzleHttp\RequestOptions::HEADERS]['Authorization'] = 'Bearer ' . $this->authToken;
        }
        if ($query) {
            $options[GuzzleHttp\RequestOptions::QUERY] = $query;
        }
        if ($body) {
            if (is_array($body)) {
                $body = json_encode($body);
            }
            $options[GuzzleHttp\RequestOptions::BODY] = $body;
        }
        return $options;
    }

    /**
     * Decode and verify JSON body.
     * @return array|string|int|bool|null decoded JSON response
     * @throws RecodexApiException
     */
    private function processJsonBody($response)
    {
        $code = $response->getStatusCode();
        if ($code !== 200) {
            throw new RecodexApiException("HTTP request failed (response $code).");
        }

        $type = $response->getHeaderLine("Content-Type") ?? '';
        if (!str_starts_with($type, 'application/json')) {
            throw new RecodexApiException("JSON body was expected but '$type' returned instead.");
        }

        $body = json_decode($response->getBody()->getContents(), true);
        if (($body['success'] ?? false) !== true) {
            $code = $body['code'];
            throw new RecodexApiException($body['error']['message'] ?? "API responded with error code $code.");
        }

        return $body['payload'] ?? null;
    }

    /**
     * Perform a GET request and return decoded JSON response.
     * @param string $url suffix for the base URL
     * @param array $params to be encoded in URL query
     * @param array $headers initial HTTP headers
     * @return array|string|int|bool|null decoded JSON response
     */
    private function get(string $url, array $params = [], array $headers = [])
    {
        $response = $this->client->get($url, $this->prepareOptions($params, null, $headers));
        return $this->processJsonBody($response);
    }

    /**
     * Perform a POST request and return decoded JSON response.
     * @param string $url suffix for the base URL
     * @param array $params to be encoded in URL query
     * @param string|array|null $body (array is encoded as JSON)
     * @param array $headers initial HTTP headers
     * @return array|string|int|bool|null decoded JSON response
     */
    private function post(string $url, array $params = [], $body = null, array $headers = [])
    {
        $response = $this->client->post($url, $this->prepareOptions($params, $body, $headers));
        return $this->processJsonBody($response);
    }

    /**
     * Parse temporary JWT, perform basic sanity check and return instance ID.
     * @param string $token JWT
     * @return string instance ID
     */
    public function getTempTokenInstance(string $token): string
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3 || !$parts[1]) {
            throw new RecodexApiException("Invalid temporary JWT given.");
        }

        $payload = json_decode(base64_decode($parts[1]), true);
        if (!$payload || !is_array($payload) || empty($payload['instance'])) {
            throw new RecodexApiException("Invalid temporary JWT given.");
        }

        if (($payload['extension'] ?? '') !== $this->extensionId) {
            throw new RecodexApiException("Temporary JWT is designated for a different extension.");
        }

        return $payload['instance'];
    }

    /**
     * Complete the authentication process. Use tmp token to fetch full-token and user info.
     * The tmp token is expected to be set as the auth token already.
     * @return array|null ['accessToken' => string, 'user' => RecodexUser] on success
     */
    public function getTokenAndUser(): ?array
    {
        $body = $this->post('extensions/' . $this->extensionId);
        if (!is_array($body) || empty($body['accessToken']) || empty($body['user'])) {
            throw new RecodexApiException("Unexpected ReCodEx API response from extension token endpoint.");
        }

        // wrap the user into a structure
        $body['user'] = new RecodexUser($body['user'], $this);
        return $body;
    }

    /**
     * Get all non-archived groups the user can see.
     */
    public function getGroups(): array
    {
        // TODO: this is just a placeholder, needs finishing
        $groups = $this->get('groups');
        return $groups;
    }
}
