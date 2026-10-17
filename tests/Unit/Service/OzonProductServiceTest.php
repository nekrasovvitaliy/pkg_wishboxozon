<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Unit\Service;

use Joomla\Http\Http;
use Joomla\Http\Response;
use PHPUnit\Framework\TestCase;
use WishboxOzonLibrary\Api\OzonApiClient;
use WishboxOzonLibrary\Service\OzonProductService;

/**
 * Tests normalized Ozon product updates.
 *
 * @since 1.0.0
 */
final class OzonProductServiceTest extends TestCase
{
	/**
	 * Verify loading product identifiers from all cursor pages.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testLoadsAllProductIds(): void
	{
		$requests = [];
		$responses = [
			'{"result":{"items":[{"offer_id":"CRM-101","product_id":101}],"last_id":"next"}}',
			'{"result":{"items":[{"offer_id":"CRM-102","product_id":102}],"last_id":""}}',
		];
		$http = $this->createMock(Http::class);
		$http->expects(self::exactly(2))
			->method('post')
			->willReturnCallback(
				static function (string $url, string $body) use (&$requests, &$responses): Response {
					$requests[] = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
					$response = new Response('php://memory', 200, ['Content-Type' => 'application/json']);
					$response->getBody()
						->write(array_shift($responses));

					return $response;
				}
			);
		$service = new OzonProductService(
			new OzonApiClient('client-id', 'api-key', $http)
		);

		self::assertSame(
			[
				['offer_id' => 'CRM-101', 'product_id' => 101],
				['offer_id' => 'CRM-102', 'product_id' => 102],
			],
			$service->getProductIds()
		);
		self::assertSame('', $requests[0]['last_id']);
		self::assertSame('next', $requests[1]['last_id']);
	}

	/**
	 * Verify price and stock payload generation.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testUpdatesNormalizedProducts(): void
	{
		$requests = [];
		$http = $this->createMock(Http::class);
		$http->expects(self::exactly(2))
			->method('post')
			->willReturnCallback(
				static function (string $url, string $body) use (&$requests): Response {
					$requests[] = ['url' => $url, 'body' => json_decode($body, true, 512, JSON_THROW_ON_ERROR)];
					$response = new Response('php://memory', 200, ['Content-Type' => 'application/json']);
					$response->getBody()
						->write('{"result":[{"product_id":101,"updated":true,"errors":[]}]}');

					return $response;
				}
			);
		$service = new OzonProductService(
			new OzonApiClient('client-id', 'api-key', $http)
		);

		$result = $service->updateProducts(
			[
				['offer_id' => 'CRM-101', 'product_id' => 101, 'price' => 1490.0, 'stock' => 3],
				['offer_id' => '', 'product_id' => 0, 'price' => null, 'stock' => 0],
			],
			77,
			'RUB'
		);

		self::assertSame(1, $result['updated']);
		self::assertSame(1, $result['skipped']);
		self::assertSame(0, $result['failed']);
		self::assertSame('/v1/product/import/prices', parse_url($requests[0]['url'], PHP_URL_PATH));
		self::assertSame('1490.00', $requests[0]['body']['prices'][0]['price']);
		self::assertSame('/v2/products/stocks', parse_url($requests[1]['url'], PHP_URL_PATH));
		self::assertSame(77, $requests[1]['body']['stocks'][0]['warehouse_id']);
	}
}
