# WishBox Ozon

Переиспользуемая библиотека интеграции с Ozon Seller API для расширений Joomla.

Пакет устанавливает компонент настроек `com_wishboxozon`, библиотеку
`lib_wishboxozon` и регистрирует пространство имён `WishboxOzonLibrary`.
Библиотека не зависит от JoomShopping, RetailCRM или других компонентов и может
использоваться в плагинах, компонентах, модулях и консольных командах Joomla.

## Возможности

- вызов любых POST-методов Ozon Seller API;
- загрузка PDF-этикеток;
- получение списка, цен, остатков и подробной информации о товарах;
- импорт карточек и обновление цен и остатков;
- получение FBS-отправлений с курсорной пагинацией;
- получение отдельного FBS-отправления, его сборка и печать этикеток;
- отдельные исключения для HTTP/API-ошибок и отклонённых обновлений товаров;
- внедрение HTTP-клиента для тестирования.

## Требования

- PHP 8.5 или новее;
- Joomla с доступным пакетом `Joomla\Http`;
- `Client-Id` и `Api-Key` продавца Ozon.

## Установка

Соберите `pkg_wishboxozon.zip` по инструкции ниже и установите его через менеджер
расширений Joomla. После установки откройте **Компоненты → WishBox Ozon** и задайте
Client ID, API-ключ, URL API, токен прокси, ID склада FBS и валюту цен. Классы
пространства имён `WishboxOzonLibrary` загружаются автозагрузчиком Joomla.

В service provider другого расширения зарегистрируйте провайдеры только для
необходимых Ozon-сервисов:

```php
use RuntimeException;
use WishboxOzonLibrary\Service\Provider\OzonApiClientProvider;
use WishboxOzonLibrary\Service\Provider\OzonProductServiceProvider;

if (!class_exists(OzonApiClientProvider::class)
	|| !class_exists(OzonProductServiceProvider::class))
{
	throw new RuntimeException('The WishBox Ozon library must be installed.');
}

$container->registerServiceProvider(new OzonApiClientProvider());
$container->registerServiceProvider(new OzonProductServiceProvider());
```

`OzonApiClientProvider` читает подключение из `com_wishboxozon`. Для работы с FBS
вместо провайдера товаров или вместе с ним зарегистрируйте
`OzonFbsServiceProvider`. Все провайдеры регистрируют shared-экземпляры.

## Клиент Ozon Seller API

`OzonApiClient` автоматически отправляет `Client-Id`, `Api-Key`, JSON-заголовки и
проверяет HTTP-статус и формат ответа.

```php
use WishboxOzonLibrary\Api\OzonApiClient;

$ozonApiClient = new OzonApiClient(
	clientId: 'OZON_CLIENT_ID',
	apiKey: 'OZON_API_KEY'
);

$warehouses = $ozonApiClient->call('/v1/warehouse/list');
```

Для тестового сервера или HTTPS-прокси можно передать другой базовый URL четвёртым
аргументом конструктора. Третий аргумент принимает внедряемый `Joomla\Http\Http`,
а пятый — общий секрет прокси.

## Прокси Ozon Seller API

Файл `ozon-api-proxy.php` можно разместить на сервере, который имеет доступ к
`api-seller.ozon.ru`. Перед публикацией обязательно настройте в начале файла хотя
бы один способ авторизации:

- добавьте разрешённые адреса в `ALLOWED_CLIENT_IPS`; или
- задайте длинное случайное значение `PROXY_SHARED_SECRET`.

При использовании общего секрета клиент отправляет его в заголовке
`X-Ozon-Proxy-Token`:

```php
$ozonApiClient = new OzonApiClient(
	clientId: 'OZON_CLIENT_ID',
	apiKey: 'OZON_API_KEY',
	apiUrl: 'https://proxy.example.com/ozon-api-proxy.php',
	proxyToken: 'OZON_PROXY_SHARED_SECRET'
);
```

Прокси принимает адреса вида
`/ozon-api-proxy.php/v4/posting/fbs/list`, пропускает только POST-запросы к
версионированным методам Ozon и не сохраняет ключи или тела запросов.

## Товары, цены и остатки

```php
use WishboxOzonLibrary\Service\OzonProductService;

$ozonProductService = new OzonProductService($ozonApiClient);

$products = $ozonProductService->getProductList(
	filter: ['visibility' => 'ALL'],
	lastId: '',
	limit: 1000
);

$prices = $ozonProductService->getPrices(
	filter: ['offer_id' => ['SKU-001']]
);

$stocks = $ozonProductService->getStocks(
	filter: ['offer_id' => ['SKU-001']]
);
```

Обновление цены и остатка использует те же структуры, что и Ozon Seller API:

```php
$ozonProductService->updatePrices(
	[
		[
			'offer_id'     => 'SKU-001',
			'price'        => '1490.00',
			'old_price'    => '1690.00',
			'min_price'    => '1390.00',
			'currency_code' => 'RUB',
		],
	]
);

$ozonProductService->updateStocks(
	[
		[
			'offer_id'    => 'SKU-001',
			'product_id'  => 123456789,
			'stock'       => 10,
			'warehouse_id' => 987654321,
		],
	]
);
```

Если Ozon возвращает `updated: false`, сервис выбрасывает
`OzonProductUpdateException`. Из исключения можно получить `product_id` и код
ошибки Ozon.

## FBS-отправления

```php
use WishboxOzonLibrary\Service\OzonFbsService;

$ozonFbsService = new OzonFbsService($ozonApiClient);

$postings = $ozonFbsService->getPostings(
	filter: [
		'since'  => '2026-10-01T00:00:00Z',
		'to'     => '2026-10-02T23:59:59Z',
		'status' => ['awaiting_packaging'],
	],
	limit: 100,
	cursor: '',
	with: [
		'analytics_data' => true,
		'barcodes'       => true,
		'financial_data' => true,
	]
);
```

Версия `/v4/posting/fbs/list` возвращает `postings`, `has_next` и `cursor` в корне
ответа. Для следующей страницы передайте полученный `cursor` в новый вызов.

Сборка отправления:

```php
$ozonFbsService->ship(
	'12345678-0001-1',
	[
		[
			'products' => [
				[
					'product_id' => 123456789,
					'quantity'   => 1,
				],
			],
		],
	]
);
```

После успешного запроса сборки проверьте состояние через `getPosting()`: ответ
HTTP 200 сам по себе не гарантирует успешный переход статуса на стороне Ozon.

Получение PDF:

```php
$pdf = $ozonFbsService->getPackageLabels(['12345678-0001-1']);
```

## Обработка ошибок

```php
use WishboxOzonLibrary\Exception\OzonApiException;
use WishboxOzonLibrary\Exception\OzonProductUpdateException;

try
{
	$ozonProductService->updateStocks($stocks);
}
catch (OzonProductUpdateException $exception)
{
	$productId = $exception->getProductId();
	$errorCode = $exception->getErrorCode();
}
catch (OzonApiException $exception)
{
	$statusCode = $exception->getStatusCode();
	$responseBody = $exception->getResponseBody();
}
```

## Структура проекта

```text
pkg_wishboxozon/
├── com_wishboxozon/
│   └── administrator/
├── language/
├── lib_wishboxozon/
│   ├── language/
│   ├── src/
│   │   ├── Api/
│   │   ├── Exception/
│   │   └── Service/
│   └── lib_wishboxozon.xml
└── pkg_wishboxozon.xml
```

## Сборка пакета

Через Phing:

```bash
php phing-latest.phar
```

Или стандартной утилитой `zip`:

```bash
cd lib_wishboxozon
zip -r ../lib_wishboxozon.zip . -x '.DS_Store'
cd ..
cd com_wishboxozon
zip -r ../com_wishboxozon.zip . -x '.DS_Store'
cd ..
zip -r pkg_wishboxozon.zip pkg_wishboxozon.xml language lib_wishboxozon.zip com_wishboxozon.zip -x '.DS_Store'
```

Полученный `pkg_wishboxozon.zip` можно установить через менеджер расширений Joomla.

## Интеграционный тест Ozon

Установите зависимости и создайте `.env` из примера:

```bash
composer install
cp .env.example .env
```

Заполните `OZON_CLIENT_ID` и `OZON_API_KEY`, затем запустите:

```bash
composer test:integration
```

Тест выполняет только чтение: получает одну страницу FBS-отправлений за указанный
период и проверяет структуру ответа Ozon. Значения `OZON_ORDER_LOOKBACK_DAYS` и
`OZON_ORDER_LIMIT` позволяют изменить период и размер страницы.

## Лицензия

GNU General Public License version 2 или более поздняя версия.
