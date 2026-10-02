<?php
/**
 * @copyright   (C) 2013-2023 Nekrasov Vitaliy
 * @license     GNU General Public License version 2 or later
 */
namespace Joomla\Component\Jshopping\Site\Model\Wishbox\Ozon\Api;

use Exception;
use Joomla\Registry\Registry;
use JSFactory;
use RetailCrm\Api\Exception\Api\AccountDoesNotExistException;
use RetailCrm\Api\Exception\Api\ApiErrorException;
use RetailCrm\Api\Exception\Api\MissingCredentialsException;
use RetailCrm\Api\Exception\Api\MissingParameterException;
use RetailCrm\Api\Exception\Api\ValidationException;
use RetailCrm\Api\Exception\Client\BuilderException;
use RetailCrm\Api\Exception\Client\HandlerException;
use RetailCrm\Api\Exception\Client\HttpClientException;
use RetailCrm\Api\Interfaces\ApiExceptionInterface;
use RetailCrm\Api\Interfaces\ClientExceptionInterface;
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
class OrderModel extends Base
{
	/**
	 * @var string $addonAlias Addon alias
	 *
	 * @since 1.0.0
	 */
	protected string $addonAlias = 'wishboxozon';

	/**
	 * @param   integer  $ozonorderId  Ozon order id
	 *
	 * @return boolean
	 *
	 * @throws AccountDoesNotExistException
	 * @throws ApiErrorException
	 * @throws MissingCredentialsException
	 * @throws MissingParameterException
	 * @throws ValidationException
	 * @throws BuilderException
	 * @throws HandlerException
	 * @throws HttpClientException
	 * @throws ApiExceptionInterface
	 * @throws ClientExceptionInterface
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function ship(int $ozonorderId): bool
	{
		if ($ozonorderId <= 0)
		{
			throw new Exception('ozonorderId param must be more than zero', 500);
		}

		/** @var \Joomla\Component\Jshopping\Site\Model\Wishbox\Retailcrm\Api\OrderModel $apiorderModel */
		$apiorderModel = JSFactory::getModel('order', 'Site\\Wishbox\\Retailcrm\\Api');

		$order = $apiorderModel->getOrder($ozonorderId);

		$apiClient = new ApiClient($this->addonParams->get('api_client_id'), $this->addonParams->get('api_key'));

		// Получаем отправление Озон
		$posting = $apiClient->getPosting(['posting_number' => $order->lastName]);

		// Массив данных для запроса к API Озона
		$data = [];
		$data['packages'] = [];
		$data['packages'][0] = [];
		$data['packages'][0]['products'] = [];

		foreach ($posting->products as $product)
		{
			$data['packages'][0]['products'][] = [
				'product_id'	=> $product->sku,
				'quantity'		=> $product->quantity
			];
		}

		$data['posting_number'] = $order->lastName;
		$result = $apiClient->ship($data);

		return true;
	}
}
