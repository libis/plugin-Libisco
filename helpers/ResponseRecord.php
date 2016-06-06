<?php

/**
 * User: NaeemM
 * Date: 23/02/2016
 */
class ResponseRecord
{
    public $administrative;
    public $usage;
    public $provenance;
    public $resourceType;
    public $descriptiveData;
    public $media;

    /**
     * ResponseRecord constructor.
     */
    public function __construct()
    {
    }

    public function initialize($administrative, $usage, $provenance, $resourceType ,$descriptiveData ,$media ){
        $this->administrative = $administrative;
        $this->usage = $usage;
        $this->provenance = $provenance;
        $this->resourceType = $resourceType;
        $this->descriptiveData = $descriptiveData;
        $this->media = $media;
    }

    public function getDescriptiveFields(){
        $descFields = array();

        if(!empty($this->descriptiveData)){
            foreach($this->descriptiveData as $key => $value){
                if(empty($value))
                    continue;

                if(is_array($value)){
                    if(array_key_exists('default', $value))
                        $descFields[$key] = $value['default'];
                    else
                        $descFields[$key] = $value;

                }
                else
                    $descFields[$key] = $value;
            }
        }
        return $descFields;
    }

    public function getAdminFields(){
        $adminFields = array();

        if(!empty($this->administrative)){
            foreach($this->administrative as $key => $value){
                if(!empty($value))
                    $adminFields[$key] = $value;
            }
        }
        return $adminFields;
    }

    public function getUsageFields(){
        $usageFields = array();

        if(!empty($this->usage)){
            foreach($this->usage as $key => $value){
                if(!empty($value))
                    $usageFields[$key] = $value;
            }
        }
        return $usageFields;
    }


    public function getProvenanceFields(){
        return array('provenance' => $this->provenance);
    }

    public function getMediaFields(){
        return array('media' => $this->media);
    }

    public function getResourceType(){
        return array('resourcetype' => $this->resourceType);
    }

    public function getAllFields(){
        $test = $this->getUsageFields();

        return array_merge($this->getAdminFields(), $this->getUsageFields(), $this->getProvenanceFields(),
            $this->getResourceType(), $this->getDescriptiveFields(), $this->getMediaFields()
        );
    }

    /**
     * Check if a record is a valid record before importing into omeka. Minimum information that we need to import a
     * record as an omeka item is a Title. Therefore a record returned from WITH API without a label is an invalid record.
     *
     * @return bool
     */
    public function isValidRecord(){
        $descriptiveFields = $this->getDescriptiveFields();
        /*if(array_key_exists('label', $descriptiveFields) && is_array($descriptiveFields['label']) && !empty(current($descriptiveFields['label'])))
            return true; */
        $desField = current($descriptiveFields['label']);
        if(array_key_exists('label', $descriptiveFields) && is_array($descriptiveFields['label']) && !empty($desField))
            return true;
    }

}
