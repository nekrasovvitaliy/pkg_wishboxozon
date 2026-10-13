<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license         GNU General Public License version 2 or later;
 */

namespace WishboxOzonLibrary\Service;

use InvalidArgumentException;
use stdClass;
use WishboxOzonLibrary\Api\OzonApiClient;
use WishboxOzonLibrary\Exception\OzonApiException;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Manages Ozon FBS postings, shipment confirmation, and package labels.
 *
 * @since        1.0.0
 *
 * @noinspection PhpUnused
 */
final readonly class OzonFbsService
{
	/**
	 * @since 1.0.0
	 */
	public function __construct(private OzonApiClient $apiClient)
	{
	}

	/**
	 * Load a page of FBS postings.
	 *
	 * @param   array<string, mixed>  $filter  Ozon posting filters.
	 * @param   array<string, bool>   $with    Optional response sections.
	 *
	 * @throws OzonApiException
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function getPostings(
		array  $filter,
		int    $limit = 1000,
		string $cursor = '',
		string $direction = 'ASC',
		array  $with = []
	): stdClass
	{
		$direction = strtoupper($direction);

		if (!in_array($direction, ['ASC', 'DESC'], true))
		{
			throw new InvalidArgumentException('Ozon posting direction must be ASC or DESC.');
		}

		return $this->apiClient->call(
			'/v4/posting/fbs/list',
			[
				'sort_dir' => strtolower($direction),
				'filter'   => (object) $filter,
				'limit'    => min(1000, max(1, $limit)),
				'cursor'   => $cursor,
				'with'     => (object) $with,
			]
		);
	}

	/**
	 * Load one FBS posting by its posting number.
	 *
	 * @param   array<string, bool>  $with  Optional response sections.
	 *
	 * @throws OzonApiException
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function getPosting(string $postingNumber, array $with = []): stdClass
	{
		$postingNumber = $this->validatePostingNumber($postingNumber);

		return $this->apiClient->call(
			'/v3/posting/fbs/get',
			[
				'posting_number' => $postingNumber,
				'with'           => (object) $with,
			]
		);
	}

	/**
	 * Package an FBS posting and move it to the awaiting-delivery state.
	 *
	 * @param   list<array<string, mixed>>  $packages  Ozon package descriptions.
	 * @param   array<string, bool>         $with      Optional response sections.
	 *
	 * @throws OzonApiException
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function ship(string $postingNumber, array $packages, array $with = []): stdClass
	{
		$postingNumber = $this->validatePostingNumber($postingNumber);

		if ($packages === [])
		{
			throw new InvalidArgumentException('An Ozon shipment must contain at least one package.');
		}

		return $this->apiClient->call(
			'/v4/posting/fbs/ship',
			[
				'posting_number' => $postingNumber,
				'packages'       => array_values($packages),
				'with'           => (object) $with,
			]
		);
	}

	/**
	 * Download a combined PDF label for one or more FBS postings.
	 *
	 * @param   list<string>  $postingNumbers  Posting numbers ready for label printing.
	 *
	 * @throws OzonApiException
	 *
	 * @since        1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function getPackageLabels(array $postingNumbers): string
	{
		if ($postingNumbers === [])
		{
			throw new InvalidArgumentException('At least one Ozon posting number is required.');
		}

		$postingNumbers = array_map($this->validatePostingNumber(...), $postingNumbers);

		return $this->apiClient->downloadPdf(
			'/v2/posting/fbs/package-label',
			['posting_number' => array_values($postingNumbers)]
		);
	}

	/**
	 * @since 1.0.0
	 */
	private function validatePostingNumber(string $postingNumber): string
	{
		$postingNumber = trim($postingNumber);

		if ($postingNumber === '')
		{
			throw new InvalidArgumentException('Ozon posting number must not be empty.');
		}

		return $postingNumber;
	}
}
