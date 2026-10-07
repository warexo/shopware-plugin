<?php declare(strict_types=1);

namespace Warexo\Tests\Unit\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Shopware\Core\System\SystemConfig\SystemConfigService;
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
        $client = new WarexoApiClient($http, $this->config());

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
        $client = new WarexoApiClient($http, $this->config());

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
        $response = (new WarexoApiClient($http, $this->config()))->request('GET', 'entity/product');

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
        $client = new WarexoApiClient($http, $config);
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

        (new WarexoApiClient($http, $config))->request('GET', 'entity/product', salesChannelId: 'sales-channel-id')->toArray();
    }

    #[DataProvider('missingConfiguration')]
    public function testMissingConfigurationDoesNotSendCredentials(string $key): void
    {
        $http = $this->http([], $requestCount);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Configure the Warexo API');
        try {
            (new WarexoApiClient($http, $this->config([$key => ''])))->request('GET', 'entity/product');
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
            (new WarexoApiClient($http, $this->config(['apiUrl' => $url])))->request('GET', 'entity/product');
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
            (new WarexoApiClient($http, $this->config()))->request('GET', $path);
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
            (new WarexoApiClient($http, $this->config()))->request('GET', 'entity/product');
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
            (new WarexoApiClient($http, $this->config()))->request('GET', 'entity/product');
        } finally {
            static::assertSame(1, $requestCount);
        }
    }

    public function testInvalidLoginJsonPropagates(): void
    {
        $http = $this->http([new MockResponse('not json')], $requestCount);
        $this->expectException(DecodingExceptionInterface::class);
        (new WarexoApiClient($http, $this->config()))->request('GET', 'entity/product');
    }

    public function testTransportFailureIsNotRetried(): void
    {
        $http = $this->http([
            new MockResponse('{"token":"test-token"}'),
            new MockResponse('', ['error' => 'Connection failed']),
        ], $requestCount);
        $this->expectException(TransportException::class);
        try {
            (new WarexoApiClient($http, $this->config()))->request('POST', 'entity/product', ['json' => ['sku' => 'TEST']]);
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
        $response = (new WarexoApiClient($http, $this->config()))->request('DELETE', 'entity/product/1');

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
