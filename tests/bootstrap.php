<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

define('_JEXEC', 1);

require dirname(__DIR__) . '/vendor/autoload.php';
require dirname(__DIR__, 2) . '/pkg_wishbox_j4/lib_wishbox/src/Dto/OrderImport/MarketplaceOrderDelivery.php';
require dirname(__DIR__, 2) . '/pkg_wishbox_j4/lib_wishbox/src/Dto/OrderImport/MarketplaceOrderItem.php';
require dirname(__DIR__, 2) . '/pkg_wishbox_j4/lib_wishbox/src/Dto/OrderImport/MarketplaceOrder.php';
require dirname(__DIR__, 2) . '/pkg_wishbox_j4/lib_wishbox/src/Dto/OrderImport/OrderImportResult.php';

$environmentFileDirectory = dirname(__DIR__);

if (is_file($environmentFileDirectory . '/.env'))
{
	Dotenv\Dotenv::createImmutable($environmentFileDirectory)
		->safeLoad();
}
