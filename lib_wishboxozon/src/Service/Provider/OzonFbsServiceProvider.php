<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license         GNU General Public License version 2 or later;
 */

namespace WishboxOzonLibrary\Service\Provider;

use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use WishboxOzonLibrary\Api\OzonApiClient;
use WishboxOzonLibrary\Service\OzonFbsService;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Registers the Ozon FBS service.
 *
 * @since        1.0.0
 *
 * @noinspection PhpUnused
 */
final class OzonFbsServiceProvider implements ServiceProviderInterface
{
	/**
	 * Register the Ozon FBS service.
	 *
	 * @param   Container  $container  Dependency injection container.
	 *
	 * @return void
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function register(Container $container): void
	{
		$container->share(
			OzonFbsService::class,
			static fn(Container $container): OzonFbsService => new OzonFbsService(
				$container->get(OzonApiClient::class)
			)
		);
	}
}
