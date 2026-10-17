<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Unit\Service;

use Joomla\Http\Http;
use Joomla\Http\Response;
use PHPUnit\Framework\TestCase;
use Wishbox\Dto\OrderImport\MarketplaceOrder;
use WishboxOzonLibrary\Api\OzonApiClient;
use WishboxOzonLibrary\Service\OzonFbsService;

/**
 * Tests normalized Ozon FBS orders.
 *
 * @since 1.0.0
 */
final class OzonFbsServiceTest extends TestCase
{
	/**
	 * Ensure an Ozon posting is converted to the shared marketplace order format.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function testGetsNormalizedOrders(): void
	{
		$response = new Response('php://memory', 200, ['Content-Type' => 'application/json']);
		$response->getBody()
			->write(json_encode([
				'postings' => [[
					'posting_number' => '1000001-0001-1',
					'order_id'       => 101,
					'order_number'   => '101-TEST',
					'status'         => 'awaiting_packaging',
					'addressee'      => [
						'name'  => 'Иванов Иван Иванович',
						'phone' => '+79990000000',
					],
					'customer'       => [
						'customer_email' => 'customer@example.com',
						'address'        => [
							'country'      => 'RU',
							'city'         => 'Москва',
							'address_tail' => 'ул. Тестовая, 1',
						],
					],
					'products'       => [[
						'sku'      => 12345,
						'offer_id' => 'product-alias',
						'quantity' => 2,
						'price'    => '1500.50',
						'name'     => 'Test product',
					]],
				]],
			], JSON_THROW_ON_ERROR));

		$http = $this->createMock(Http::class);
		$http->expects(self::once())
			->method('post')
			->with(
				'https://api-seller.ozon.ru/v4/posting/fbs/list',
				self::callback(
					static function (string $body): bool {
						$request = json_decode($body, true, 512, JSON_THROW_ON_ERROR);

						return $request['limit'] === 10
							&& $request['filter']['status'] === ['awaiting_packaging'];
					}
				),
				self::isType('array')
			)
			->willReturn($response);
		$ozonApiClient = new OzonApiClient('client-id', 'api-key', $http);
		$ozonFbsService = new OzonFbsService($ozonApiClient);

		$orders = $ozonFbsService->getOrders();

		self::assertCount(1, $orders);
		self::assertInstanceOf(MarketplaceOrder::class, $orders[0]);
		self::assertSame('1000001-0001-1', $orders[0]->externalId);
		self::assertSame('Иван', $orders[0]->firstName);
		self::assertSame('Иванов', $orders[0]->lastName);
		self::assertSame('product-alias', $orders[0]->items[0]->productCode);
		self::assertSame(1500.5, $orders[0]->items[0]->price);
		self::assertSame('Москва', $orders[0]->delivery->city);
	}
}
