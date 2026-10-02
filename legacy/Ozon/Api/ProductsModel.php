<?php
/**
 * @copyright   (C) 2013-2023 Nekrasov Vitaliy
 * @license     GNU General Public License version 2 or later
 */
namespace Joomla\Component\Jshopping\Site\Model\Wishbox\Ozon\Api;

use Exception;
use InvalidArgumentException;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\Registry;
use Joomla\CMS\Log\Log;
use Joomla\Component\Jshopping\Site\Table\WishboxozonproductstockTable;
use Joomla\Database\DatabaseDriver;
use JSFactory;
use stdClass;
use Wishbox\JShopping\Model\Base;
use Wishbox\Marketplace\Ozon\ApiClient;
use Wishbox\Marketplace\Ozon\Exception\ProductStockImportException;

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
class ProductsModel extends Base
{
	/**
	 * @var string $addonAlias Addon alias
	 *
	 * @since 1.0.0
	 */
	protected string $addonAlias = 'wishboxozon';

	/**
	 * Метод получает от API Ozon количество товаров и сохраняет его в базу данных
	 *
	 * @param   string   $lastId   Last id
	 * @param   integer  $limit    Limit
	 * @param   string   $updated  Updated
	 *
	 * @return object|true
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function loadQuantityToDatabase(string $lastId = '', int $limit = 1000, string $updated = ''): object
	{
		if ($limit == 0)
		{
			throw new InvalidArgumentException('limit == 0', 500);
		}

		if (empty($updated))
		{
			throw new InvalidArgumentException('updated is empty', 500);
		}

		// Получаем количество
		$response = $this->getProductStocks($lastId, $limit);

		if (count($response->items))
		{
			foreach ($response->items as $item)
			{
				$present = 0;
				$reserved = 0;
				$stock = 0;

				foreach ($item->stocks as $stock)
				{
					if ($stock->type == 'fbs')
					{
						$present = $stock->present;
						$reserved = $stock->reserved;
						$stock = intval($present) - intval($reserved);
					}
				}

				/** @var WishboxozonproductstockTable $wishboxozonproductstockTable */
				$wishboxozonproductstockTable = JSFactory::getTable('wishboxozonproductstock');

				$wishboxozonproductstockTable->load(['product_id' => $item->product_id, 'offer_id' => $item->offer_id]); // phpcs:ignore
				$wishboxozonproductstockTable->offer_id = $item->offer_id; // phpcs:ignore
				$wishboxozonproductstockTable->product_id = $item->product_id; // phpcs:ignore
				$wishboxozonproductstockTable->present = $present;
				$wishboxozonproductstockTable->reserved = $reserved;
				$wishboxozonproductstockTable->stock = $stock;
				$wishboxozonproductstockTable->updated = $updated;

				if (!$wishboxozonproductstockTable->store())
				{
					throw new Exception('Ошибка в базе', 500);
				}
			}
		}

		return $response;
	}

	/**
	 *
	 * @return integer
	 *
	 * @since 1.0.0
	 */
	public function getCountChangedQuantityFromDatabase(): int
	{
		$query = $this->db->getQuery(true)
			->select('COUNT(op.product_id)')
			->from('#__jshopping_wishboxozon_product_stocks op')
			->leftJoin('#__jshopping_products AS p ON p.product_ean = op.offer_id')
			->where('op.present - op.reserved <> p.product_quantity');
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
	public function getChangedQuantityFromDatabase(int $page, int $pageSize): array
	{
		$this->db = Factory::getContainer()->get(DatabaseDriver::class);
		$query = $this->db->getQuery(true)
			->select('op.product_id')
			->select('op.offer_id')
			->select('p.product_quantity AS stock')
			->from('#__jshopping_wishboxozon_product_stocks op')
			->leftJoin('#__jshopping_products AS p ON p.product_ean = op.offer_id')
			->where('op.present - op.reserved <> p.product_quantity');
		$this->db->setQuery($query, $page * $pageSize, $pageSize);

		return $this->db->loadObjectList();
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
	public function updateStocks(array $data): void
	{
		$apiClient = new ApiClient($this->addonParams->get('api_client_id'), $this->addonParams->get('api_key'));

		try
		{
			$apiClient->updateStocks($data);
		}
		catch (ProductStockImportException $e)
		{
			Log::add(__CLASS__ . '::' . __METHOD__ . ' ' . $e->getMessage(), Log::ERROR, $this->errorCategory);
		}
		catch (Exception $e)
		{
			Log::add(__CLASS__ . '::' . __METHOD__ . ' ' . $e->getMessage(), Log::ERROR, $this->errorCategory);

			throw $e;
		}
	}

	/**
	 * @param   string   $lastId  Last id
	 * @param   integer  $limit   Limit
	 *
	 * @return object|true
	 *
	 * @since 1.0.0
	 */
	public function getProductStocks(string $lastId = '', int $limit = 1000): object
	{
		$data = [];
		$data['filter'] = new stdClass;
		$data['last_id'] = $lastId;
		$data['limit'] = $limit;

		$apiClient = new ApiClient($this->addonParams->get('api_client_id'), $this->addonParams->get('api_key'));

		return $apiClient->getProductStocks($data);
	}
}
