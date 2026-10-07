<?php declare(strict_types=1);

namespace Warexo\Service;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class WarexoApiClient implements EventSubscriberInterface
{
    private const CACHE_PREFIX = 'warexo-api-token-';
    private const CONFIG_KEYS = ['apiUrl', 'apiUsername', 'apiPassword', 'apiClientId'];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SystemConfigService $systemConfigService,
        private readonly TagAwareCacheInterface $cache
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

        $cacheKey = self::CACHE_PREFIX . hash('sha256', serialize([$url, $username, $password, $clientId, $salesChannelId]));

        $client = $this->httpClient->withOptions([
            'headers' => ['Accept' => 'application/json', 'X-Client-Id' => $clientId],
            'timeout' => 30,
            'max_duration' => 30,
            'max_redirects' => 0,
        ]);

        $loadToken = fn (): string => $this->cache->get($cacheKey, function (ItemInterface $item) use ($client, $url, $username, $password, $salesChannelId): string {
            $token = $this->login($client, $url, $username, $password);
            $item->expiresAfter($this->tokenLifetime($token));
            $item->tag([self::configTag(null), self::configTag($salesChannelId)]);

            return $token;
        }, 0.0);

        $token = $loadToken();
        $options['auth_bearer'] = $token;
        $response = $client->request($method, $url . '/' . ltrim($path, '/'), $options);

        if ($response->getStatusCode() === 401) {
            $response->cancel();
            // Another worker may already have replaced the rejected token.
            $replacement = $loadToken();
            if ($replacement === $token) {
                $this->cache->delete($cacheKey);
                $replacement = $loadToken();
            }
            $options['auth_bearer'] = $replacement;
            $response = $client->request($method, $url . '/' . ltrim($path, '/'), $options);
            if ($response->getStatusCode() === 401) {
                $this->cache->delete($cacheKey);
            }
        }

        return $response;
    }

    public static function getSubscribedEvents(): array
    {
        return [SystemConfigChangedEvent::class => 'onConfigChanged'];
    }

    public function onConfigChanged(SystemConfigChangedEvent $event): void
    {
        foreach (self::CONFIG_KEYS as $key) {
            if ($event->getKey() === 'AggroWarexoPlugin.config.' . $key) {
                $this->invalidateToken($event->getSalesChannelId());

                return;
            }
        }
    }

    public function invalidateToken(?string $salesChannelId = null): void
    {
        $this->cache->invalidateTags([self::configTag($salesChannelId)]);
    }

    private static function configTag(?string $salesChannelId): string
    {
        return self::CACHE_PREFIX . 'config-' . ($salesChannelId ?? 'global');
    }

    private function tokenLifetime(string $token): int
    {
        // Decode only for expiry; authentication and signature validation remain with Warexo.
        $parts = explode('.', $token);
        $payload = isset($parts[1]) ? base64_decode(strtr($parts[1], '-_', '+/'), true) : false;
        $claims = $payload === false ? null : json_decode($payload, true);

        if (is_array($claims) && isset($claims['exp']) && is_int($claims['exp'])) {
            return max(0, $claims['exp'] - time() - 30);
        }

        return 300;
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
