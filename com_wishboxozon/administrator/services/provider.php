<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

use Joomla\CMS\Dispatcher\ComponentDispatcherFactoryInterface;
use Joomla\CMS\Extension\ComponentInterface;
use Joomla\CMS\Extension\Service\Provider\ComponentDispatcherFactory;
use Joomla\CMS\Extension\Service\Provider\MVCFactory;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Component\WishboxOzon\Administrator\Extension\WishboxOzonComponent;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Registers the WishBox Ozon component services.
 *
 * @since 1.0.0
 */
return new class implements ServiceProviderInterface {
	/**
	 * Register the component in the Joomla dependency injection container.
	 *
	 * @param Container $container Joomla dependency injection container.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function register(Container $container): void
	{
		$container->registerServiceProvider(
			new MVCFactory('\\Joomla\\Component\\WishboxOzon')
		);
		$container->registerServiceProvider(
			new ComponentDispatcherFactory('\\Joomla\\Component\\WishboxOzon')
		);

		$container->set(
			ComponentInterface::class,
			static function (Container $container): ComponentInterface {
				$wishboxOzonComponent = new WishboxOzonComponent(
					$container->get(ComponentDispatcherFactoryInterface::class)
				);
				$wishboxOzonComponent->setMVCFactory(
					$container->get(MVCFactoryInterface::class)
				);

				return $wishboxOzonComponent;
			}
		);
	}
};
