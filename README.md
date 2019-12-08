# Импорт населенных пунктов Украины и отделений службы доставки «Новая Почта» для Bitrix CMS.

## Функции
- Импорт и обновление населенных пунктов Украины для местоположений интернет магазина
- Импорт и обновление отделений службы доставки

## Требования
Bitrix CMS с установленным модулем Интернет-магазина (sale).

## Установка

- `composer create-project --prefer-dist konopden/bitrix-np-import`

## Примеры

### Добавить населеные пункты с отделениями и населеные пункты с возможностью доставки.
```bash
require __DIR__ . '/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('sale');

use BitrixNovaPoshta\Client;
use BitrixNovaPoshta\Location;
use BitrixNovaPoshta\Warehouse;

$client = new Client(API_KEY);
$settlements = $client->getSettlements();
$cities = $client->getCities();
$areas = $client->getAreas();

$location = new Location(COUNTRY_ID);
$location->addSettlementsAndCities($settlements, $cities, $areas);

$warehouses = $client->getWarehouses();
$warehouse = new Warehouse();
$warehouse->createTable();
$warehouse->createWarehouses($warehouses);
```

### Добавить только населенные пункты с отделениями НП.
```bash
require __DIR__ . '/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('sale');

use BitrixNovaPoshta\Client;
use BitrixNovaPoshta\Location;
use BitrixNovaPoshta\Warehouse;

$client = new Client(API_KEY);
$settlements = $client->getSettlements();
$cities = $client->getCities();
$areas = $client->getAreas();

$location = new Location(COUNTRY_ID);
$location->addCities($cities, $areas);

$warehouses = $client->getWarehouses();
$warehouse = new Warehouse();
$warehouse->createTable();
$warehouse->createWarehouses($warehouses);
```

### Обновить населеные пункты с отделениями и населеные пункты с возможностью доставки. Обновить отделения НП.
```bash
require __DIR__ . '/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('sale');

use BitrixNovaPoshta\Client;
use BitrixNovaPoshta\Location;
use BitrixNovaPoshta\Warehouse;

$client = new Client(API_KEY);
$settlements = $client->getSettlements();
$cities = $client->getCities();
$areas = $client->getAreas();

$location = new Location(COUNTRY_ID);
$location->updateSettlementsAndCities($settlements, $cities, $areas);

$warehouses = $client->getWarehouses();
$warehouse = new Warehouse();
$warehouse->syncWarehouses($warehouses);
```

### Обновить населеные пункты с отделениями, обновить отделения.
```bash
require __DIR__ . '/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('sale');

use BitrixNovaPoshta\Client;
use BitrixNovaPoshta\Location;
use BitrixNovaPoshta\Warehouse;

$client = new Client(API_KEY);
$settlements = $client->getSettlements();
$cities = $client->getCities();
$areas = $client->getAreas();

$location = new Location(COUNTRY_ID);
$location->updateCities($cities, $areas);

$warehouses = $client->getWarehouses();
$warehouse = new Warehouse();
$warehouse->syncWarehouses($warehouses);
```

### Добавить населенные пункты.
```bash
require __DIR__ . '/vendor/autoload.php';
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('sale');

use BitrixNovaPoshta\Client;
use BitrixNovaPoshta\Location;
use BitrixNovaPoshta\Warehouse;

$client = new Client(API_KEY);
$settlements = $client->getSettlements();
$cities = $client->getCities();
$areas = $client->getAreas();

$location = new Location(COUNTRY_ID);
$location->addSettlements($settlements);
```