<?
$_SERVER['DOCUMENT_ROOT'] = $_ENV['DOCUMENT_ROOT'];
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
CModule::IncludeModule('sale');

use PHPUnit\Framework\TestCase;
use  BitrixNovaPoshta\Location;
use  BitrixNovaPoshta\Client;


class LocationTest extends TestCase
{
    public function testAddSettlementsAndCitiesWithWarehouses()
    {
        $client = new Client($_ENV['API_KEY']);
        $settlements = $client->getSettlements(array('Page' => 1));
        $settlements = array_slice($settlements, 0, 5);
        $cities = $client->getCities(array('FindByString' => "Абазівка"));
        $areas = $client->getAreas();
        $location = new Location($_ENV['COUNTRY_ID']);
        $location->setSettlements($settlements);
        $location->createAreasFromSettlements();
        $location->createRegionsFromSettlements();
        $location->createSettlements();
        $location->setCities($cities);
        $location->setAreas($areas);
        $location->createAreas();
        $location->createCities();

        $cityCodes = array_column($cities, 'Ref');
        $settlementCodes = array_column($settlements, 'Ref');
        $settlementsAndCities = array_merge_recursive($cityCodes, $settlementCodes);

        $citiesAvailableAnSite = $location::getLocationsList(
            array(
                'CODE' => $cityCodes
            ),
            array('ID')
        );
        $settlementsAndCitiesAvailableAnSite = $location::getLocationsList(
            array(
                'CODE' => $settlementsAndCities
            ),
            array('ID')
        );
        $locationsAvailableAnSite = $location::getLocationsList(
            array(
                '!ID' => $_ENV['COUNTRY_ID']
            ),
            array('ID')
        );
        $location->deleteDeprecatedSettlements($locationsAvailableAnSite);
        $this->assertEquals(count($cities), count($citiesAvailableAnSite));
        $this->assertEquals(count($settlementsAndCities), count($settlementsAndCitiesAvailableAnSite));
    }

}
