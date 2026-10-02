<?php
namespace Joomla\Component\Jshopping\Site\Model\Wishbox\Ozon\Retailcrm;

use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Wishbox\JShopping\AddonHelper;

defined('_JEXEC') or die;

/**
 *
 */
class OrdersModel extends \Wishbox\JShopping\Model\Base
{
	/**
	 *
	 */
	public function __get($property): mixed
	{
		switch ($property)
		{
			case 'order_model':
				return JSFactory::getModel('order', 'Site\\Wishbox\\Retailcrm');

			case 'apiorder_model':
			{
				return JSFactory::getModel('order', 'Site\\Wishbox\\Retailcrm\\Api');
			}
		}
	}
	
	/**
	 *
	 */
	public function updateOrderStatesFromOzonToRetailcrm(): bool
	{
		// Получаем массив статусов для обновления
		$order_statuses_for_update = AddonHelper::getAddonParamsByAlias('wishboxretailcrm')->get('order_statuses_for_update', [], 'array');

		$orders_model = JSFactory::getModel('orders', 'Site\\Wishbox\\Retailcrm');

		$retailcrmOrderModel = JSFactory::getModel('order', 'Site\\Wishbox\\Retailcrm');

		// Получаем массив заказов из retailCRM со способом ozon и статусами для обновления
		$retailcrm_orders = $orders_model->getOrders(
														[
															'orderMethods'		=> ['ozon'],
															'extendedStatus'	=> $order_statuses_for_update
														]
													);

		$statuses = [
						'delivering'	=> 'send-out',
						'delivered'		=> 'complete',
						'cancelled'		=> 'cancelled'
					];

		// Если массив заказов из retailCRM не пустой
		if (count($retailcrm_orders))
		{

			// Проходим по массиву заказов из retailCRM
			foreach($retailcrm_orders as $retailcrm_order)
			{

				// Если у заказа из retailCRM нет внешнего id
				if (empty($retailcrm_order->externalId))
				{
					throw new \Exception('Ошибка: у заказа из retailCRM с id '.$retailcrm_order->id.' нет внешнего id', 500);
				}

				$ozonapiordersModel = JSFactory::getModel('orders', 'Site\\Wishbox\\Ozon\\Api');

				// Загружаем массив отправлений Ozon
				$postings = $ozonapiordersModel->getOrders(
					(object)[
					'since'		=> "2021-11-01T00:00:00.000Z",
					'to'		=> date('c'),
					'order_id'	=> $retailcrm_order->externalId
				]);

				// Если массив отправлений не пустой
				if (count($postings))
				{
					// Проходим по массиву отправлений
					foreach($postings as $posting)
					{
						// Если статус отправления нас интересует
						if (isset($statuses[$posting->status]))
						{
							// Устанавливаем новый статус заказу в retailCRM
							$retailcrmOrderModel->updateOrderStatus($retailcrm_order->id, $statuses[$posting->status]);
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 *
	 */
	public function exportNewOrdersFromOzonToRetailCrm(): bool
	{
		$ozonapiordersModel = JSFactory::getModel('orders', 'Site\\Wishbox\\Ozon\\Api');

		// Загружаем массив заказов за последние 5 дней
		$orders = $ozonapiordersModel->getOrders(
			(object)[
				'since'	=> date('c', time() - 1 * 60 * 60 * 24 * 5),
				'to'	=> date('c')
			]
		);

		// Если есть массив заказов и он не пустой
		if (count($orders))
		{
			$orderModel = JSFactory::getModel('order', 'Site\\Wishbox\\Retailcrm');

			// Проходим по массиву заказов
			foreach($orders as $order)
			{
				// Если в retailCRM нет заказа с таким id
				if (!$orderModel->orderExistsByExternalId($order->order_id))
				{
					// Создаём заказ
					$orderModel->createOrder(['order' => $order], 'ozon');
				}
			}
		}

		return true;
	}
}
