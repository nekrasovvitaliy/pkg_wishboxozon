<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonLibrary\Service;

use JsonException;
use stdClass;
use WishboxOzonLibrary\Api\OzonApiClient;
use WishboxOzonLibrary\Exception\OzonApiException;
use WishboxOzonLibrary\Exception\OzonProductUpdateException;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Reads and updates products through the Ozon Seller API.
 *
 * @since 1.0.0
 */
final readonly class OzonProductService
{
	/**
	 * @since 1.0.0
	 */
	public function __construct(private OzonApiClient $apiClient)
	{
	}

	/**
	 * Load a page of product identifiers using the current cursor-based endpoint.
	 *
	 * @param   array<string, mixed>  $filter  Ozon product filters.
	 *
	 * @throws OzonApiException
	 *
	 * @since 1.0.0
	 */
	public function getProductList(array $filter = [], string $lastId = '', int $limit = 1000): stdClass
	{
		return $this->apiClient->call(
			'/v3/product/list',
			[
				'filter'  => (object) $filter,
				'last_id' => $lastId,
				'limit'   => min(1000, max(1, $limit)),
			]
		);
	}

	/**
	 * Load detailed product information for identifiers accepted by Ozon.
	 *
	 * @param   array<string, mixed>  $data  Complete /v3/product/info/list payload.
	 *
	 * @throws OzonApiException
	 *
	 * @since 1.0.0
	 */
	public function getProductInfoList(array $data): stdClass
	{
		return $this->apiClient->call('/v3/product/info/list', $data);
	}

	/**
	 * Load product prices with cursor pagination.
	 *
	 * @param   array<string, mixed>  $filter  Ozon price filters.
	 *
	 * @throws OzonApiException
	 *
	 * @since 1.0.0
	 */
	public function getPrices(array $filter = [], string $cursor = '', int $limit = 1000): stdClass
	{
		return $this->apiClient->call(
			'/v5/product/info/prices',
			[
				'cursor' => $cursor,
				'filter' => (object) $filter,
				'limit'  => min(1000, max(1, $limit)),
			]
		);
	}

	/**
	 * Load product stocks with cursor pagination.
	 *
	 * @param   array<string, mixed>  $filter  Ozon stock filters.
	 *
	 * @throws OzonApiException
	 *
	 * @since 1.0.0
	 */
	public function getStocks(array $filter = [], string $cursor = '', int $limit = 1000): stdClass
	{
		return $this->apiClient->call(
			'/v4/product/info/stocks',
			[
				'cursor' => $cursor,
				'filter' => (object) $filter,
				'limit'  => min(1000, max(1, $limit)),
			]
		);
	}

	/**
	 * Import or update product cards.
	 *
	 * @param   list<array<string, mixed>>  $items  Product cards in Ozon's current schema.
	 *
	 * @throws OzonApiException
	 *
	 * @since 1.0.0
	 */
	public function importProducts(array $items): stdClass
	{
		if ($items === [])
		{
			throw new OzonApiException('An Ozon product import must contain at least one item.');
		}

		return $this->apiClient->call('/v3/product/import', ['items' => array_values($items)]);
	}

	/**
	 * Update product prices and fail on the first rejected item.
	 *
	 * @param   list<array<string, mixed>>  $prices  Price records in Ozon's current schema.
	 *
	 * @throws OzonApiException
	 * @throws OzonProductUpdateException
	 *
	 * @since 1.0.0
	 */
	public function updatePrices(array $prices): stdClass
	{
		if ($prices === [])
		{
			throw new OzonApiException('An Ozon price update must contain at least one item.');
		}

		$response = $this->apiClient->call('/v1/product/import/prices', ['prices' => array_values($prices)]);
		$this->validateUpdateResult($response, 'price');

		return $response;
	}

	/**
	 * Update product stocks and fail on the first rejected item.
	 *
	 * @param   list<array<string, mixed>>  $stocks  Stock records in Ozon's current schema.
	 *
	 * @throws OzonApiException
	 * @throws OzonProductUpdateException
	 *
	 * @since 1.0.0
	 */
	public function updateStocks(array $stocks): stdClass
	{
		if ($stocks === [])
		{
			throw new OzonApiException('An Ozon stock update must contain at least one item.');
		}

		$response = $this->apiClient->call('/v2/products/stocks', ['stocks' => array_values($stocks)]);
		$this->validateUpdateResult($response, 'stock');

		return $response;
	}

	/**
	 * @throws OzonApiException
	 * @throws OzonProductUpdateException
	 *
	 * @since 1.0.0
	 */
	private function validateUpdateResult(stdClass $response, string $operation): void
	{
		if (!is_array($response->result ?? null) || $response->result === [])
		{
			throw new OzonApiException('Ozon returned an empty or invalid ' . $operation . ' update result.');
		}

		foreach ($response->result as $item)
		{
			if (!$item instanceof stdClass || ($item->updated ?? false) === true)
			{
				continue;
			}

			$error = is_array($item->errors ?? null) ? reset($item->errors) : null;
			$errorCode = $error instanceof stdClass ? (string) ($error->code ?? '') : '';
			$message = 'Ozon rejected a product ' . $operation . ' update';

			if ($errorCode !== '')
			{
				$message .= ': ' . $errorCode;
			}

			try
			{
				$message .= ' ' . json_encode($item, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
			}
			catch (JsonException)
			{
				$message .= '.';
			}

			throw new OzonProductUpdateException(
				$message,
				(int) ($item->product_id ?? 0),
				$errorCode
			);
		}
	}
}
