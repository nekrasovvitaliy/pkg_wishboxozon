<?php
/**
 * @copyright 2023 Nekrasov Vitaliy
 * @license GNU General Public License version 2 or later
 */
namespace Wishbox\Marketplace\Ozon;

use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;
use Joomla\CMS\Http\HttpFactory;
use Joomla\CMS\Log\Log;
use Joomla\Http\Response;
use stdClass;
use Wishbox\Marketplace\Ozon\Exception\ProductPriceImportException;
use Wishbox\Marketplace\Ozon\Exception\ProductStockImportException;
use function defined;

// phpcs:disable PSR1.Files.SideEffects
defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * @since 1.0.0
 */
class ApiClient
{
	/**
	 * @var integer $clientId Client id
	 *
	 * @since 1.0.0
	 */
	private int $clientId;

	/**
	 * @var string $apiKey API key
	 *
	 * @since 1.0.0
	 */
	private string $apiKey;

	/**
	 * @since 1.0.0
	 */
	private const ERROR_CATEGORY = 'com_jshopping.addon_wishboxozon.ozon';

	/**
	 * @param   integer  $clientId  Client id
	 * @param   string   $apiKey    API key
	 *
	 * @since 1.0.0
	 */
	public function __construct(int $clientId, string $apiKey)
	{
		$this->clientId = $clientId;
		$this->apiKey = $apiKey;
		Log::addLogger(
			['text_file' => self::ERROR_CATEGORY . '.log.php'],
			Log::ALL,
			[self::ERROR_CATEGORY]
		);
	}

	/**
	 * @param   array $data  Data
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function updateStocks(array $data): void
	{
		$result = $this->sendRequest('/v2/products/stocks', $data);
		
		

		if (!count($result->result))
		{
			throw new Exception('No count result', 500);
		}

		foreach ($result->result as $item)
		{
			if ($item->updated === false)
			{
				foreach ($item->errors as $error)
				{
					$exception = new ProductStockImportException($error->code . ' ' . json_encode($item), 500);
					$exception->ozonProductId = $item->product_id; // phpcs:ignore

					throw $exception;
				}
			}
		}
	}

	/**
	 * @param   array  $data  Data
	 *
	 * @throws Exception
	 *
	 * @return void
	 *
	 * @since 1.0.0
	 */
	public function updatePrices(array $data): void
	{
		$result = $this->sendRequest('/v1/product/import/prices', $data);

		if (!count($result->result))
		{
			throw new Exception('No count result', 500);
		}

		foreach ($result->result as $item)
		{
			if ($item->updated === false)
			{
				foreach ($item->errors as $error)
				{
					$exception = new ProductPriceImportException($error->code . ' ' . json_encode($item), 500);
					$exception->ozonProductId = $item->product_id; // phpcs:ignore
					throw $exception;
				}
			}
		}
	}

	/**
	 * @param   array  $data  Data
	 *
	 * @return stdClass|null
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function getProductStocks(array $data): ?stdClass
	{
		if ($result = $this->sendRequest('/v4/product/info/stocks', $data))
		{
			if (is_object($result))
			{
				return $result;
			}
		}

		return null;
	}

	/**
	 * @param   array $data  Data
	 *
	 * @return stdClass|null
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function getProductPrices(array $data): ?stdClass
	{
		if ($result = $this->sendRequest('/v5/product/info/prices', $data))
		{
			if (is_object($result))
			{
				return $result;
			}
		}

		return null;
	}

	/**
	 * @param   array  $data  Data
	 *
	 * @return void
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	#[NoReturn]
	public function getProductInfo(array $data): void
	{
		$result = $this->sendRequest('/v2/product/info', $data);
		print_r($result);
		die;
	}

	/**
	 * @param   array  $data  Data
	 *
	 * @return stdClass
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function getProductList(array $data): stdClass
	{
		$result = $this->sendRequest('/v2/product/list', $data);

		return $result->result;
	}

	/**
	 * @param   array  $data  Data
	 *
	 * @return boolean
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 *
	 * @noinspection PhpUnused
	 */
	public function importProducts(array $data): bool
	{
		if ($this->sendRequest('/v2/product/import', $data))
		{
			return true;
		}

		return true;
	}

	/**
	 * @param   array  $data  Data
	 *
	 * @return stdClass|null
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function getPosting(array $data): ?stdClass
	{
		if ($result = $this->sendRequest('/v3/posting/fbs/get', $data))
		{
			return $result->result;
		}

		return null;
	}

	/**
	 * @param   array  $data  Data
	 *
	 * @return array|boolean
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function getOrders(array $data): array|bool
	{
		if ($result = $this->sendRequest('/v3/posting/fbs/list', $data))
		{
			return $result->result->postings;
		}

		return false;
	}

	/**
	 * @param   array  $data  Data
	 *
	 * @return stdClass|null
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function ship(array $data): ?stdClass
	{

		$result = $this->sendRequest('/v4/posting/fbs/ship', $data);

		if ($result)
		{
			return $result->result;
		}

		return null;
	}

	/**
	 * @param   array  $data  Data
	 *
	 * @return stdClass|string|null
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	public function packageLabel(array $data): stdClass|string|null
	{
		if (!count($data))
		{
			throw new InvalidArgumentException(
				__CLASS__ . '::' . __METHOD__ . ' Argument array $data is empty',
				500
			);
		}

		if (!count($data))
		{
			foreach ($data as $value)
			{
				$value = trim($value);

				if (empty($value))
				{
					throw new InvalidArgumentException(
						__CLASS__ . '::' . __METHOD__ . ' Argument array $data containts empty element',
						500
					);
				}
			}
		}

		return $this->sendRequest('/v2/posting/fbs/package-label', $data);
	}

	/**
	 * @param   string  $url   URL
	 * @param   array   $data  Data
	 *
	 * @return mixed
	 *
	 * @throws Exception
	 *
	 * @since 1.0.0
	 */
	private function sendRequest(string $url, array $data): mixed
	{
		$response = $this->getResponse($url, $data);
		$result = null;
		$message = 'HTTP: ' . $response->code
					. ' HEADERS: ' . json_encode($response->headers, true)
					. ' Response: ' . $response->body
					. ' URL: ' . $url . ' POST: ' . json_encode($data);
		Log::add($message, Log::INFO, self::ERROR_CATEGORY);
		$headers = [];

		foreach ($response->headers as $name => $value)
		{
			$headers[mb_strtolower($name)] = $value;
		}

		if (is_array($headers['content-type']) && count($headers['content-type']))
		{
			$contentType = $headers['content-type'][0];

			switch ($contentType)
			{
				case 'application/json' :
					$result = json_decode($response->body);

					if ($result === null)
					{
						throw new Exception('Ozon API return not JSON response: ' . $response->body);
					}

					if ($response->code != 200)
					{
						throw new Exception(print_r($data, true) . $response->body, 500);
					}

					break;
				case 'application/pdf' :
					$result = $response->body;
					break;
				default :
					throw new Exception('Ozon API return header with unknown "Content-Type" ' . $message, 500);
			}
		}
		else
		{
			// иначе если в заголовках нет "Content-Type"
			throw new Exception('Ozon API return headers without "Content-Type" ' . $message, 500);
		}

		return $result;
	}

	/**
	 * @param   string  $url     URL
	 * @param   array   $data    Data
	 * @param   string  $method  Method
	 *
	 * @return Response
	 *
	 * @since 1.0.0
	 */
	private function getResponse(string $url, array $data, string $method = 'post'): Response
	{
		$http = HttpFactory::getHttp([], ['curl', 'stream']);
		$curlOptions = [];
		$curlOptions[CURLOPT_SSL_VERIFYPEER] = false;
		$curlOptions[CURLOPT_TIMEOUT] = 5;
		$http->setOption('transport.curl', $curlOptions);
		$post = json_encode($data);
		$headers = [
			'Content-Type' => 'application/json',
			'Client-Id' => $this->clientId,
			'Api-Key' => $this->apiKey
		];

		return $http->post('https://api-seller.ozon.ru' . $url, $post, $headers);
	}
}
