<?

namespace BitrixNovaPoshta;

class Warehouse
{
    private $tableName = '';
    private $createdWarehouses;
    private $updatedWarehouses;
    private $deprecatedWarehouses;

    public function __construct($tableName = 'nova_poshta')
    {
        $this-> tableName = $tableName;
    }

    public function __destruct(){
        new Log(
            array(
                "created" => $this->createdWarehouses,
                "updated" => $this->updatedWarehouses,
                "deprecated" => $this->deprecatedWarehouses
            ),
            "Warehouses"
        );
    }

    /**
     * Create table for warehouses.
     */
    public function createTable()
    {
        global $DB;
        $DB->Query("
          CREATE TABLE ".$this-> tableName." (
            Id INT NOT NULL AUTO_INCREMENT,
            SiteKey INT,
            CityId INT,
            DescriptionRu TEXT,
            Description TEXT,
            Phone VARCHAR(255),
            Longitude VARCHAR(255),
            Latitude VARCHAR(255),
            PostFinance INT(2),
            Schedule VARCHAR(255),
            Ref VARCHAR(255),
            DateUpdate datetime NOT NULL,
            TypeOfWarehouse TEXT,
            PRIMARY KEY (Id)
          )"
        );
    }

    /**
     * @param $warehouses
     */
    public function createWarehouses($warehouses = array())
    {
        global $DB;
        $settlements = Location::getSettlementsAndCitiesFromSite();
        foreach ($warehouses as $warehouse) {
            if(!$settlements[$warehouse['CityRef']]['ID'])
                continue;
            $res = $DB->Query("INSERT INTO ".$this-> tableName."(
                SiteKey,
                CityId,
                DescriptionRu,
                Description,
                Phone,
                Longitude,
                Latitude,
                PostFinance,
                Schedule,
                Ref,
                DateUpdate,
                TypeOfWarehouse
             )
             VALUES(" . $warehouse['SiteKey'] . ", " .
                "'" . $settlements[$warehouse['CityRef']]['ID'] . "', " .
                "'" . str_replace("'", "\'", $warehouse['DescriptionRu']) . "', " .
                "'" . str_replace("'", "\'", $warehouse['Description']) . "', " .
                "'" . $warehouse['Phone'] . "', " .
                "'" . $warehouse['Longitude'] . "', " .
                "'" . $warehouse['Latitude'] . "', " .
                "'" . $warehouse['PostFinance'] . "', " .
                "'" . json_encode($warehouse['Schedule']) . "', " .
                "'" . $warehouse['Ref'] . "', " .
                "'" . date("Y-m-d H:m:s") . "', " .
                "'" . $warehouse['TypeOfWarehouse'] .
                "')"
            );
            if($res) {
                $this->createdWarehouses[] =
                    ($warehouse['Description']) ? $warehouse['Description'] : $warehouse['DescriptionRu'];
            }
        }
    }

    /**
     * @param $warehouses
     */
    public function syncWarehouses($warehouses)
    {
        $newWarehouses = array();
        $settlements = Location::getSettlementsAndCitiesFromSite();
        $existingWarehouses = $this->getList();
        foreach($warehouses as  &$warehouseData){
            $warehouseData['cityId'] = $settlements[$warehouseData['CityRef']]['ID'];
        }
        $warehousesRef = array_column($warehouses, "Ref");
        $existingWarehousesRef = array_column($existingWarehouses, "Ref");
        $warehouses = array_combine($warehousesRef, $warehouses);
        $existingWarehouses = array_combine($existingWarehousesRef, $existingWarehouses);
        foreach ($warehouses as $ref => $warehouse){
            if($existingWarehouses[$ref]){
                $warehouseChanged = false;
                foreach ($existingWarehouses[$ref] as $key => $existingWarehouseData){
                    if(in_array($key, array("Id", "CityId", "Schedule", "DateUpdate"))) {
                        continue;
                    }
                    if($warehouse[$key] != $existingWarehouseData){
                        $warehouseChanged = true;
                    }
                }
                if($warehouseChanged === true){
                    $this->updateWarehouse($existingWarehouses[$ref]["Id"], $warehouse);
                }
            }
            else{
                $newWarehouses[] = $warehouse;
            }
        }
        $this->createWarehouses($newWarehouses);

        foreach ($existingWarehouses as $ref => $existingWarehouse){
            if(!$warehouses[$ref]){
                $this->deleteWarehouse($existingWarehouse['Id']);
            }
        }
    }

    /**
     * @param $id
     */
    public function deleteWarehouse($id){
        global $DB;
        $res = $DB->Query("DELETE FROM ".$this-> tableName." WHERE Id='".$id."'");
        if($res) {
            $this->deprecatedWarehouses[] = $id;
        }
    }

    /**
     * Get existing warehouses.
     * @return array
     */
    public function getList()
    {
        global $DB;
        $rows = array();
        $results = $DB->Query("SELECT * FROM ".$this-> tableName);
        while ($row = $results->Fetch()) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * @param $id
     * @param $warehouse
     */
    protected function updateWarehouse($id, $warehouse){
        global $DB;
        $warehouse['Description'] = str_replace("'", "''", $warehouse['Description']);
        $warehouse['DescriptionRu'] = str_replace("'", "''", $warehouse['DescriptionRu']);
        $res = $DB->Query("UPDATE ".$this-> tableName." SET
            SiteKey='".$warehouse['SiteKey']."',
            CityId='".$warehouse['cityId']."',
            DescriptionRu='".$warehouse['DescriptionRu']."',
            Description='".$warehouse['Description']."',
            Phone='".$warehouse['Phone']."',
            Longitude='".$warehouse['Longitude']."',
            Latitude='".$warehouse['Latitude']."',
            PostFinance='".$warehouse['PostFinance']."',
            Schedule='".json_encode($warehouse['Schedule']) ."',
            Ref='".$warehouse['Ref']."',
            DateUpdate='".date("Y-m-d H:m:s")."',
            TypeOfWarehouse='".$warehouse['TypeOfWarehouse']."'
            WHERE ID='$id'"
        );
        if($res) {
            $this->updatedWarehouses[] =
                ($warehouse['Description']) ? $warehouse['Description'] : $warehouse['DescriptionRu'];
        }
    }
}