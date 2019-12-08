<?

namespace BitrixNovaPoshta;

use \Bitrix\Sale\Location\LocationTable;
use \Bitrix\Sale\Location\TypeTable;

class Location
{
    const VILLAGE_TYPE_ID = 6;
    const CITY_TYPE_ID = 5;
    const REGION_TYPE_ID = 4;
    const AREA_TYPE_ID = 3;
    const DEFAULT_SORT_VALUE = 10000;

    private $locationTypes = array();
    private $settlements = array();
    private $cities = array();
    private $regions = array();
    private $areas = array();
    private $createdLocations = array();
    private $updatedLocations = array();
    private $deprecatedLocations = array();
    private $areasFromSettlements = array();
    private $areasFromSite = array();
    private $countryId;

    /**
     * Cities constructor.
     * @param int $countryId
     */
    public function __construct($countryId)
    {
        $this->countryId = $countryId;
        $this->locationTypes = $this->getLocationTypes();
    }

    public function __destruct(){
        new Log(
            array(
                "created" => $this->createdLocations,
                "updated" => $this->updatedLocations,
                "deprecated" => $this->deprecatedLocations
            ),
            "Locations"
        );
    }

    /**
     * @param $settlements
     * @param $cities
     * @param $areas
     */
    public function addSettlementsAndCities($settlements, $cities, $areas){
        $this->setSettlements($settlements);
        $this->createAreasFromSettlements();
        $this->createRegionsFromSettlements();
        $this->createSettlements();
        $this->setCities($cities);
        $this->setAreas($areas);
        $this->createAreas();
        $this->createCities();
    }

    /**
     * @param $settlements
     * @param $cities
     * @param $areas
     */
    public function updateSettlementsAndCities($settlements, $cities, $areas){
        $this->setSettlements($settlements);
        $this->setCities($cities);
        $this->setAreas($areas);
        $this->syncCities();
        $this->syncSettlements();
    }

    /**
     * @param $settlements
     */
    public function addSettlements($settlements){
        $this->setSettlements($settlements);
        $this->createAreasFromSettlements();
        $this->createRegionsFromSettlements();
        $this->createSettlements();
    }

    /**
     * @param $settlements
     */
    public function updateSettlements($settlements){
        $this->setSettlements($settlements);
        $this->syncSettlements();
    }

    /**
     * @param $cities
     * @param $areas
     */
    public function addCities($cities, $areas){
        $this->setCities($cities);
        $this->setAreas($areas);
        $this->createAreas();
        $this->createCities();
    }

    /**
     * @param $cities
     * @param $areas
     */
    public function updateCities($cities, $areas){
        $this->setCities($cities);
        $this->setAreas($areas);
        $this->syncCities();
    }

    /**
     * Helper function, used in Warehouse class.
     * @return array
     */
    public static function getSettlementsAndCitiesFromSite(){
        $settlementsAndCities = self::getLocationsList(
            array(
                'TYPE_ID' => array(
                    self::CITY_TYPE_ID,
                    self::VILLAGE_TYPE_ID
                )
            ),
            array('ID', 'CODE')
        );
        return $settlementsAndCities;
    }

    /**
     * @param array $settlements
     */
    private function setSettlements(array $settlements){
        foreach ($settlements as $settlement){
            $this->settlements[$settlement['Ref']] = $settlement;
        }
    }

    private function createSettlements()
    {
        foreach ($this->settlements as $settlement) {
            if($this->regions[$settlement['Region']])
                $settlement['PARENT_ID'] = $this->regions[$settlement['Region']];
            elseif($this->areasFromSettlements[$settlement['Area']])
                $settlement['PARENT_ID'] = $this->areasFromSettlements[$settlement['Area']];
            $this->createLocation($settlement, "settlement");
        }
    }

    private function syncSettlements()
    {
        $this->setRegionsFromSite();
        $this->setAreasFromSite();
        $settlementsAvailableAnSite = self::getLocationsList(
            array(
                'PARENT_ID' => $this->countryId,
                '=CHILDREN.CHILDREN.NAME.LANGUAGE_ID' => LANGUAGE_ID
            ),
            array(
                'IDENTIFIER' => 'CHILDREN.CHILDREN.ID',
                'SETTLEMENT_NAME' => 'CHILDREN.CHILDREN.NAME.NAME',
                'SETTLEMENT_CODE' => 'CHILDREN.CHILDREN.CODE'
            ),
            "SETTLEMENT_CODE"
        );
        $newSettlements = array_diff_assoc($this->settlements, $settlementsAvailableAnSite);
        foreach ($newSettlements as $newSettlement){
            $cityData = $this->getExistingSettlementData($newSettlement['AreaDescriptionRu'], $newSettlement['DescriptionRu']);
            if(!$cityData || !$this->cities[$cityData['CODE']]) {
                if($this->regions[$newSettlement['Region']])
                    $newSettlement['PARENT_ID'] = $this->regions[$newSettlement['Region']];
                elseif($this->areasFromSite[$newSettlement['Area']])
                    $newSettlement['PARENT_ID'] = $this->areasFromSite[$newSettlement['Area']];
                $this->createLocation($newSettlement, "settlement");
            }
        }
        $deprecatedSettlements = array_diff_assoc($settlementsAvailableAnSite, $this->settlements);
        if ($this->cities) {
            $deprecatedSettlements = array_diff_assoc($deprecatedSettlements, $this->cities);
        }
        $this->deleteDeprecatedSettlements($deprecatedSettlements);
    }

    /**
     * Creates areas based on settlement information.
     */
    private function createAreasFromSettlements(){
        foreach ($this->settlements as $settlement) {
            if (!$this->areasFromSettlements[$settlement['Area']]) {
                $resLocationAreaId = $this->createLocation($settlement, "area");
                $this->areasFromSettlements[$settlement['Area']] = $resLocationAreaId;
            }
        }
    }

    /**
     * Creates regions based on settlement information.
     */
    private function createRegionsFromSettlements(){
        foreach ($this->settlements as $settlement) {
            if (!$this->regions[$settlement['Region']]) {
                $resLocationRegionId = $this->createLocation($settlement, "region");
                $this->regions[$settlement['Region']] = $resLocationRegionId;
            }
        }
    }

    private function setRegionsFromSite(){
        $regionsAnSite = self::getLocationsList(
            array(
                'ID' => $this->countryId,
            ),
            array(
                'CHILDREN.CHILDREN*', 'AREA_CODE' => 'CHILDREN.CHILDREN.CODE', 'IDENTIFIER' => 'CHILDREN.CHILDREN.ID'
            ),
            'AREA_CODE'
        );
        foreach($regionsAnSite as $code => $region){
            $this->regions[$code] = $region['IDENTIFIER'];
        }
    }

    private function setAreasFromSite(){
        $areasAnSite = self::getLocationsList(
            array(
                'ID' => $this->countryId,
            ),
            array(
                'CHILDREN*', 'AREA_CODE' => 'CHILDREN.CODE', 'IDENTIFIER' => 'CHILDREN.ID'
            ),
            'AREA_CODE'
        );
        foreach($areasAnSite as $code => $area){
            $this->areasFromSite[$code] = $area['IDENTIFIER'];
        }
    }

    /**
     * @param array $areas
     */
    private function setAreas(array $areas){
        $this->areas = $areas;
    }

    private function createAreas(){
        if(!$this->settlements){
            foreach ($this->areas as $area) {
                $area['Area'] = $area['Ref'];
                $area['AreaDescription'] = $area['Description'] . " область";
                $area['AreaDescriptionRu'] = $area['Description'] . " область";
                $this->createLocation($area, "area");
            }
        }
    }

    /**
     * @param $cities
     */
    private function setCities($cities){
        foreach ($cities as $city){
            $this->cities[$city['Ref']] = $city;
        }
    }

    private function createCities()
    {
        $areasDesc = array_column($this->areas, 'Description');
        $areasRef = array_column($this->areas, 'Ref');
        $areasForSelect = array_combine($areasRef, $areasDesc);
        if ($this->settlements) {
            foreach ($this->cities as $city) {
                $settlementData =
                    $this->getExistingSettlementData($areasForSelect[$city['Area']], $city['DescriptionRu']);
                if ($settlementData['ID']) {
                    $this->replaceSettlementCodeToCityCode($settlementData['ID'], $city['Ref'], $city['CityID']);
                } else {
                    $area = $this->getSettlement(
                        array('PARENT_ID' => $this->countryId, '%NAME.NAME' => $areasForSelect[$city['Area']]),
                        array('ID')
                    );
                    $city['PARENT_ID'] = ($area['ID']) ? $area['ID'] : $this->countryId;
                    $this->createLocation($city, "settlement");
                }
            }
        } else {
            foreach ($this->cities as $city) {
                $area = $this->getSettlement(
                    array('PARENT_ID' => $this->countryId, '%NAME.NAME' => $areasForSelect[$city['Area']]),
                    array('ID')
                );
                $city['PARENT_ID'] = ($area['ID']) ? $area['ID'] : $this->countryId;
                $this->createLocation($city, "settlement");
            }
        }
    }

    private function syncCities(){
        if ($this->settlements) {
            $filter = array(
                'PARENT_ID' => $this->countryId,
                '=CHILDREN.CHILDREN.NAME.LANGUAGE_ID' => LANGUAGE_ID
            );
            $select = array(
                'IDENTIFIER' => 'CHILDREN.CHILDREN.ID',
                'SETTLEMENT_NAME' => 'CHILDREN.CHILDREN.NAME.NAME',
                'SETTLEMENT_CODE' => 'CHILDREN.CHILDREN.CODE'
            );
        } else {
            $filter = array(
                'PARENT_ID' => $this->countryId,
                '=CHILDREN.NAME.LANGUAGE_ID' => LANGUAGE_ID
            );
            $select = array(
                'IDENTIFIER' => 'CHILDREN.ID',
                'SETTLEMENT_NAME' => 'CHILDREN.NAME',
                'SETTLEMENT_CODE' => 'CHILDREN.CODE'
            );
        }
        $areasDesc = array_column($this->areas, 'Description');
        $areasRef = array_column($this->areas, 'Ref');
        $areasForSelect = array_combine($areasRef, $areasDesc);
        $citiesAvailableAnSite = self::getLocationsList($filter, $select, 'SETTLEMENT_CODE');
        $newCities = array_diff_assoc($this->cities, $citiesAvailableAnSite);
        foreach ($newCities as $newCity){
            $cityData =
                $this->getExistingSettlementData($areasForSelect[$newCity['Area']], $newCity['DescriptionRu']);
            if ($cityData['ID'] && $cityData['CODE'] != $newCity['Ref']) {
                $this->replaceSettlementCodeToCityCode($cityData['ID'], $newCity['Ref'], $newCity['CityID']);
            } elseif($cityData['CODE'] != $newCity['Ref']) {
                $area = $this->getSettlement(
                    array('PARENT_ID' => $this->countryId, '%NAME.NAME' => $areasForSelect[$newCity['Area']]),
                    array('ID')
                );
                $newCity['PARENT_ID'] = ($area['ID']) ? $area['ID'] : $this->countryId;
                $this->createLocation($newCity, "settlement");
            }
        }
        if(!$this->settlements){
            $deprecatedCities = array_diff_assoc($citiesAvailableAnSite, $this->cities);
            $this->deleteDeprecatedSettlements($deprecatedCities);
        }
    }

    /**
     * @param $deprecatedSettlements
     */
    private function deleteDeprecatedSettlements(array $deprecatedSettlements){
        foreach ($deprecatedSettlements as $deprecatedSettlement){
            $location = self::getLocationsList(
                array('ID' => $deprecatedSettlement['IDENTIFIER']),
                array('CODE')
            );
            if($location) {
                $res = LocationTable::delete($deprecatedSettlement['IDENTIFIER']);
                if($res){
                    $this->deprecatedLocations[$deprecatedSettlement['ID']] = $deprecatedSettlement;
                }
            }
        }
    }

    /**
     * @param $filter
     * @param array $select
     * @param string $key
     * @return array
     */
    private static function getLocationsList($filter, $select = array(), $key = "CODE"){
        $locations = Array();
        $res = LocationTable::getList(array(
            'filter' => $filter,
            'select' => $select
        ));
        while ($location = $res->fetch()) {
            $locations[$location[$key]] = $location;
        }
        return $locations;
    }

    /**
     * Get location types available in sale module(/bitrix/admin/sale_location_type_list.php).
     * This is needed to determine the type of imported locations.
     * @return array('locationTypeName' => 'locationTypeId')
     */
    private function getLocationTypes(){
        $locationTypes = array();
        $resTypes = TypeTable::getList(array(
            'select' => array('ID', 'NAME_SETTLEMENT' => 'NAME.NAME'),
            'filter' => array('=NAME.LANGUAGE_ID' => 'RU')
        ));
        while($type = $resTypes->fetch())
        {
            $locationTypes[strtolower($type['NAME_SETTLEMENT'])] = $type['ID'];
        }
        return $locationTypes;
    }

    /**
     * @param array $locationData
     * @param string $type
     * @return int|false
     */
    private function createLocation(array $locationData, $type){
        $locationDataForSave = array();
        if ($type == "area") {
            $locationDataForSave = array(
                'CODE' => $locationData['Area'],
                'PARENT_ID' => $this->countryId,
                'TYPE_ID' => self::AREA_TYPE_ID,
                'NAME' => array(
                    'ru' => array(
                        'NAME' => $locationData['AreaDescriptionRu'],
                        'SHORT_NAME' => $locationData['AreaDescriptionRu']
                    ),
                    'ua' => array(
                        'NAME' => $locationData['AreaDescription'],
                        'SHORT_NAME' => $locationData['AreaDescription']
                    ),
                )
            );
        } elseif ($type == "region") {
            $locationDataForSave = array(
                'CODE' => $locationData['Region'],
                'PARENT_ID' => $this->areasFromSettlements[$locationData['Area']],
                'TYPE_ID' => self::REGION_TYPE_ID,
                'NAME' => array(
                    'ru' => array(
                        'NAME' => $locationData['RegionsDescriptionRu'],
                        'SHORT_NAME' => $locationData['RegionsDescriptionRu']
                    ),
                    'ua' => array(
                        'NAME' => $locationData['RegionsDescription'],
                        'SHORT_NAME' => $locationData['RegionsDescription']
                    ),
                )
            );
        } elseif ($type == "settlement") {
            if ($this->locationTypes[$locationData['SettlementTypeDescriptionRu']])
                $settlementType = $this->locationTypes[$locationData['SettlementTypeDescriptionRu']];
            else
                $settlementType = self::CITY_TYPE_ID;
            if ($locationData['PARENT_ID'])
                $parentId = $locationData['PARENT_ID'];
            else
                $parentId = $this->countryId;
            if (!$locationData['Ref'])
                return false;
            $locationDataForSave = array(
                'CODE' => $locationData['Ref'],
                'SORT' => ($locationData['CityID']) ? $locationData['CityID'] : self::DEFAULT_SORT_VALUE,
                'PARENT_ID' => $parentId,
                'TYPE_ID' => $settlementType,
                'LATITUDE' => $locationData['Latitude'],
                'LONGITUDE' => $locationData['Longitude'],
                'NAME' => array(
                    'ru' => array(
                        'NAME' => ($name = $locationData['DescriptionRu']) ? $name : $locationData['Description'],
                        'SHORT_NAME' => $locationData['DescriptionRu']
                    ),
                    'ua' => array(
                        'NAME' => ($name = $locationData['Description']) ? $name : $locationData['DescriptionRu'],
                        'SHORT_NAME' => $locationData['Description']
                    ),
                )
            );
        }
        $res = LocationTable::addExtended(
            $locationDataForSave,
            array(
                'RESET_LEGACY' => false
            )
        );
        try {
            if (!$res->isSuccess()) {
                foreach ($res->getErrorMessages() as $error){
                    throw new \Exception($error);
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage()."<br>";
        }
        $id = $res->getId();
        if($id){
            $this->createdLocations[$id]
                = ($name = $locationDataForSave['NAME']['ua']['NAME']) ? $name : $locationDataForSave['NAME']['ru']['NAME'];
            return $id;
        }
        else{
            return false;
        }
    }

    /**
     * @param $settlementId
     * @param $code
     * @param $sort
     */
    private function replaceSettlementCodeToCityCode($settlementId, $code, $sort = self::DEFAULT_SORT_VALUE){
        $res = LocationTable::update($settlementId, array('CODE' => $code, 'SORT' => $sort));
        try {
            if (!$res->isSuccess()) {
                foreach ($res->getErrorMessages() as $error){
                    throw new \Exception($error);
                }
            }
        } catch (\Exception $e) {
            echo $e->getMessage()."<br>";
        }
        if($id = $res->getId()){
            $this->updatedLocations[$id] = $code;
        }
    }

    /**
     * @param $filter
     * @param array $select
     * @return array|false
     */
    private function getSettlement($filter, $select = array()){
        $select = array_merge($select, array('CODE'));
        $res = LocationTable::getList(array(
            'filter' => $filter,
            'select' => $select
        ));
        if ($settlement = $res->fetch()) {
            return $settlement;
        }
        else{
            return false;
        }
    }

    /**
     * @param $areaName
     * @param $settlementName
     * @return array|false
     */
    private function getExistingSettlementData($areaName, $settlementName)
    {
        $settlementData = false;
        $area = $this->getSettlement(
            array('PARENT_ID' => $this->countryId, '%NAME.NAME' => $areaName),
            array('ID', 'CODE')
        );
        $regions = self::getLocationsList(
            array('PARENT_ID' => $area['ID']),
            array('ID', 'NAME', 'CODE')
        );
        foreach ($regions as $region) {
            $res = self::getSettlement(
                array('PARENT_ID' => $region['ID'], 'NAME.NAME' => $settlementName),
                array('ID', 'CODE')
            );
            if ($res['ID']) {
                $settlementData = $res;
            }
        }
        return $settlementData;
    }
}