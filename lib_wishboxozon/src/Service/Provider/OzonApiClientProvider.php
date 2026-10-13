<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonLibrary\Service\Provider;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\DI\Container;
use Joomla\DI\ServiceProviderInterface;
use RuntimeException;
use WishboxOzonLibrary\Api\OzonApiClient;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Registers the Ozon Seller API client.
 *
 * @since 1.0.0
 *
 * @noinspection PhpUnused
 */
final class OzonApiClientProvider implements ServiceProviderInterface
{
	/**
	 * Register the configured Ozon API client.
	 *
	 * @param   Container  $container  Dependency injection container.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function register(Container $container): void
	{
		if (!ComponentHelper::isEnabled('com_wishboxozon'))
		{
			throw new RuntimeException(
				'The Wishbox Ozon component must be installed and enabled before using the Ozon API client.'
			);
		}

		$container->share(
			OzonApiClient::class,
			static function (): OzonApiClient {
				$wishboxOzonComponentParams = ComponentHelper::getParams('com_wishboxozon');

				return new OzonApiClient(
					clientId: (string) $wishboxOzonComponentParams->get('ozon_client_id', ''),
					apiKey: (string) $wishboxOzonComponentParams->get('ozon_api_key', ''),
					apiUrl: (string) $wishboxOzonComponentParams->get(
						'ozon_api_url',
						'https://api-seller.ozon.ru'
					),
					proxyToken: (string) $wishboxOzonComponentParams->get('ozon_proxy_token', '')
				);
			}
		);
	}
}
