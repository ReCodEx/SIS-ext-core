<?php

namespace App\Helpers;

use App\Exceptions\ConfigException;
use App\Exceptions\RecodexApiException;
use App\Model\Entity\User;
use Nette;
use GuzzleHttp;
use Nette\Utils\Arrays;
use Tracy\Debugger;

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
     * @param GuzzleHttp\Client|null $client optional injection of HTTP client for testing purposes
     */
    public function __construct(array $config, ?GuzzleHttp\Client $client = null)
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

        if (!$client) {
            $client = new GuzzleHttp\Client(['base_uri' => $this->apiBase]);
        }
        $this->client = $client;
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
     * Set authentication token which is send in headers of each request.
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
            if (is_array($body)) { // array indicate JSON structure
                $options[GuzzleHttp\RequestOptions::JSON] = $body;
            } else {
                $options[GuzzleHttp\RequestOptions::BODY] = $body;
            }
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
            Debugger::log("HTTP request to ReCodEx API failed (response $code).", Debugger::DEBUG);
            throw new RecodexApiException("HTTP request failed (response $code).");
        }

        $type = $response->getHeaderLine("Content-Type") ?? '';
        if (!str_starts_with($type, 'application/json')) {
            Debugger::log("JSON response expected from ReCodEx API but '$type' returned instead.", Debugger::DEBUG);
            throw new RecodexApiException("JSON response was expected but '$type' returned instead.");
        }

        $body = json_decode($response->getBody()->getContents(), true);
        Debugger::log($body, Debugger::DEBUG);
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
     * Perform a DELETE request and return decoded JSON response.
     * @param string $url suffix for the base URL
     * @param array $params to be encoded in URL query
     * @param array $headers initial HTTP headers
     * @return array|string|int|bool|null decoded JSON response
     */
    private function delete(string $url, array $params = [], array $headers = [])
    {
        $response = $this->client->delete($url, $this->prepareOptions($params, null, $headers));
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

    /*
     * REST API calls
     */

    /**
     * Complete the authentication process. Use tmp token to fetch full-token and user info.
     * The tmp token is expected to be set as the auth token already.
     * @return array|null ['accessToken' => string, 'user' => RecodexUser] on success
     */
    public function getTokenAndUser(): ?array
    {
        Debugger::log('ReCodEx::getTokenAndUser()', Debugger::DEBUG);
        $body = $this->post('extensions/' . $this->extensionId);
        if (!is_array($body) || empty($body['accessToken']) || empty($body['user'])) {
            throw new RecodexApiException("Unexpected ReCodEx API response from extension token endpoint.");
        }

        // wrap the user into a structure
        $body['user'] = new RecodexUser($body['user'], $this);
        return $body;
    }

    /**
     * Retrieve user data.
     * @param string $id
     * @return RecodexUser|null null if user does not exist
     */
    public function getUser(string $id): ?RecodexUser
    {
        Debugger::log('ReCodEx::getUser(' . $id . ')', Debugger::DEBUG);
        $body = $this->get("users/$id");
        return $body ? new RecodexUser($body, $this) : null;
    }

    /**
     * Update basic user data (name parts and email).
     * @param User $user entity from which the data are posted for update.
     * @return RecodexUser updated data of the user
     */
    public function updateUser(User $user): RecodexUser
    {
        $body = [
            'titlesBeforeName' => $user->getTitlesBeforeName(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'titlesAfterName' => $user->getTitlesAfterName(),
            'email' => $user->getEmail(),
        ];
        $id = $user->getId();
        Debugger::log('ReCodEx::updateUser(' . $id . ')', Debugger::INFO);
        Debugger::log('New user data: ' . json_encode($body), Debugger::DEBUG);
        $res = $this->post("users/$id", [], $body);
        if (!$res || !is_array($res) || empty($res['user']) || ($res['user']['id'] ?? '') !== $id) {
            throw new RecodexApiException("Unexpected ReCodEx API response from update user's profile endpoint.");
        }

        return new RecodexUser($res['user'], $this);
    }

    /**
     * Set or update external ID for given ext. auth. service.
     * @param string $id of the user in the ReCodEx
     * @param string $service auth. service identifier
     * @param string $externalId the new ID to be set for $service
     * @return RecodexUser updated data of the user
     */
    public function setExternalId(string $id, string $service, string $externalId): RecodexUser
    {
        Debugger::log("ReCodEx::setExternalId('$id', '$service', '$externalId')", Debugger::INFO);
        $res = $this->post("users/$id/external-login/$service", [], ['externalId' => $externalId]);
        if (!$res || !is_array($res) || ($res['id'] ?? '') !== $id) {
            throw new RecodexApiException("Unexpected ReCodEx API response from update user's external ID endpoint.");
        }
        return new RecodexUser($res, $this);
    }

    /**
     * Set or update external ID for given ext. auth. service.
     * @param string $id of the user in the ReCodEx
     * @param string $service auth. service identifier
     * @return RecodexUser updated data of the user
     */
    public function removeExternalId(string $id, string $service): RecodexUser
    {
        Debugger::log("ReCodEx::removeExternalId('$id', '$service')", Debugger::INFO);
        $res = $this->delete("users/$id/external-login/$service");
        if (!$res || !is_array($res) || ($res['id'] ?? '') !== $id) {
            throw new RecodexApiException("Unexpected ReCodEx API response from remove user's external ID endpoint.");
        }
        return new RecodexUser($res, $this);
    }

    /**
     * Get all non-archived groups with attributes and membership relation to given user.
     * @param User $user whose membership relation is being injected
     * @return RecodexGroup[] groups indexed by their IDs
     */
    public function getGroups(User $user): array
    {
        Debugger::log('ReCodEx::getGroups(' . $user->getId() . ')', Debugger::DEBUG);
        $body = $this->get(
            "group-attributes",
            ['instance' => $user->getInstanceId(), 'service' => $this->extensionId, 'user' => $user->getId()]
        );
        $groups = [];
        foreach ($body as $groupData) {
            $group = new RecodexGroup($groupData, $this->extensionId);
            $groups[$group->id] = $group;
        }
        return $groups;
    }

    /**
     * Add external attribute to selected group (service ID is injected automatically).
     * @param string $groupId ReCodEx ID of a group for which the attribute is being added
     * @param string $key
     * @param string $value
     */
    public function addAttribute(string $groupId, string $key, string $value): void
    {
        Debugger::log("ReCodEx::addAttribute('$groupId', '$key', '$value')", Debugger::INFO);
        $this->post("group-attributes/$groupId", [], [
            'service' => $this->extensionId,
            'key' => $key,
            'value' => $value
        ]);
    }

    /**
     * Remove external attribute from selected group (service ID is injected automatically).
     * @param string $groupId ReCodEx ID of a group from which the attribute is being removed
     * @param string $key
     * @param string $value
     */
    public function removeAttribute(string $groupId, string $key, string $value): void
    {
        Debugger::log("ReCodEx::removeAttribute('$groupId', '$key', '$value')", Debugger::INFO);
        $this->delete("group-attributes/$groupId", [
            'service' => $this->extensionId,
            'key' => $key,
            'value' => $value
        ]);
    }

    /**
     * Add student to group.
     * @param string $groupId ReCodEx ID of a group to which the student is being added
     * @param User $student
     */
    public function addStudentToGroup(string $groupId, User $student): void
    {
        Debugger::log("ReCodEx::addStudentToGroup('$groupId', '{$student->getId()}')", Debugger::INFO);
        $studentId = $student->getId();
        $this->post("groups/$groupId/students/$studentId");
    }

    /**
     * Remove student from group.
     * @param string $groupId ReCodEx ID of a group from which the student is being removed
     * @param User $student
     */
    public function removeStudentFromGroup(string $groupId, User $student): void
    {
        Debugger::log("ReCodEx::removeStudentFromGroup('$groupId', '{$student->getId()}')", Debugger::INFO);
        $studentId = $student->getId();
        $this->delete("groups/$groupId/students/$studentId");
    }
}
