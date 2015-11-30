<?php
/**
 * Created by PhpStorm.
 * User: NaeemM
 * Date: 25/09/2015
 * Time: 10:34
 */

class ImportRecord {

    public $db;
    public $userId;
    public $collectionName;
    public $collectionId;
    public $collectionAdded;
    public $addToExistingCollection;
    public $addToExistingCollectionId;
    public $messages = array();

    function __construct()
    {
        $this->db = get_db();
        $this->collectionAdded = false;
        $this->addToExistingCollection;
    }

    function importRecords($dataToImport){

        /* Create collection. */
        if(!empty($this->collectionName)){
            $isPublicCollection = 1;    // by default collection is public
            $isFeaturedCollection = 0;  // by default collection is not featured
            $this->addCollection($isPublicCollection, $isFeaturedCollection);
            if(!$this->collectionAdded)
                return;
        }

        $counter = 0;
        $isPublic = 1;      // by default imported records are public
        $isFeatured = 0;    // by default imported records are not featured
        $invalidRecords = 0;

        /* Add each selected record as Items in the database. */
        foreach($dataToImport as $record){
            $recordArray = unserialize(base64_decode($record));
            $elementsToAdd = $this->parseResult($recordArray);
            if(!empty($elementsToAdd)){  // is a valid record

                /* Check if record is public. */
                if(array_key_exists('isPublic', $recordArray) && !empty($recordArray['isPublic'])
                    && $recordArray['isPublic'] === "true"
                )
                    $isPublic = 1;

                $itemId = $this->addRecord($elementsToAdd, $isPublic, $isFeatured);
                if(!empty($itemId)) /* Record successfully imported. */
                    $counter++;
                else                /* It is a valid record but could not be imported. */
                    _log('Error in importing record: ' . $recordArray['id'], Zend_Log::ERR);
            }
            else
            {
                /* It is not a valid record because it does not have a Title. */
                _log('Invalid record: ' . $recordArray['id'], Zend_Log::ERR);
                $invalidRecords++;
            }
        }
        $this->messages[] = __("Number of items imported: %d", $counter);

        if($invalidRecords > 0)
            $this->messages[] = __("Invalid items: %d", $invalidRecords);
    }

    function addCollection($isPublicCollection, $isFeaturedCollection){
        $message = "";
        //Check if collection exists
        $collectionExists = $this->collectionExists($this->collectionName);
        // If collection exists and it is an add to existing collection request, proceed,
        // otherwise return with an error.
        if(!$this->addToExistingCollection && $collectionExists){
            $this->messages[] = __("Collection '%s' already exists. Please choose another name or select add to
            existing collection option.", $this->collectionName);
            return;
        }

        // Add to existing collection, collection id and name already known
        if($this->addToExistingCollection && !empty($this->addToExistingCollectionId) && !empty($this->collectionName)){
            $collectionTable = $this->db->getTable('Collection');
            $retCollection = $collectionTable->find($this->addToExistingCollectionId);
            $collection = $retCollection->id;
        }
        else
            $collection = $this->db->insert("Collection", array('public' => $isPublicCollection, 'featured' => $isFeaturedCollection, 'added' => date('Y-m-d G:i:s'), 'owner_id' => $this->userId));

        if(!empty($collection)){
            $this->collectionId = $collection;
            $elementId = $this->getElementId("Title", "Dublin Core");
            if(!empty($elementId)){
                $this->db->insert("Element_text",
                    array('record_id' => $collection, 'record_type' => 'Collection',
                        'element_id' => $elementId, 'text' => $this->collectionName, 'html' => true));

                $this->collectionAdded = true;
                if($this->addToExistingCollection)
                    $this->messages[] = __("Items added to the existing collection  '%s '.", $this->collectionName);
                else
                    $this->messages[] = __("Items added to a new collection '%s '.", $this->collectionName);
            }

        }
    }

    function collectionExists($collectionName){
        $elementId = $this->getElementId("Title", "Dublin Core");

        $collectionTable = $this->db->getTable('Collection');
        $select = $collectionTable->getSelect();
        $select->joinInner(array('s' => $this->db->ElementText),
            's.record_id = collections.id', array());
        $select->where("s.record_type = 'Collection'");
        $select->where("s.element_id = ?", $elementId);
        $select->where("s.text = ?", $collectionName);

        $collection = $collectionTable->fetchObject($select);
        if (!$collection) {
            _log("Collection does not exist", Zend_Log::NOTICE);
            return false;
        }
        else
            return true;
    }

    function addRecord($elementsToAdd, $isPublic, $isFeatured){
        $insertOptions = array('added' => date('Y-m-d G:i:s'), 'owner_id' => $this->userId, 'public' => $isPublic, 'featured' => $isFeatured);

        if(!empty($this->collectionId))
            $insertOptions['collection_id'] =$this->collectionId;

        $itemId = $this->db->insert("Item", $insertOptions);
        if(!empty($itemId)){
            $this->addElements($elementsToAdd, 'Item', $itemId);
            return $itemId;
        }

        _log(__("Error in inserting Item in the database.") , Zend_Log::ERR);
        _log(__("Insert options: %s", implode(",", $insertOptions)) , Zend_Log::ERR);
    }

    function addElements($elementsToAdd, $recordType, $recordId){
        /*  Elements are added to the Element_text table,
            however thumb(images) need to be fetched and inserted into files. For that we need to skip that element from the
            elementToAdd list and handled differently.
        */
        $imageElement = "thumb"; // This element can be changed

        foreach($elementsToAdd as $element){

            /* Skip image element and add it to omeka item files. */
            if($element['elementName'] === $imageElement){
                $this->addFile($element['value'], $recordId);
                continue;
            }

            $insertOptions = array(
                'record_id' => $recordId, 'record_type' => $recordType,
                'element_id' => $element['elementId'], 'text' => $element['value'], 'html' => 0
            );
            $this->db->insert("Element_text", $insertOptions);
        }

    }

    function addFile($url, $itemId){
        $storage = Zend_Registry::get('storage');
        $file_name = round(microtime(true) * 1000).'.jpg';
        $image_path = $storage->getTempDir().'/'.$file_name;
        if(!empty($url)){
            $context_array = array('http'=>array('proxy'=>get_option('libco_server_proxy'),'request_fulluri'=>true));
            $context = stream_context_create($context_array);
            $imageData = file_get_contents($url,false,$context);
            file_put_contents($image_path,$imageData);

            // Link image to item
            $fHandler = new File();
            $fHandler->original_filename = $file_name;
            $fHandler->setDefaults($image_path);
            $item = $this->db->getTable('Item')->find($itemId);
            $item->addFile($fHandler);
            $item->saveFiles();
        }
    }

    function parseResult($recordToAdd){
        $elements = array();
        $isValidItem = false;

        $resultKeyValues = $this->getArrayKeyValues($recordToAdd);
        foreach($resultKeyValues as $items){
            foreach($items as $key => $value){
                if(strtolower($key) === "title") //Items should always have a title, otherwise it is not a valid record.
                    $isValidItem = true;

                $element = $this->getElement($key);
                if(!empty($element) && !empty($value) && $value != "null"){
                    $element['value'] = $value;
                    $elements [] = $element;
                }
            }
        }

        /* Return null if 'Title' is not among the elements to be added. */
        if($isValidItem)
            return $elements;
    }

    function getElement($elementName)
    {
        //$element = array();
        $dcType = "Dublin Core";

        switch ($elementName) {
            case 'id':
                $elementId = $this->getElementId('Identifier', $dcType);
                $name = 'Identifier';
                break;

            case 'thumb': // This field will be used to download and store images into file
                $elementId = $this->getElementId('References', $dcType);
                $name = 'thumb';
                break;

            case 'title':
                $elementId = $this->getElementId('Title', $dcType);
                $name = 'Title';
                break;

            case 'description':
                $elementId = $this->getElementId('Description', $dcType);
                $name = 'Description';
                break;

            case 'creator':
                $elementId = $this->getElementId('Creator', $dcType);
                $name = 'Creator';
                break;

            case 'year':
                $elementId = $this->getElementId('Date', $dcType);
                $name = 'Date';
                break;

            case 'dataProvider':
                $elementId = $this->getElementId('Source', $dcType);
                $name = 'Source';
                break;

            case 'provider':
                $elementId = $this->getElementId('Source', $dcType);
                $name = 'Source';
                break;

            /* Temporarly urls of the search record are being stored in
               reference element of the item (of type Dublin Core). */
            case 'url':
                $elementId = $this->getElementId('Source', $dcType);
                $name = 'Source';
                break;

/*
            case 'urloriginal':
                $elementId = $this->getElementId('References', $dcType);
                break;

            case 'urlfromSourceAPI':
                $elementId = $this->getElementId('References', $dcType);
                break;

            case 'fullresolution':
                $elementId = $this->getElementId('References', $dcType);
                break;*/

            case 'rights':
                $elementId = $this->getElementId('Rights', $dcType);
                $name = 'Rights';
                break;

            case 'externalId':
                $elementId = $this->getElementId('References', $dcType);
                $name = 'References';
                break;
        }

        if(!empty($elementId))
            return array('elementId' => $elementId, 'elementName' => $name);
    }

    function getElementId($elementName, $typeName){
        $db = get_db();
        $elementTable = $db->getTable('Element');
        $element = $elementTable->findByElementSetNameAndElementName($typeName, $elementName);
        if(array_key_exists('id',$element))
            return $element->id;
    }

    function getArrayKeyValues($array){
        $iterator = new RecursiveIteratorIterator(new RecursiveArrayIterator($array));
        $keys = array();
        foreach ($iterator as $key => $value) {
            for ($i = $iterator->getDepth() - 1; $i >= 0; $i--) {
                $key = $iterator->getSubIterator($i)->key();
            }
            if(!empty($value))
                $keys[] = array($key => $value);
        }
        return $keys;
    }

}