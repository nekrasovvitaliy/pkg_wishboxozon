<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonLibrary\Service;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Defines the contract for classes that depend on the Ozon FBS service.
 *
 * @since 1.0.0
 *
 * @noinspection PhpUnused
 */
interface OzonFbsServiceAwareInterface
{
	/**
	 * Get the Ozon FBS service.
	 *
	 * @return OzonFbsService Ozon FBS service.
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function getOzonFbsService(): OzonFbsService;

	/**
	 * Set the Ozon FBS service.
	 *
	 * @param   OzonFbsService  $ozonFbsService  Ozon FBS service.
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function setOzonFbsService(OzonFbsService $ozonFbsService): void;
}
