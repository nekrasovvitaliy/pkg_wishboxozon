<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonLibrary\Service;

use UnexpectedValueException;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Provides Ozon FBS service injection.
 *
 * @since 1.0.0
 *
 * @noinspection PhpUnused
 */
trait OzonFbsServiceAwareTrait
{
	private ?OzonFbsService $ozonFbsService = null;

	/**
	 * Get the Ozon FBS service.
	 *
	 * @return OzonFbsService Ozon FBS service.
	 *
	 * @throws UnexpectedValueException When the service has not been injected.
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function getOzonFbsService(): OzonFbsService
	{
		if ($this->ozonFbsService instanceof OzonFbsService)
		{
			return $this->ozonFbsService;
		}

		throw new UnexpectedValueException(
			'OzonFbsService not set in ' . static::class . '.'
		);
	}

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
	public function setOzonFbsService(OzonFbsService $ozonFbsService): void
	{
		$this->ozonFbsService = $ozonFbsService;
	}
}
