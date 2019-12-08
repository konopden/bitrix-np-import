<?

use PHPUnit\Framework\TestCase;
use  BitrixNovaPoshta\Client;

class ClientTest extends TestCase
{
    public function testGetSettlements()
    {
        $client = new Client($_ENV['API_KEY']);
        $settlements = $client->getSettlements(array('Page' => 1));
        $this->assertIsArray($settlements);
        $this->assertArrayHasKey("Ref", $settlements[0]);
    }

    public function testGetCities()
    {
        $client = new Client($_ENV['API_KEY']);
        $settlements = $client->getCities();
        $this->assertIsArray($settlements);
        $this->assertArrayHasKey("Ref", $settlements[0]);
    }

    public function testGetAreas()
    {
        $client = new Client($_ENV['API_KEY']);
        $areas = $client->getAreas();
        $this->assertIsArray($areas);
        $this->assertArrayHasKey("Ref", $areas[0]);
    }

    public function testGetWarehouses()
    {
        $client = new Client($_ENV['API_KEY']);
        $warehouses = $client->getWarehouses();
        $this->assertIsArray($warehouses);
        $this->assertArrayHasKey("Ref", $warehouses[0]);
    }
}
