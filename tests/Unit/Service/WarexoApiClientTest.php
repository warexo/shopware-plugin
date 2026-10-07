<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SystemConfig\Event\SystemConfigChangedEvent;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Warexo\Service\WarexoApiClient;

#[CoversClass(WarexoApiClient::class)]
final class WarexoApiClientTest extends TestCase
{
    private const CONFIG = [
        'apiUrl' => 'https://warexo.example/api/v1/',
        'apiUsername' => 'user@example.com',
        'apiPassword' => ' secret ',
        'apiClientId' => '123',
    ];

    public function testLoginAndAuthenticatedRequestsReuseToken(): void
    {
        $requests = [];
        $http = new MockHttpClient(static function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = [$method, $url, $options];
            return new MockResponse(count($requests) === 1 ? '{"token":"test-token"}' : '[{"id":1}]');
        });
        $client = new WarexoApiClient($http, $this->config(), $this->cache());

        static::assertSame([['id' => 1]], $client->request('GET', '/entity/product', [
            'query' => ['limit' => 10, 'fields' => ['id', 'sku']],
        ])->toArray());
        $client->request('PATCH', 'entity/product/1', ['json' => ['title' => 'New title']])->toArray();

        static::assertCount(3, $requests);
        static::assertSame('POST', $requests[0][0]);
        static::assertSame('https://warexo.example/api/v1/login', $requests[0][1]);
        static::assertSame(['username' => 'user@example.com', 'password' => ' secret '], json_decode($requests[0][2]['body'], true));
        static::assertArrayNotHasKey('authorization', $requests[0][2]['normalized_headers']);
        static::assertStringStartsWith('https://warexo.example/api/v1/entity/product?', $requests[1][1]);
        static::assertSame(['limit' => '10', 'fields' => ['id', 'sku']], $this->query($requests[1][1]));
        static::assertSame('PATCH', $requests[2][0]);
        static::assertSame('https://warexo.example/api/v1/entity/product/1', $requests[2][1]);
        static::assertSame(['title' => 'New title'], json_decode($requests[2][2]['body'], true));

        foreach ($requests as $index => $request) {
            static::assertSame(['X-Client-Id: 123'], $request[2]['normalized_headers']['x-client-id']);
            static::assertSame(['Accept: application/json'], $request[2]['normalized_headers']['accept']);
            static::assertSame(0, $request[2]['max_redirects']);
            static::assertSame(30.0, $request[2]['timeout']);
            static::assertSame(30.0, $request[2]['max_duration']);
            if ($index > 0) {
                static::assertSame(['Authorization: Bearer test-token'], $request[2]['normalized_headers']['authorization']);
            }
        }
    }

    public function testExpiredTokenLogsInAndRetriesOnce(): void
    {
        $requests = [];
        $http = new MockHttpClient(static function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = [$method, $url, $options];
            return match (count($requests)) {
                1 => new MockResponse('{"token":"old-token"}'),
                2 => new MockResponse('{"error":"Expired"}', ['http_code' => 401]),
                3 => new MockResponse('{"token":"new-token"}'),
                default => new MockResponse('{"id":42}'),
            };
        });
        $client = new WarexoApiClient($http, $this->config(), $this->cache());

        static::assertSame(['id' => 42], $client->request('POST', '_action/placeorder', ['json' => ['id' => 42]])->toArray());
        static::assertCount(4, $requests);
        static::assertSame($requests[0][1], $requests[2][1]);
        static::assertSame($requests[1][1], $requests[3][1]);
        static::assertSame($requests[1][2]['body'], $requests[3][2]['body']);
        static::assertSame(['Authorization: Bearer new-token'], $requests[3][2]['normalized_headers']['authorization']);
    }

    public function testSecondUnauthorizedResponseDoesNotLoop(): void
    {
        $http = $this->http([
            new MockResponse('{"token":"first"}'),
            new MockResponse('{}', ['http_code' => 401]),
            new MockResponse('{"token":"second"}'),
            new MockResponse('{}', ['http_code' => 401]),
        ], $requestCount);
        $response = (new WarexoApiClient($http, $this->config(), $this->cache()))->request('GET', 'entity/product');

        static::assertSame(401, $response->getStatusCode());
        static::assertSame(4, $requestCount);
        $this->expectException(ClientExceptionInterface::class);
        $response->toArray();
    }

    #[DataProvider('connectionChanges')]
    public function testChangedConfigurationDiscardsToken(string $key, string $value): void
    {
        $values = self::CONFIG;
        $config = $this->createMock(SystemConfigService::class);
        $config->method('getString')->willReturnCallback(static function (string $key) use (&$values): string {
            return $values[substr($key, strlen('AggroWarexoPlugin.config.'))];
        });
        $http = $this->http([
            new MockResponse('{"token":"first"}'), new MockResponse('{}'),
            new MockResponse('{"token":"second"}'), new MockResponse('{}'),
        ], $requestCount);
        $client = new WarexoApiClient($http, $config, $this->cache());
        $client->request('GET', 'entity/product')->toArray();
        $values[$key] = $value;
        $client->request('GET', 'entity/product')->toArray();

        static::assertSame(4, $requestCount);
    }

    public static function connectionChanges(): iterable
    {
        yield ['apiUrl', 'https://other.example/api/v1'];
        yield ['apiUsername', 'other-user'];
        yield ['apiPassword', 'other-password'];
        yield ['apiClientId', '456'];
    }

    public function testSalesChannelConfigurationIsPassedToShopware(): void
    {
        $config = $this->createMock(SystemConfigService::class);
        $config->expects(static::exactly(4))->method('getString')->willReturnCallback(static function (string $key, ?string $salesChannelId): string {
            static::assertSame('sales-channel-id', $salesChannelId);
            return self::CONFIG[substr($key, strlen('AggroWarexoPlugin.config.'))];
        });
        $http = $this->http([new MockResponse('{"token":"test-token"}'), new MockResponse('{}')], $requestCount);

        (new WarexoApiClient($http, $config, $this->cache()))->request('GET', 'entity/product', salesChannelId: 'sales-channel-id')->toArray();
    }

    #[DataProvider('missingConfiguration')]
    public function testMissingConfigurationDoesNotSendCredentials(string $key): void
    {
        $http = $this->http([], $requestCount);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configure the Warexo API');
        try {
            (new WarexoApiClient($http, $this->config([$key => '']), $this->cache()))->request('GET', 'entity/product');
        } finally {
            static::assertSame(0, $requestCount);
        }
    }

    public static function missingConfiguration(): iterable
    {
        foreach (array_keys(self::CONFIG) as $key) {
            yield [$key];
        }
    }

    #[DataProvider('invalidUrls')]
    public function testInvalidBaseUrlDoesNotSendCredentials(string $url): void
    {
        $http = $this->http([], $requestCount);
        $this->expectException(\RuntimeException::class);
        try {
            (new WarexoApiClient($http, $this->config(['apiUrl' => $url]), $this->cache()))->request('GET', 'entity/product');
        } finally {
            static::assertSame(0, $requestCount);
        }
    }

    public static function invalidUrls(): iterable
    {
        yield ['warexo.example/api/v1'];
        yield ['ftp://warexo.example/api/v1'];
        yield ['https://user:secret@warexo.example/api/v1'];
        yield ['https://warexo.example/api/v1?token=secret'];
        yield ['https://warexo.example/api/v1#fragment'];
        yield ['http://'];
    }

    #[DataProvider('invalidPaths')]
    public function testInvalidEndpointDoesNotSendCredentials(string $path): void
    {
        $http = $this->http([], $requestCount);
        $this->expectException(\InvalidArgumentException::class);
        try {
            (new WarexoApiClient($http, $this->config(), $this->cache()))->request('GET', $path);
        } finally {
            static::assertSame(0, $requestCount);
        }
    }

    public static function invalidPaths(): iterable
    {
        yield [''];
        yield ['https://other.example/entity/product'];
        yield ['//other.example/entity/product'];
        yield ['../login'];
        yield ['entity/../../login'];
        yield ['entity/%2e%2e/login'];
        yield ['entity/product?limit=10'];
        yield ['entity/product#fragment'];
        yield ['entity\\product'];
    }

    #[DataProvider('invalidTokens')]
    public function testLoginRequiresANonEmptyStringToken(string $body): void
    {
        $http = $this->http([new MockResponse($body)], $requestCount);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('did not return an access token');
        try {
            (new WarexoApiClient($http, $this->config(), $this->cache()))->request('GET', 'entity/product');
        } finally {
            static::assertSame(1, $requestCount);
        }
    }

    public static function invalidTokens(): iterable
    {
        yield ['{}'];
        yield ['{"token":""}'];
        yield ['{"token":"  "}'];
        yield ['{"token":123}'];
    }

    public function testLoginErrorPropagatesWithoutRetry(): void
    {
        $http = $this->http([new MockResponse('{"error":"Bad credentials"}', ['http_code' => 401])], $requestCount);
        $this->expectException(ClientExceptionInterface::class);
        try {
            (new WarexoApiClient($http, $this->config(), $this->cache()))->request('GET', 'entity/product');
        } finally {
            static::assertSame(1, $requestCount);
        }
    }

    public function testInvalidLoginJsonPropagates(): void
    {
        $http = $this->http([new MockResponse('not json')], $requestCount);
        $this->expectException(DecodingExceptionInterface::class);
        (new WarexoApiClient($http, $this->config(), $this->cache()))->request('GET', 'entity/product');
    }

    public function testTransportFailureIsNotRetried(): void
    {
        $http = $this->http([
            new MockResponse('{"token":"test-token"}'),
            new MockResponse('', ['error' => 'Connection failed']),
        ], $requestCount);
        $this->expectException(TransportException::class);
        try {
            (new WarexoApiClient($http, $this->config(), $this->cache()))->request('POST', 'entity/product', ['json' => ['sku' => 'TEST']]);
        } finally {
            static::assertSame(2, $requestCount);
        }
    }

    #[DataProvider('responseStatuses')]
    public function testOtherResponseStatusesAreReturnedWithoutRetry(int $status): void
    {
        $http = $this->http([
            new MockResponse('{"token":"test-token"}'),
            new MockResponse('', ['http_code' => $status]),
        ], $requestCount);
        $response = (new WarexoApiClient($http, $this->config(), $this->cache()))->request('DELETE', 'entity/product/1');

        static::assertSame($status, $response->getStatusCode());
        static::assertSame(2, $requestCount);
        $response->getContent(false);
    }

    public static function responseStatuses(): iterable
    {
        yield [204];
        yield [302];
        yield [403];
        yield [422];
        yield [500];
    }

    public function testTokenIsSharedAcrossIndependentPersistentCacheInstances(): void
    {
        $directory = sys_get_temp_dir() . '/warexo-cache-test-' . bin2hex(random_bytes(8));
        $http = $this->http([
            new MockResponse('{"token":"shared-token"}'), new MockResponse('{}'), new MockResponse('{}'),
        ], $requestCount);
        try {
            $firstCache = new TagAwareAdapter(new FilesystemAdapter('warexo-test', 0, $directory));
            (new WarexoApiClient($http, $this->config(), $firstCache))->request('GET', 'entity/product')->toArray();
            $secondCache = new TagAwareAdapter(new FilesystemAdapter('warexo-test', 0, $directory));
            (new WarexoApiClient($http, $this->config(), $secondCache))->request('GET', 'entity/product')->toArray();

            static::assertSame(3, $requestCount, 'Two service/cache instances must share one login.');
        } finally {
            (new Filesystem())->remove($directory);
        }
    }

    #[DataProvider('expiringTokens')]
    public function testExpiredAndNearlyExpiredJwtTokensAreNotReused(int $offset): void
    {
        $payload = rtrim(strtr(base64_encode(json_encode(['exp' => time() + $offset], JSON_THROW_ON_ERROR)), '+/', '-_'), '=');
        $token = 'header.' . $payload . '.signature';
        $http = $this->http([
            new MockResponse(json_encode(['token' => $token], JSON_THROW_ON_ERROR)), new MockResponse('{}'),
            new MockResponse('{"token":"replacement"}'), new MockResponse('{}'),
        ], $requestCount);
        $client = new WarexoApiClient($http, $this->config(), $this->cache());

        $client->request('GET', 'entity/product')->toArray();
        $client->request('GET', 'entity/product')->toArray();

        static::assertSame(4, $requestCount);
    }

    public static function expiringTokens(): iterable
    {
        yield 'expired' => [-60];
        yield 'within safety margin' => [15];
    }

    #[DataProvider('connectionChanges')]
    public function testApiSettingChangesInvalidateStoredToken(string $key, string $value): void
    {
        $http = $this->http([
            new MockResponse('{"token":"first"}'), new MockResponse('{}'),
            new MockResponse('{"token":"second"}'), new MockResponse('{}'),
        ], $requestCount);
        $cache = $this->cache();
        $client = new WarexoApiClient($http, $this->config(), $cache);
        $client->request('GET', 'entity/product')->toArray();
        $client->onConfigChanged(new SystemConfigChangedEvent('AggroWarexoPlugin.config.' . $key, $value, null));
        (new WarexoApiClient($http, $this->config(), $cache))->request('GET', 'entity/product')->toArray();

        static::assertSame(4, $requestCount);
    }

    public function testUnrelatedSettingChangesKeepToken(): void
    {
        $http = $this->http([
            new MockResponse('{"token":"cached"}'), new MockResponse('{}'), new MockResponse('{}'),
        ], $requestCount);
        $client = new WarexoApiClient($http, $this->config(), $this->cache());
        $client->request('GET', 'entity/product')->toArray();
        $client->onConfigChanged(new SystemConfigChangedEvent('AggroWarexoPlugin.config.decimalstock', true, null));
        $client->onConfigChanged(new SystemConfigChangedEvent('OtherPlugin.config.apiPassword', 'other', null));
        $client->request('GET', 'entity/product')->toArray();

        static::assertSame(3, $requestCount);
    }

    public function testSalesChannelInvalidationKeepsOtherSalesChannelToken(): void
    {
        $http = $this->http([
            new MockResponse('{"token":"channel-a"}'), new MockResponse('{}'),
            new MockResponse('{"token":"channel-b"}'), new MockResponse('{}'), new MockResponse('{}'),
            new MockResponse('{"token":"channel-a-replacement"}'), new MockResponse('{}'),
        ], $requestCount);
        $client = new WarexoApiClient($http, $this->config(), $this->cache());
        $client->request('GET', 'entity/product', salesChannelId: 'a')->toArray();
        $client->request('GET', 'entity/product', salesChannelId: 'b')->toArray();
        $client->onConfigChanged(new SystemConfigChangedEvent('AggroWarexoPlugin.config.apiPassword', 'changed', 'a'));
        $client->request('GET', 'entity/product', salesChannelId: 'b')->toArray();
        $client->request('GET', 'entity/product', salesChannelId: 'a')->toArray();

        static::assertSame(7, $requestCount);
    }

    public function testGlobalInvalidationAlsoExpiresSalesChannelTokens(): void
    {
        $http = $this->http([
            new MockResponse('{"token":"channel-a"}'), new MockResponse('{}'),
            new MockResponse('{"token":"channel-b"}'), new MockResponse('{}'),
            new MockResponse('{"token":"replacement-a"}'), new MockResponse('{}'),
            new MockResponse('{"token":"replacement-b"}'), new MockResponse('{}'),
        ], $requestCount);
        $client = new WarexoApiClient($http, $this->config(), $this->cache());
        $client->request('GET', 'entity/product', salesChannelId: 'a')->toArray();
        $client->request('GET', 'entity/product', salesChannelId: 'b')->toArray();
        $client->invalidateToken();
        $client->request('GET', 'entity/product', salesChannelId: 'a')->toArray();
        $client->request('GET', 'entity/product', salesChannelId: 'b')->toArray();

        static::assertSame(8, $requestCount);
    }

    public function testRejectedReplacementTokenIsRemovedFromCache(): void
    {
        $http = $this->http([
            new MockResponse('{"token":"first"}'), new MockResponse('{}', ['http_code' => 401]),
            new MockResponse('{"token":"rejected"}'), new MockResponse('{}', ['http_code' => 401]),
            new MockResponse('{"token":"fresh"}'), new MockResponse('{}'),
        ], $requestCount);
        $cache = $this->cache();
        $client = new WarexoApiClient($http, $this->config(), $cache);
        $client->request('GET', 'entity/product')->getContent(false);
        (new WarexoApiClient($http, $this->config(), $cache))->request('GET', 'entity/product')->toArray();

        static::assertSame(6, $requestCount);
    }

    public function testTokenAlreadyRenewedByAnotherWorkerIsReused(): void
    {
        $cache = $this->cache();
        $config = $this->config();
        $otherHttp = $this->http([new MockResponse('{"token":"renewed-by-other-worker"}'), new MockResponse('{}')], $otherCount);
        $count = 0;
        $http = new MockHttpClient(static function (string $method, string $url, array $options) use ($cache, $config, $otherHttp, &$count): MockResponse {
            ++$count;
            if ($count === 1) {
                return new MockResponse('{"token":"original"}');
            }
            if ($count === 2) {
                $otherClient = new WarexoApiClient($otherHttp, $config, $cache);
                $otherClient->invalidateToken();
                $otherClient->request('GET', 'entity/product')->toArray();
                return new MockResponse('{}', ['http_code' => 401]);
            }
            static::assertSame(['Authorization: Bearer renewed-by-other-worker'], $options['normalized_headers']['authorization']);
            return new MockResponse('{}');
        });

        (new WarexoApiClient($http, $config, $cache))->request('GET', 'entity/product')->toArray();
        static::assertSame(3, $count, 'The original worker must not perform an extra login.');
        static::assertSame(2, $otherCount);
    }

    /** @param list<MockResponse> $responses */
    private function http(array $responses, ?int &$requestCount): MockHttpClient
    {
        $requestCount = 0;

        // withOptions() clones MockHttpClient; share the counter through its factory.
        return new MockHttpClient(static function () use (&$responses, &$requestCount): MockResponse {
            ++$requestCount;
            static::assertNotEmpty($responses, 'Unexpected HTTP request.');
            return array_shift($responses);
        });
    }

    private function cache(): TagAwareAdapter
    {
        return new TagAwareAdapter(new ArrayAdapter());
    }

    private function config(array $overrides = []): SystemConfigService
    {
        $values = array_replace(self::CONFIG, $overrides);
        $config = $this->createMock(SystemConfigService::class);
        $config->method('getString')->willReturnCallback(static function (string $key) use ($values): string {
            return $values[substr($key, strlen('AggroWarexoPlugin.config.'))];
        });

        return $config;
    }

    private function query(string $url): array
    {
        parse_str(parse_url($url, PHP_URL_QUERY), $query);
        return $query;
    }
}
