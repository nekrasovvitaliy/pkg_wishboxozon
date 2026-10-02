<?php
/**
 * @copyright   (C) 2013-2023 Nekrasov Vitaliy
 * @license     GNU General Public License version 2 or later
 */
namespace Joomla\Component\Jshopping\Site\Controller;

use Joomla\CMS\Plugin\PluginHelper;
use Joomla\Component\Jshopping\Site\Lib\JSFactory;
use Joomla\Component\Jshopping\Site\Model\Wishbox\Ozon\ProductsModel;
use Wishbox\JShopping\Controller\Base;
use function defined;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * @since 1.0.0
 *
 * @noinspection PhpUnused
 */
class WishboxozontestController extends Base
{
	/**
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function init()
	{
		parent::init();

		PluginHelper::importPlugin('jshoppingadmin');
		$obj = $this;
		$this->app->triggerEvent('onConstructJShoppingControllerWishBoxOzon', [&$obj]);
	}

	/**
	 * @return void
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function updatequantityfromsitetoozon(): void
	{
		
		/** @var ProductsModel $wishboxozonproductsModel */
		$wishboxozonproductsModel = JSFactory::getModel('products', 'Site\\Wishbox\\Ozon');

		$wishboxozonproductsModel->updateQuantityFromSiteToOzonAll();

		die;
	}

	/**
	 * @return void
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function updatepricesfromsitetoozon(): void
	{
		/** @var ProductsModel $wishboxozonproductsModel */
		$wishboxozonproductsModel = JSFactory::getModel('products', 'Site\\Wishbox\\Ozon');

		$wishboxozonproductsModel->updatePricesFromSiteToOzonAll();

		die;
	}
}
