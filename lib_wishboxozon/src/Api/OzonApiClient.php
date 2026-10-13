<?php
/**
 * @copyright   (c) 2013-2026 Nekrasov Vitaliy <nekrasov_vitaliy@list.ru>
 * @license     GNU General Public License version 2 or later;
 */

namespace WishboxOzonLibrary\Api;

use InvalidArgumentException;
use Joomla\Http\Http;
use Joomla\Http\HttpFactory;
use Joomla\Http\Response;
use JsonException;
use stdClass;
use Throwable;
use WishboxOzonLibrary\Exception\OzonApiException;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Sends authenticated JSON requests to the Ozon Seller API.
 *
 * @since 1.0.0
 */
final class OzonApiClient
{
	private const string DEFAULT_API_URL = 'https://api-seller.ozon.ru';

	private Http $http;

	private string $apiUrl;

	private string $proxyToken;

	/**
	 * @since 1.0.0
	 */
	public function __construct(
		private readonly string $clientId,
		private readonly string $apiKey,
		?Http $http = null,
		string $apiUrl = self::DEFAULT_API_URL,
		string $proxyToken = ''
	) {
		$apiUrl = trim($apiUrl);

		if (filter_var($apiUrl, FILTER_VALIDATE_URL) === false || parse_url($apiUrl, PHP_URL_SCHEME) !== 'https')
		{
			throw new InvalidArgumentException('Ozon Seller API URL must be a valid HTTPS URL.');
		}

		$this->apiUrl = rtrim($apiUrl, '/');
		$this->proxyToken = trim($proxyToken);
		$this->http = $http ?? new HttpFactory()->getHttp([], ['curl', 'stream']);
	}

	/**
	 * Send a POST request and decode its JSON object response.
	 *
	 * @param   array<string, mixed>  $data  Request payload.
	 *
	 * @throws OzonApiException When credentials, the request, or the response are invalid.
	 *
	 * @since 1.0.0
	 */
	public function call(string $endpoint, array $data = []): stdClass
	{
		$response = $this->send($endpoint, $data);
		$contentType = $this->getContentType($response->getHeader('Content-Type'));

		if ($contentType !== 'application/json')
		{
			throw new OzonApiException(
				'Ozon Seller API returned Content-Type "' . ($contentType ?: 'unknown') . '" instead of JSON.',
				$response->getStatusCode(),
				(string) $response->getBody()
			);
		}

		try
		{
			$result = json_decode((string) $response->getBody(), false, 512, JSON_THROW_ON_ERROR);
		}
		catch (JsonException $exception)
		{
			throw new OzonApiException(
				'Ozon Seller API returned invalid JSON.',
				$response->getStatusCode(),
				(string) $response->getBody(),
				$exception
			);
		}

		if (!$result instanceof stdClass)
		{
			throw new OzonApiException(
				'Ozon Seller API response must be a JSON object.',
				$response->getStatusCode(),
				(string) $response->getBody()
			);
		}

		return $result;
	}

	/**
	 * Send a POST request and return a PDF response body.
	 *
	 * @param   array<string, mixed>  $data  Request payload.
	 *
	 * @throws OzonApiException When credentials, the request, or the response are invalid.
	 *
	 * @since 1.0.0
	 */
	public function downloadPdf(string $endpoint, array $data = []): string
	{
		$response = $this->send($endpoint, $data);
		$contentType = $this->getContentType($response->getHeader('Content-Type'));

		if ($contentType !== 'application/pdf')
		{
			throw new OzonApiException(
				'Ozon Seller API returned Content-Type "' . ($contentType ?: 'unknown') . '" instead of PDF.',
				$response->getStatusCode(),
				(string) $response->getBody()
			);
		}

		return (string) $response->getBody();
	}

	/**
	 * @param   array<string, mixed>  $data  Request payload.
	 *
	 * @throws OzonApiException
	 *
	 * @since 1.0.0
	 */
	private function send(string $endpoint, array $data): Response
	{
		if (!preg_match('#^/[a-z0-9][a-z0-9/-]*$#', $endpoint))
		{
			throw new InvalidArgumentException('Invalid Ozon Seller API endpoint.');
		}

		if (trim($this->clientId) === '' || trim($this->apiKey) === '')
		{
			throw new OzonApiException('Ozon Client-Id and Api-Key must be configured.');
		}

		try
		{
			$body = json_encode(
				$data === [] ? (object) [] : $data,
				JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
			);
		}
		catch (JsonException $exception)
		{
			throw new OzonApiException('Unable to encode an Ozon Seller API request.', 0, '', $exception);
		}

		try
		{
			$headers = [
				'Client-Id'    => $this->clientId,
				'Api-Key'      => $this->apiKey,
				'Content-Type' => 'application/json',
				'Accept'       => 'application/json, application/pdf',
			];

			if ($this->proxyToken !== '')
			{
				$headers['X-Ozon-Proxy-Token'] = $this->proxyToken;
			}

			$response = $this->http->post(
				$this->apiUrl . $endpoint,
				$body,
				$headers
			);
		}
		catch (Throwable $exception)
		{
			throw new OzonApiException('Unable to send an Ozon Seller API request.', 0, '', $exception);
		}

		$statusCode = $response->getStatusCode();

		if ($statusCode < 200 || $statusCode >= 300)
		{
			$responseBody = (string) $response->getBody();

			throw new OzonApiException(
				$this->getErrorMessage($responseBody, $statusCode),
				$statusCode,
				$responseBody
			);
		}

		return $response;
	}

	/**
	 * @since 1.0.0
	 */
	private function getContentType(mixed $header): string
	{
		if (is_array($header))
		{
			$header = reset($header);
		}

		return strtolower(trim(explode(';', (string) $header, 2)[0]));
	}

	/**
	 * Build a useful exception message without exposing request credentials.
	 *
	 * @since 1.0.0
	 */
	private function getErrorMessage(string $responseBody, int $statusCode): string
	{
		try
		{
			$error = json_decode($responseBody, false, 512, JSON_THROW_ON_ERROR);
		}
		catch (JsonException)
		{
			return 'Ozon Seller API returned HTTP status ' . $statusCode . '.';
		}

		if (!$error instanceof stdClass)
		{
			return 'Ozon Seller API returned HTTP status ' . $statusCode . '.';
		}

		$code = trim((string) ($error->code ?? ''));
		$message = trim((string) ($error->message ?? $error->error ?? ''));
		$details = trim($code . ' ' . $message);

		return $details === ''
			? 'Ozon Seller API returned HTTP status ' . $statusCode . '.'
			: 'Ozon Seller API error: ' . $details;
	}
}
