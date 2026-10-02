<?php
namespace Joomla\Component\Jshopping\Site\Controller;

use Joomla\CMS\Log\Log;

defined('_JEXEC') or die;


\JLoader::register('AddonWishBoxOzon', JPATH_SITE.'/components/com_jshopping/addons/wishboxozon/wishboxozon.php');
\JLoader::register('AddonWishBoxRetailCRMnew', JPATH_SITE.'/components/com_jshopping/addons/wishboxretailcrmnew/wishboxretailcrmnew.php');


/**
 *
 */
class WishboxozonretailcrmController extends \Wishbox\JShopping\Controller\Base
{
	/**
	 *
	 */
	private \AddonWishBoxOzon $addon_ozon;

	/**
	 *
	 */
	private \AddonWishBoxRetailCRMnew $addon_retailcrm;

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

		$this->addon_ozon = \AddonWishBoxOzon::getInstance();
		$this->addon_retailcrm = \AddonWishBoxRetailCrmnew::getInstance();
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
	public function packageLabel()
	{
		try
		{
			$orderId = $this->input->get('order_id', '');
			$apiorderModel = \JSFactory::getModel('order', 'Site\\Wishbox\\Retailcrm\\Api');
			$order = $apiorderModel->getOrder($orderId, 'id');
			
			$result = $this->addon_ozon->packageLabel(['posting_number' => [$order->lastName]]);
			
			header('Content-type:application/pdf');
			header('Content-Disposition:inline;filename=000.pdf');
			echo $result;
		}
		catch (Exception $e)
		{
			$message = $e->getMessage();
			Log::add(__CLASS__ .'::'. __METHOD__ .' '.$message, Log::ERROR, self::ERROR_CATEGORY);
			throw new \Exception($message);
		}

		die;
	}
}