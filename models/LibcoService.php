<?php
/**
 * User: NaeemM
 */

require_once 'Zend/Rest/Client.php';
require_once 'Zend/Service/Exception.php';

class LibcoService {


    /**
     * Add or update a record from omeka to an external repository.
     * @param $record
     * @param $recordKey
     * @return bool
     */
    public function export($record, $recordKey){

        //$recordKey = "tempvalue"; //tbr
        /* If record key exists, it is an update operation, else a new record. */
        if(isset($recordKey)){
            //return $this->updateRecord($record['record'], $recordKey);
            return $this->updateRecord($record, $recordKey);
        }
        else{
            //return $this->addRecord($record['record']);
            return $this->addRecord($record);
        }

    }

    /**
     * Search a record in external repository.
     * @param $searchTerm
     * @param string $searchBy
     * @return mixed
     */
    public function searchRecord($searchTerm, $searchBy = "title"){
        $responseBody = $this->makeRequest('GET', null, null);

        $resBody = json_decode($responseBody);
        foreach ($resBody as $item) {
            if($item->filename === $searchTerm)
                return $item->key;
        }

    }

    /**
     * Get a specific record.
     * @param $recordKey
     * @return mixed
     */
    public function getRecord($recordKey){
        $responseBody = $this->makeRequest('GET', null, $recordKey);
        return unserialize($responseBody);
    }

    /**
     * Get a list of records.
     */
    public function getRecordsList(){
        $responseBody = $this->makeRequest('GET', null, null);
        $recordKeys = json_decode($responseBody);
    }

    /**
     * Http post request to external repository.
     * @param $record
     * @param $recordKey
     * @return bool
     */
    public function updateRecord($record, $recordKey){
        $requestBody = $this->prepareRequestBody($record);
        $recordKey = null; //tbr
        $response = $this->makeRequest('PUT', $requestBody, $recordKey);
        $response = trim($response, '"');
        return true;
    }


    /**
     * Http put request to external repository.
     * @param $record
     * @return bool
     */
    public function addRecord($record){
        $requestBody = $this->prepareRequestBody($record);
        $recordKey = null;
        $response = $this->makeRequest('POST', $requestBody, null);
        return trim($response, '"');
    }


    /**
     * Http delete request to external repository.
     * @param $recordKey
     * @return bool
     */
    public function deleteRecord($recordKey){
        $response = $this->makeRequest('DELETE', null, $recordKey);
        return true;
    }

    /**
     * Prepare and make http request.
     * @param $requestType
     * @param $requestBody
     * @param $recordKey
     * @return string|void
     * @throws Zend_Service_Exception
     */
    public function makeRequest($requestType, $requestBody, $recordKey){
        if(!in_array($requestType, array('GET', 'POST', 'PUT', 'DELETE'))){
            echo 'invalid request: '. $requestType;
            return;
        }

        $url = $this->prepareUrl($recordKey);
        if(empty($url)){
            $this->errorMessage($requestType, 'Server url not available');
            return;
        }

        $restClient = new Zend_Rest_Client();
        $httpClient = $restClient->getHttpClient();
        $httpClient->resetParameters();
        $httpClient->setUri($url);
        $httpClient->setHeaders('Content-Type', 'application/json');
        $test = get_option('libco_server_login_token');
        $httpClient->setHeaders('Authorization', get_option('libco_server_login_token'));
        $httpClient->setRawData(json_encode($requestBody));
        $response = $httpClient->request($requestType);

        /* If error, throw exception. */
        if ($response->isError()){
            $this->errorMessage($requestType,  $response);
            return;
        }

        return $response->getBody();
    }

    /**
     * Prepare url for http requests.
     * @param $recordKey
     * @return null|string
     */
    public function prepareUrl($recordKey){
        $currentUser = current_user();
        $serverUrl = get_option('libco_server_url');
        $urlPath = get_option('libco_url_path');
        if(empty($serverUrl) || empty($urlPath) || empty($currentUser))
            return null;

        //VEP data stores are like directories containing file or sub directories. We can create a data store (top level
        //directory ) with name as user id.
        $endPoint = $currentUser->id;
        if(!empty($recordKey))
            $endPoint .= "/".$recordKey;

        return get_option('libco_server_url').'/'.get_option('libco_url_path').'/'.$endPoint;
    }

    /**
     * Converts omeka record to appropriate request body in json format.
     * @param $record
     * @return string
     */
    public function prepareRequestBody($record){
        $requestBody = array(
            'data' => array(
                'name' => $record->id.'.json',
                'type' => 'text/plain',
                'content' => serialize($record)
            )
        );

        return $requestBody;
    }


    /**
     * Prepare and throw exception.
     * @param $requestType
     * @param $message
     * @throws Zend_Service_Exception
     */
    public function errorMessage($requestType, $message){
        throw new Zend_Service_Exception('An error occurred making '. $requestType .' request. Message: ' . $message);
    }

}