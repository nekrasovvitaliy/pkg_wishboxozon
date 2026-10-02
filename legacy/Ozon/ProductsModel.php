<?php
/**
 * @copyright   (C) 2013-2023 Nekrasov Vitaliy
 * @license     GNU General Public License version 2 or later
 */
namespace Joomla\Component\Jshopping\Site\Model\Wishbox\Ozon;

use Exception;
use Joomla\Registry\Registry;
use Joomla\Component\Jshopping\Site\Helper\Helper as JSHelper;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\Component\Jshopping\Site\Table\ProductTable;
use Joomla\Database\DatabaseDriver;
use Wishbox\JShopping\Model\Base;
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
class ProductsModel extends Base
{
	/**
	 * @var string $addonAlias Addon alias
	 *
	 * @since 1.0.0
	 */
	protected string $addonAlias = 'wishboxozon';

	/**
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function getOzonProductIds(): array
	{
		$query = $this->db->getQuery(true)
			->select('wishboxozon_id')
			->from('#__jshopping_products')
			->where('wishboxozon_id > 0');
		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	/**
	 * Метод обновляет id товаров в Ozon
	 *
	 * @param   integer  $page      Page
	 * @param   integer  $pageSize  Page size
	 *
	 * @return integer
	 *
	 * @since 1.0.0
	 */
	public function updateOzonProductIds(int $page = 0, int $pageSize = 1000): int
	{
		$data = [];
		$data['page'] = 0;
		$data['page_size'] = 1000;

		$apiClient = new ApiClient($this->addonParams->get('api_client_id'), $this->addonParams->get('api_key'));

		// Получаем объект с общим количеством и массивом товаров
		$result = $apiClient->getProductList($data);

		foreach ($result->items as $item)
		{
			/** @var ProductTable $productTable */
			$productTable = JSFactory::getTable('product');

			$productTable->load(['product_ean' => $item->offer_id]); // phpcs:ignore
			$productTable->wishboxozon_id = $item->product_id; // phpcs:ignore
			$productTable->store();
		}

		return $result->total;
	}

	/**
	 * @return boolean
	 *
	 * @since 1.0.0
	 */
	public function updateQuantityFromSiteToOzonAll(): bool
	{
		// Получаем идентификатор последнего значения на странице
		$lastId = '';

		// Получаем количество элементов на странице
		$limit = 1000;

		// Получаем дату обновления
		$updated = JSHelper::getJSDate();

		/** @var Api\ProductsModel $ozonApiProductsModel */
		$ozonApiProductsModel = JSFactory::getModel('products', 'Site\\Wishbox\\Ozon\\Api');

		while (true)
		{
			// Загружаем количество в базу данных
			$response = $ozonApiProductsModel->loadQuantityToDatabase($lastId, $limit, $updated);

			if (count($response->items) == $limit)
			{
				$lastId = $response->last_id; // phpcs:ignore
			}
			else
			{
				break;
			}
		}

		$this->db->setQuery('DELETE FROM #__jshopping_wishboxozon_product_stocks WHERE updated <> ' . $this->db->quote($updated));
		$this->db->execute();

		// Получаем номер страницы
		$page = 0;

		// Получаем количество элементов на странице
		$pageSize = 1000;

		$total = $ozonApiProductsModel->getCountChangedQuantityFromDatabase();

		if ($total)
		{
			while (true)
			{
				$stocks = $ozonApiProductsModel->getChangedQuantityFromDatabase($page, $pageSize);
				$data = [];
				$data['stocks'] = [];

				foreach ($stocks as $stock)
				{
					$data['stocks'][] = [
						'offer_id'		=> $stock->offer_id, // phpcs:ignore
						'product_id'	=> (int) $stock->product_id, // phpcs:ignore
						'stock'			=> (int) $stock->stock,
						'warehouse_id'	=> 19627965447000,
					];
				}

				$ozonApiProductsModel->updateStocks($data);

				if (($page + 1) * $pageSize < $total)
				{
					$page++;
				}
				else
				{
					break;
				}
			}
		}

		return true;
	}

	/**
	 * Метод обновляет изменившиеся цены с сайта в Озон
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function updatePricesFromSiteToOzonAll(): void
	{
		// Указатель для выборки следующих данных
		$cursor = '';

		// Получаем количество элементов на странице
		$limit = 1000;

		// Получаем дату обновления
		$updated = JSHelper::getJSDate();

		// Получаем массив идентификаторов товаров в Ozon
		$ozonproductIds = $this->getOzonProductIds();

		/** @var ProductpricesModel $wishboxozonproductpricesModel */
		$wishboxozonproductpricesModel = JSFactory::getModel('productprices', 'Site\\Wishbox\\Ozon');

		while (true)
		{
			// Загружаем количество в базу данных
			$response = $wishboxozonproductpricesModel->loadToDatabase(
				$cursor,
				$limit,
				(object) [
					'product_id' => $ozonproductIds
				],
				$updated
			);

			if (count($response->items) == $limit)
			{
				$cursor = $response->cursor; // phpcs:ignore
			}
			else
			{
				break;
			}
		}

		// Получаем номер страницы
		$page = 0;

		// Получаем количество элементов на странице
		$pageSize = 1000;

		$wishboxozonproductpricesModel->removeNotUpdated($updated);
		$total = $wishboxozonproductpricesModel->getCountChangedFromDatabase();

		if ($total)
		{
			while (true)
			{
				$prices = $wishboxozonproductpricesModel->getChangedFromDatabase($page, $pageSize);
				$data = [];
				$data['prices'] = [];

				foreach ($prices as $price)
				{
					$data['prices'][] = [
						'offer_id' => $price->offer_id, // phpcs:ignore
						'product_id' => (int) $price->product_id, // phpcs:ignore
						'price' => (string) $price->price,
					];
				}

				$wishboxozonproductpricesModel->updatePrices($data);

				if (($page + 1) * $pageSize < $total)
				{
					$page++;
				}
				else
				{
					break;
				}
			}
		}
	}
}
