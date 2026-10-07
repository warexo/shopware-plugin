<?php declare(strict_types=1);

namespace Warexo\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class WarexoApiClient
{
    private ?string $connectionKey = null;
    private ?string $token = null;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SystemConfigService $systemConfigService
    ) {
    }

    /**
     * Paths are relative to the configured API URL, e.g. "entity/product".
     * HTTP, transport and JSON errors use Symfony HttpClient's exceptions.
     *
     * @param array<string, mixed> $options Symfony HttpClient request options (e.g. query, json)
     */
    public function request(string $method, string $path, array $options = [], ?string $salesChannelId = null): ResponseInterface
    {
        if (!preg_match('~^/?[a-zA-Z0-9_-]+(?:/[a-zA-Z0-9_-]+)*/?$~D', $path)) {
            throw new \InvalidArgumentException('Warexo API paths must be relative endpoints. Use the query option for query parameters.');
        }

        $url = rtrim(trim($this->systemConfigService->getString('AggroWarexoPlugin.config.apiUrl', $salesChannelId)), '/');
        $username = trim($this->systemConfigService->getString('AggroWarexoPlugin.config.apiUsername', $salesChannelId));
        $password = $this->systemConfigService->getString('AggroWarexoPlugin.config.apiPassword', $salesChannelId);
        $clientId = trim($this->systemConfigService->getString('AggroWarexoPlugin.config.apiClientId', $salesChannelId));

        if ($url === '' || $username === '' || $password === '' || $clientId === '') {
            throw new \RuntimeException('Configure the Warexo API URL, username, password and client ID in the plugin settings before using the API.');
        }

        $urlParts = parse_url($url);
        if ($urlParts === false || !in_array($urlParts['scheme'] ?? '', ['https', 'http'], true)
            || empty($urlParts['host']) || isset($urlParts['user']) || isset($urlParts['pass'])
            || isset($urlParts['query']) || isset($urlParts['fragment'])) {
            throw new \RuntimeException('The Warexo API URL must be an HTTP(S) URL without credentials, query parameters or a fragment.');
        }

        $connectionKey = hash('sha256', serialize([$url, $username, $password, $clientId]));
        if ($this->connectionKey !== $connectionKey) {
            $this->connectionKey = $connectionKey;
            $this->token = null;
        }

        $client = $this->httpClient->withOptions([
            'headers' => ['Accept' => 'application/json', 'X-Client-Id' => $clientId],
            'timeout' => 30,
            'max_duration' => 30,
            'max_redirects' => 0,
        ]);

        $this->token ??= $this->login($client, $url, $username, $password);
        $options['auth_bearer'] = $this->token;
        $response = $client->request($method, $url . '/' . ltrim($path, '/'), $options);

        if ($response->getStatusCode() === 401) {
            $response->cancel();
            $this->token = null;
            $this->token = $this->login($client, $url, $username, $password);
            $options['auth_bearer'] = $this->token;
            $response = $client->request($method, $url . '/' . ltrim($path, '/'), $options);
        }

        return $response;
    }

    private function login(HttpClientInterface $client, string $url, string $username, string $password): string
    {
        $data = $client->request('POST', $url . '/login', [
            'json' => ['username' => $username, 'password' => $password],
        ])->toArray();

        $token = $data['token'] ?? null;
        if (!is_string($token) || trim($token) === '') {
            throw new \RuntimeException('The Warexo API login did not return an access token.');
        }

        return $token;
    }
}
