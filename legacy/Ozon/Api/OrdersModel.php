<?php
/**
 * @copyright   (C) 2013-2023 Nekrasov Vitaliy
 * @license     GNU General Public License version 2 or later
 */
namespace Joomla\Component\Jshopping\Site\Model\Wishbox\Ozon\Api;

use Joomla\Registry\Registry;
use stdClass;
use Wishbox\JShopping\Model\Base;
use Wishbox\Marketplace\Ozon\ApiClient;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * @property Registry $addonParams
 *
 * @since 1.0.0
 */
class OrdersModel extends Base
{
	/**
	 * @var string $addonAlias Addon alias
	 *
	 * @since 1.0.0
	 */
	protected string $addonAlias = 'wishboxozon';

	/**
	 * @param   stdClass|null  $filter  Filter
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function getOrders(stdClass $filter = null): array
	{
		$data = [];
		$data['dir'] = 'asc';
		$data['filter'] = $filter;
		$data['limit'] = 999;
		$data['with'] = (object) [
			'barcodes' => true,
			'nalytics_data' => true,
			'financial_data' => true
		];

		$apiClient = new ApiClient($this->addonParams->get('api_client_id'), $this->addonParams->get('api_key'));

		return $apiClient->getOrders($data);
	}
}
