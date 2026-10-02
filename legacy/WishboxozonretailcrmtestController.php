<?php
namespace Joomla\Component\Jshopping\Site\Controller;

use Joomla\CMS\Log\Log;

defined('_JEXEC') or die;

/**
 *
 */
class WishboxozonretailcrmtestController extends \Wishbox\JShopping\Controller\Base
{
	/**
	 *
	 */
	private const ERROR_CATEGORY = 'com_jshopping.controller_wishboxozonretailcrm';
	
	/**
	 * 
	 */
	public function init()
	{
		parent::init();

		Log::addLogger
		(
			['text_file' => self::ERROR_CATEGORY.'.log.php'],
			Log::ALL,
			[self::ERROR_CATEGORY]
		);
	}

	/**
	 *
	 */
	public function updateorderstatesfromozontoretailcrm()
	{
		try
		{
			$wishboxozonretailcrmordersModel = \JSFactory::getModel('orders', 'Site\\Wishbox\\Ozon\\Retailcrm');

			if (!$wishboxozonretailcrmordersModel->updateOrderStatesFromOzonToRetailcrm())
			{
				throw new \Exception('updateOrderStatesFromOzonToRetailcrm() return false', 500);
			}
		}
		catch (\Exception|\Error $e)
		{
			echo (string)$e;
		}

		die;
	}

	/**
	 *
	 */
	public function exportnewordersfromozontoretailcrm()
	{
		try
		{
			$wishboxozonretailcrmordersModel = \JSFactory::getModel('orders', 'Site\\Wishbox\\Ozon\\Retailcrm');

			if (!$wishboxozonretailcrmordersModel->exportNewOrdersFromOzonToRetailCrm())
			{
				throw new \Exception('exportNewOrdersFromOzonToRetailCrm() return false', 500);
			}
		}
		catch (\Exception|\Error $e)
		{
			echo (string)$e;
		}

		die;
	}
}