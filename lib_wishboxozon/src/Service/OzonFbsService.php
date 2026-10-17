<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license         GNU General Public License version 2 or later;
 */

namespace WishboxOzonLibrary\Service;

use DateMalformedStringException;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use stdClass;
use Wishbox\Dto\OrderImport\MarketplaceOrder;
use Wishbox\Dto\OrderImport\MarketplaceOrderDelivery;
use Wishbox\Dto\OrderImport\MarketplaceOrderItem;
use WishboxOzonLibrary\Api\OzonApiClient;
use WishboxOzonLibrary\Exception\OzonApiException;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Manages Ozon FBS postings, shipment confirmation, and package labels.
 *
 * @since        1.0.0
 *
 * @noinspection PhpUnused
 */
final readonly class OzonFbsService
{
	/**
	 * @since 1.0.0
	 */
	public function __construct(private OzonApiClient $apiClient)
	{
	}

	/**
	 * Load a page of FBS postings.
	 *
	 * @param   array<string, mixed>  $filter  Ozon posting filters.
	 * @param   array<string, bool>   $with    Optional response sections.
	 *
	 * @throws OzonApiException
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function getPostings(
		array  $filter,
		int    $limit = 1000,
		string $cursor = '',
		string $direction = 'ASC',
		array  $with = []
	): stdClass
	{
		$direction = strtoupper($direction);

		if (!in_array($direction, ['ASC', 'DESC'], true))
		{
			throw new InvalidArgumentException('Ozon posting direction must be ASC or DESC.');
		}

		return $this->apiClient->call(
			'/v4/posting/fbs/list',
			[
				'sort_dir' => strtolower($direction),
				'filter'   => (object) $filter,
				'limit'    => min(1000, max(1, $limit)),
				'cursor'   => $cursor,
				'with'     => (object) $with,
			]
		);
	}

	/**
	 * Load Ozon FBS postings as transport-neutral order DTOs.
	 *
	 * @param   int           $limit         Maximum orders to load.
	 * @param   int           $lookbackDays  Number of previous days to inspect.
	 * @param   list<string>  $statuses      Ozon posting statuses.
	 * @param   string        $cursor        Ozon pagination cursor.
	 *
	 * @return list<MarketplaceOrder> Normalized marketplace orders.
	 *
	 * @throws DateMalformedStringException When the date interval cannot be created.
	 * @throws OzonApiException When Ozon rejects the request.
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function getOrders(
		int $limit = 10,
		int $lookbackDays = 5,
		array $statuses = ['awaiting_packaging'],
		string $cursor = ''
	): array
	{
		$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
		$response = max(1, $limit)
				|> (fn($x) => min(1000, $x))
				|> (fn($x) => $this->getPostings(filter: ['since' => $now->modify('-' . min(365, max(1, $lookbackDays)) . ' days')->format('Y-m-d\TH:i:s\Z'), 'to' => $now->format('Y-m-d\TH:i:s\Z'), 'status' => array_map('trim', $statuses)
						|> array_filter(...)
						|> array_values(...),], limit: $x, cursor: $cursor, direction: 'DESC', with: ['analytics_data' => true, 'barcodes' => true, 'financial_data' => true,]));
		$postings = is_array($response->postings ?? null) ? $response->postings : [];

		return array_map($this->normalizeOrder(...), $postings);
	}

	/**
	 * Load one FBS posting by its posting number.
	 *
	 * @param   array<string, bool>  $with  Optional response sections.
	 *
	 * @throws OzonApiException
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function getPosting(string $postingNumber, array $with = []): stdClass
	{
		$postingNumber = $this->validatePostingNumber($postingNumber);

		return $this->apiClient->call(
			'/v3/posting/fbs/get',
			[
				'posting_number' => $postingNumber,
				'with'           => (object) $with,
			]
		);
	}

	/**
	 * Package an FBS posting and move it to the awaiting-delivery state.
	 *
	 * @param   list<array<string, mixed>>  $packages  Ozon package descriptions.
	 * @param   array<string, bool>         $with      Optional response sections.
	 *
	 * @throws OzonApiException
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function ship(string $postingNumber, array $packages, array $with = []): stdClass
	{
		$postingNumber = $this->validatePostingNumber($postingNumber);

		if ($packages === [])
		{
			throw new InvalidArgumentException('An Ozon shipment must contain at least one package.');
		}

		return $this->apiClient->call(
			'/v4/posting/fbs/ship',
			[
				'posting_number' => $postingNumber,
				'packages'       => array_values($packages),
				'with'           => (object) $with,
			]
		);
	}

	/**
	 * Download a combined PDF label for one or more FBS postings.
	 *
	 * @param   list<string>  $postingNumbers  Posting numbers ready for label printing.
	 *
	 * @throws OzonApiException
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function getPackageLabels(array $postingNumbers): string
	{
		if ($postingNumbers === [])
		{
			throw new InvalidArgumentException('At least one Ozon posting number is required.');
		}

		$postingNumbers = array_map($this->validatePostingNumber(...), $postingNumbers);

		return $this->apiClient->downloadPdf(
			'/v2/posting/fbs/package-label',
			['posting_number' => array_values($postingNumbers)]
		);
	}

	/**
	 * @since 1.0.0
	 */
	private function validatePostingNumber(string $postingNumber): string
	{
		$postingNumber = trim($postingNumber);

		if ($postingNumber === '')
		{
			throw new InvalidArgumentException('Ozon posting number must not be empty.');
		}

		return $postingNumber;
	}

	/**
	 * Normalize one Ozon posting.
	 *
	 * @param   mixed  $posting  Raw Ozon FBS posting.
	 *
	 * @return MarketplaceOrder Normalized marketplace order.
	 *
	 * @since 1.0.0
	 */
	private function normalizeOrder(mixed $posting): MarketplaceOrder
	{
		if (!$posting instanceof stdClass)
		{
			return new MarketplaceOrder(
				sourceId: '',
				externalId: 'unknown',
				error: 'Ozon returned an invalid posting.'
			);
		}

		$postingNumber = trim((string) ($posting->posting_number ?? ''));

		if ($postingNumber === '')
		{
			return new MarketplaceOrder(
				sourceId: (string) ($posting->order_id ?? ''),
				externalId: 'unknown',
				error: 'Ozon returned a posting without a posting number.'
			);
		}

		$customer = ($posting->customer ?? null) instanceof stdClass
			? $posting->customer
			: new stdClass();
		$addressee = ($posting->addressee ?? null) instanceof stdClass
			? $posting->addressee
			: new stdClass();
		$name = trim((string) ($addressee->name ?? $customer->name ?? ''));
		$nameParts = preg_split('/\s+/u', $name, 3, PREG_SPLIT_NO_EMPTY) ?: [];
		$products = array_filter(
			is_array($posting->products ?? null) ? $posting->products : [],
			static fn (mixed $product): bool => $product instanceof stdClass
		);

		return new MarketplaceOrder(
			sourceId: (string) ($posting->order_id ?? $postingNumber),
			externalId: $postingNumber,
			items: array_values(array_map($this->normalizeOrderItem(...), $products)),
			firstName: (string) ($nameParts[1] ?? $nameParts[0] ?? 'Ozon'),
			lastName: (string) ($nameParts[0] ?? 'Customer'),
			patronymic: (string) ($nameParts[2] ?? ''),
			phone: (string) ($addressee->phone ?? $customer->phone ?? ''),
			email: (string) ($customer->customer_email ?? $customer->email ?? ''),
			customerComment: (string) ($customer->comment ?? ''),
			managerComment: 'Imported from Ozon posting ' . $postingNumber
				. '; order #' . ($posting->order_number ?? $posting->order_id ?? '')
				. '; status ' . ($posting->status ?? '') . '.',
			delivery: $this->normalizeDelivery($customer)
		);
	}

	/**
	 * Normalize one Ozon posting product.
	 *
	 * @param   stdClass  $product  Ozon posting product.
	 *
	 * @return MarketplaceOrderItem Normalized product.
	 *
	 * @since 1.0.0
	 */
	private function normalizeOrderItem(stdClass $product): MarketplaceOrderItem
	{
		return new MarketplaceOrderItem(
			productId: (int) ($product->sku ?? 0),
			productCode: trim((string) ($product->offer_id ?? '')),
			quantity: max(1.0, (float) ($product->quantity ?? 1)),
			price: is_numeric($product->price ?? null) ? (float) $product->price : 0.0,
			name: (string) ($product->name ?? '')
		);
	}

	/**
	 * Normalize an Ozon delivery address.
	 *
	 * @param   stdClass  $customer  Ozon customer data.
	 *
	 * @return MarketplaceOrderDelivery Normalized delivery data.
	 *
	 * @since 1.0.0
	 */
	private function normalizeDelivery(stdClass $customer): MarketplaceOrderDelivery
	{
		$address = ($customer->address ?? null) instanceof stdClass
			? $customer->address
			: new stdClass();

		return new MarketplaceOrderDelivery(
			country: (string) ($address->country ?? ''),
			region: (string) ($address->region ?? ''),
			city: (string) ($address->city ?? ''),
			zip: (string) ($address->zip_code ?? ''),
			text: (string) ($address->address_tail ?? $address->address_line ?? ''),
			notes: (string) ($address->comment ?? '')
		);
	}
}
