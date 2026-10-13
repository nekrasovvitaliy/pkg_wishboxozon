<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Unit\Api;

use Joomla\Http\Http;
use Joomla\Http\Response;
use PHPUnit\Framework\TestCase;
use WishboxOzonLibrary\Api\OzonApiClient;

final class OzonApiClientTest extends TestCase
{
	public function testAddsProxyTokenToRequestHeaders(): void
	{
		$response = new Response('php://memory', 200, ['Content-Type' => 'application/json']);
		$response->getBody()
			->write('{"result":true}');

		$http = $this->createMock(Http::class);
		$http->expects(self::once())
			->method('post')
			->with(
				'https://proxy.example.com/ozon-api-proxy.php/v1/warehouse/list',
				'{}',
				self::callback(
					static fn (array $headers): bool => $headers['Client-Id'] === 'client-id'
						&& $headers['Api-Key'] === 'api-key'
						&& $headers['X-Ozon-Proxy-Token'] === 'proxy-secret'
				)
			)
			->willReturn($response);
		$ozonApiClient = new OzonApiClient(
			clientId: 'client-id',
			apiKey: 'api-key',
			http: $http,
			apiUrl: 'https://proxy.example.com/ozon-api-proxy.php',
			proxyToken: 'proxy-secret'
		);

		$result = $ozonApiClient->call('/v1/warehouse/list');

		self::assertTrue($result->result);
	}
}
