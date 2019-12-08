<?

namespace BitrixNovaPoshta;

use HTTP_Request2;

require_once 'HTTP/Request2.php';

class Client
{
    const API_BASE_PATH = 'http://api.novaposhta.ua/v2.0/json/';
    const COUNT_RESPONSE_ELEMENTS = 150;

    private $responseData;
    private $apiKey;

    /**
     * Request constructor.
     * @param array $body
     */
    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    /**
     * @param $body
     * @return mixed
     */
    public function sendRequest($body)
    {
        $request = new Http_Request2(self::API_BASE_PATH);
        $request->setHeader(array('Content-Type' => 'application/json'));
        $request->setMethod(HTTP_Request2::METHOD_POST);
        $request->setBody(json_encode($body));
        try {
            $response = $request->send();
            $this->responseData = json_decode($response->getBody(), true);
        } catch (HttpException $ex) {
            echo $ex->getMessage();
        }
        return $this->responseData;
    }

    /**
     * https://devcenter.novaposhta.ua/docs/services/556d7ccaa0fe4f08e8f7ce43/operations/56248fffa0fe4f0da0550ea8
     * @param array $methodProperties
     * @return array
     */
    public function getSettlements($methodProperties = array())
    {
        $page = 1;
        $responseData = array();
        do {
            $requestData = array(
                'modelName' => 'AddressGeneral',
                'calledMethod' => 'getSettlements',
                'apiKey' => $this->apiKey
            );
            if($methodProperties['AreaRef'])
                $requestData['methodProperties']['AreaRef'] = $methodProperties['AreaRef'];
            if($methodProperties['Ref'])
                $requestData['methodProperties']['Ref'] = $methodProperties['Ref'];
            if($methodProperties['RegionRef'])
                $requestData['methodProperties']['RegionRef'] = $methodProperties['RegionRef'];
            if($methodProperties['Page'])
                $requestData['methodProperties']['Page'] = $methodProperties['Page'];
            else
                $requestData['methodProperties']['Page'] = $page;
            $response = $this->sendRequest($requestData);
            if($methodProperties['Page'])
                $totalPagesCount = 1;
            else
                $totalPagesCount = ceil($response['info']['totalCount'] / self::COUNT_RESPONSE_ELEMENTS);
            $responseData = array_merge($responseData, $response['data']);
            $page++;
        } while ($page <= $totalPagesCount);
        return $responseData;
    }

    /**
     * https://devcenter.novaposhta.ua/docs/services/556d7ccaa0fe4f08e8f7ce43/operations/556d885da0fe4f08e8f7ce46
     * @param array $methodProperties
     * @return array
     */
    public function getCities($methodProperties = array())
    {
        $requestData = array(
            'modelName' => 'Address',
            'calledMethod' => 'getCities',
            'apiKey' => $this->apiKey
        );
        if ($methodProperties['Ref'])
            $requestData['methodProperties']['Ref'] = $methodProperties['Ref'];
        if ($methodProperties['FindByString'])
            $requestData['methodProperties']['FindByString'] = $methodProperties['FindByString'];
        if ($methodProperties['Page'])
            $requestData['methodProperties']['Page'] = $methodProperties['Page'];
        $response = $this->sendRequest($requestData);
        $responseData = $response['data'];
        return $responseData;
    }

    /**
     * https://devcenter.novaposhta.ua/docs/services/556d7ccaa0fe4f08e8f7ce43/operations/556d9130a0fe4f08e8f7ce48
     * @return array
     */
    public function getAreas()
    {
        $requestData = array(
            'modelName' => 'Address',
            'calledMethod' => 'getAreas',
            'apiKey' => $this->apiKey
        );
        $response = $this->sendRequest($requestData);
        $responseData = $response['data'];
        return $responseData;
    }

    /**
     * https://devcenter.novaposhta.ua/docs/services/556d7ccaa0fe4f08e8f7ce43/operations/556d8211a0fe4f08e8f7ce45
     * @param array $methodProperties
     * @return mixed
     */
    public function getWarehouses($methodProperties = array()){
        $page = 1;
        $defaultPageLimit = 500;
        $responseData = array();
        do {
            $requestData = array(
                'modelName' => 'AddressGeneral',
                'calledMethod' => 'getWarehouses',
                'apiKey' => $this->apiKey
            );
            if ($methodProperties['CityName'])
                $requestData['methodProperties']['CityName'] = $methodProperties['CityName'];
            if ($methodProperties['CityRef'])
                $requestData['methodProperties']['CityRef'] = $methodProperties['CityRef'];
            if ($methodProperties['Language'])
                $requestData['methodProperties']['Language'] = $methodProperties['Language'];

            if ($methodProperties['Page'])
                $requestData['methodProperties']['Page'] = $methodProperties['Page'];
            else
                $requestData['methodProperties']['Page'] = $page;

            if ($methodProperties['Limit'])
                $requestData['methodProperties']['Limit'] = $methodProperties['Limit'];
            else
                $requestData['methodProperties']['Limit'] = $defaultPageLimit;

            $response = $this->sendRequest($requestData);
            if($methodProperties['Page'])
                $totalPagesCount = 1;
            else
                $totalPagesCount = ceil(
                    $response['info']['totalCount'] / $requestData['methodProperties']['Limit']
                );
            $responseData = array_merge($responseData, $response['data']);
            $page++;
        } while ($page <= $totalPagesCount);
        return $responseData;
    }
}