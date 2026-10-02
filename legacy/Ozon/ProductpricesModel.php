<?php
/**
 * @copyright   (C) 2013-2023 Nekrasov Vitaliy
 * @license     GNU General Public License version 2 or later
 */
namespace Joomla\Component\Jshopping\Site\Model\Wishbox\Ozon;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Log\Log;
use Joomla\Database\DatabaseDriver;
use Joomla\Registry\Registry;
use JSFactory;
use ProductPriceImportException;
use stdClass;
use WishBox\JShopping\Model\Base;
use Wishbox\Marketplace\Ozon\ApiClient;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * @property DatabaseDriver $db
 * @property Registry       $addonParams
 * @property string         $errorCategory
 *
 * @since 1.0.0
 */
class ProductpricesModel extends Base
{
	/**
	 * @var string $addonAlias Addon alias
	 *
	 * @since 1.0.0
	 */
	protected string $addonAlias = 'wishboxozon';

	/**
	 * Метод получает от API Ozon цены товаров и сохраняет его в базу данных
	 *
	 * @param   string         $lastId   Last id
	 * @param   int            $limit    Limit
	 * @param   stdClass|null  $filter   Filter
	 * @param   string         $updated  Updated
	 *
	 * @return object|true
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function loadToDatabase(
		string $lastId = '',
		int $limit = 1000,
		stdClass $filter = null,
		string $updated = ''
	): object
	{
		if ($limit == 0)
		{
			throw new InvalidArgumentException('limit == 0', 500);
		}

		if (empty($updated))
		{
			throw new InvalidArgumentException('updated is empty', 500);
		}

		// Получаем
		$response = $this->getProductPrices($lastId, $limit, $filter);

		if (count($response->items))
		{
			foreach ($response->items as $item)
			{
				$price = intval($item->price->price);
				$wishboxozonproductpriceTable = JSFactory::getTable('wishboxozonproductprice');
				$wishboxozonproductpriceTable->load(
					[
						'product_id' => $item->product_id, // phpcs:ignore
						'offer_id' => $item->offer_id // phpcs:ignore
					]
				);
				$wishboxozonproductpriceTable->offer_id = $item->offer_id; // phpcs:ignore
				$wishboxozonproductpriceTable->product_id = $item->product_id; // phpcs:ignore
				$wishboxozonproductpriceTable->price = $price;
				$wishboxozonproductpriceTable->updated = $updated;

				if (!$wishboxozonproductpriceTable->store())
				{
					print_r($wishboxozonproductpriceTable);
					die;

					throw new Exception('Ошибка в базе', 500);
				}
			}
		}

		return $response;
	}

	/**
	 * @param   string  $updated  Updated
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function removeNotUpdated(string $updated): void
	{
		$this->db->setQuery(
			'DELETE FROM #__jshopping_wishboxozon_product_prices WHERE updated <> ' . $this->db->quote($updated) // phpcs:ignore
		);
		$this->db->execute();
	}

	/**
	 * @return integer
	 *
	 * @since 1.0.0
	 */
	public function getCountChangedFromDatabase(): int
	{
		$productPriceRatio = $this->addonParams->get('product_price_ratio', 1);
		$productPriceRatio = str_replace(',', '.', $productPriceRatio);
		$productPriceRatio = floatval($productPriceRatio);

		$query = $this->db->getQuery(true)
			->select('COUNT(op.product_id)')
			->from('#__jshopping_wishboxozon_product_prices op')
			->leftJoin('#__jshopping_products AS p ON p.product_ean = op.offer_id')
			->where('(
							p.wishboxozon_product_price_ratio > 0 AND op.price <> p.product_price * p.wishboxozon_product_price_ratio
						)
						OR
						(
							p.wishboxozon_product_price_ratio = 0 AND op.price <> p.product_price * ' . $productPriceRatio . '
						)'
			);

		$this->db->setQuery($query);

		return $this->db->loadResult();
	}

	/**
	 * @param   integer  $page      Page
	 * @param   integer  $pageSize  Page size
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function getChangedFromDatabase(int $page, int $pageSize): array
	{
		$productPriceRatio = $this->addonParams->get('product_price_ratio', 1);
		$productPriceRatio = str_replace(',', '.', $productPriceRatio);
		$productPriceRatio = floatval($productPriceRatio);

		$query = $this->db->getQuery(true);
		$query->select('op.product_id');
		$query->select('op.offer_id');
		$query->select('(SELECT 
							IF (p.wishboxozon_product_price_ratio > 0, 
							p.product_price * p.wishboxozon_product_price_ratio, 
							p.product_price * ' . $productPriceRatio . ')
						) as price'
		);
		$query->from('#__jshopping_wishboxozon_product_prices op');
		$query->join('LEFT', '#__jshopping_products AS p ON p.product_ean = op.offer_id');
		$query->where('(
							p.wishboxozon_product_price_ratio > 0 AND op.price <> p.product_price * p.wishboxozon_product_price_ratio
						)
						OR
						(
							p.wishboxozon_product_price_ratio = 0 AND op.price <> p.product_price * ' . $productPriceRatio . '
						)'
		);
		$this->db->setQuery($query, $page * $pageSize, $pageSize);

		return $this->db->loadObjectList();
	}

	/**
	 * @param   string         $lastId  Last id
	 * @param   int            $limit   Limit
	 * @param   stdclass|null  $filter  Filter
	 *
	 * @return object|true
	 *
	 * @since 1.0.0
	 */
	public function getProductPrices(string $cursor = '', int $limit = 1000, stdclass $filter = null): object
	{
		$data = [];
		$data['cursor'] = $cursor;
		$data['filter'] = $filter;
		$data['limit'] = $limit;

		$apiClient = new ApiClient($this->addonParams->get('api_client_id'), $this->addonParams->get('api_key'));

		return $apiClient->getProductPrices($data);
	}


	/**
	 * @param   array  $data  Data
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function updatePrices(array $data): void
	{
		$apiClient = new ApiClient($this->addonParams->get('api_client_id'), $this->addonParams->get('api_key'));

		try
		{
			$apiClient->updatePrices($data);
		}
		catch (ProductPriceImportException $e)
		{
			Log::add(__CLASS__ . '::' . __METHOD__ . ' ' . $e->getMessage(), Log::ERROR, $this->errorCategory);
		}
		catch (Exception $e)
		{
			Log::add(__CLASS__ . '::' . __METHOD__ . ' ' . $e->getMessage(), Log::ERROR, $this->errorCategory);

			throw new Exception($e->getMessage(), 500);
		}
	}
}
