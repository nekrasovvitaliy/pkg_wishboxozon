<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonLibrary\Exception;

use RuntimeException;
use Throwable;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Represents a failed Ozon Seller API request or an invalid response.
 *
 * @since 1.0.0
 */
class OzonApiException extends RuntimeException
{
	/**
	 * @since 1.0.0
	 */
	public function __construct(
		string $message,
		private readonly int $statusCode = 0,
		private readonly string $responseBody = '',
		?Throwable $previous = null
	) {
		parent::__construct($message, $statusCode, $previous);
	}

	/**
	 * Return the HTTP status received from Ozon, or zero before an HTTP response exists.
	 *
	 * @since 1.0.0
	 */
	public function getStatusCode(): int
	{
		return $this->statusCode;
	}

	/**
	 * Return the unmodified response body supplied with the error.
	 *
	 * @since 1.0.0
	 */
	public function getResponseBody(): string
	{
		return $this->responseBody;
	}
}
