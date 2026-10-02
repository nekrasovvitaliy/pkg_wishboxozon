<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonLibrary\Exception;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Represents an item-level error returned while updating an Ozon product.
 *
 * @since 1.0.0
 */
final class OzonProductUpdateException extends OzonApiException
{
	/**
	 * @since 1.0.0
	 */
	public function __construct(
		string $message,
		private readonly int $productId = 0,
		private readonly string $errorCode = ''
	) {
		parent::__construct($message);
	}

	/**
	 * @since 1.0.0
	 */
	public function getProductId(): int
	{
		return $this->productId;
	}

	/**
	 * @since 1.0.0
	 */
	public function getErrorCode(): string
	{
		return $this->errorCode;
	}
}
