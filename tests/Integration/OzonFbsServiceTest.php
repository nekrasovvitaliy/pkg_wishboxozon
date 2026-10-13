<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace Tests\Integration;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use stdClass;
use WishboxOzonLibrary\Api\OzonApiClient;
use WishboxOzonLibrary\Service\OzonFbsService;

/**
 * Verifies access to real FBS postings through the Ozon Seller API.
 *
 * @since 1.0.0
 */
final class OzonFbsServiceTest extends TestCase
{
	/**
	 * Request a page of FBS postings without changing seller data.
	 *
	 * @throws \DateMalformedStringException
	 * @since 1.0.0
	 */
	public function testGetsFbsPostingsFromOzon(): void
	{
		$clientId = $this->requireEnvironmentVariable('OZON_CLIENT_ID');
		$apiKey = $this->requireEnvironmentVariable('OZON_API_KEY');
		$apiUrl = $this->getEnvironmentVariable('OZON_API_URL') ?: 'https://api-seller.ozon.ru';
		$proxyToken = $this->getEnvironmentVariable('OZON_PROXY_TOKEN');
		$lookbackDays = min(
			365,
			max(1, (int) ($this->getEnvironmentVariable('OZON_ORDER_LOOKBACK_DAYS') ?: 30))
		);
		$limit = min(1000, max(1, (int) ($this->getEnvironmentVariable('OZON_ORDER_LIMIT') ?: 10)));
		$now = new DateTimeImmutable('now', new DateTimeZone('UTC'));
		$ozonApiClient = new OzonApiClient(
			clientId: $clientId,
			apiKey: $apiKey,
			apiUrl: $apiUrl,
			proxyToken: $proxyToken
		);
		$ozonFbsService = new OzonFbsService($ozonApiClient);

		$response = $ozonFbsService->getPostings(
			filter: [
				'since' => $now->modify('-' . $lookbackDays . ' days')->format('Y-m-d\TH:i:s.v\Z'),
				'to'    => $now->format('Y-m-d\TH:i:s.v\Z'),
			],
			limit: $limit,
			cursor: '',
			direction: 'DESC',
			with: [
				'analytics_data' => false,
				'barcodes'       => false,
				'financial_data' => false,
				'legal_info'     => false,
			]
		);

		self::assertIsArray($response->postings ?? null, 'Ozon response must contain a postings array.');
		self::assertIsBool($response->has_next ?? null, 'Ozon response must contain has_next.');
		self::assertIsString($response->cursor ?? null, 'Ozon response must contain a cursor.');
		self::assertLessThanOrEqual($limit, count($response->postings));

		foreach ($response->postings as $posting)
		{
			self::assertInstanceOf(stdClass::class, $posting);
			self::assertNotSame('', trim((string) ($posting->posting_number ?? '')));
			self::assertIsArray($posting->products ?? null);
		}

		fwrite(
			STDOUT,
			sprintf(
				"\nOzon returned %d FBS postings; has next page: %s.\n",
				count($response->postings),
				$response->has_next ? 'yes' : 'no'
			)
		);
	}

	/**
	 * Return a required value or skip the live integration test.
	 *
	 * @since 1.0.0
	 */
	private function requireEnvironmentVariable(string $name): string
	{
		$value = trim($this->getEnvironmentVariable($name));

		if ($value === '')
		{
			self::markTestSkipped('Set ' . $name . ' in .env to run the Ozon integration test.');
		}

		return $value;
	}

	private function getEnvironmentVariable(string $name): string
	{
		return (string) ($_ENV[$name] ?? $_SERVER[$name] ?? getenv($name));
	}
}
